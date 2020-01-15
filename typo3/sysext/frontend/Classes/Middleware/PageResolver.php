<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Frontend\Middleware;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendWorkspaceRestriction;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Routing\RouteNotFoundException;
use TYPO3\CMS\Core\Routing\SiteRouteResult;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page\PageAccessFailureReasons;

/**
 * Process the ID, type and other parameters.
 * After this point we have an array, TSFE->page, which is the page-record of the current page, $TSFE->id.
 *
 * Now, if there is a backend user logged in and he has NO access to this page,
 * then re-evaluate the id shown!
 */
class PageResolver implements MiddlewareInterface
{
    /**
     * @var TypoScriptFrontendController
     */
    protected $controller;

    public function __construct(TypoScriptFrontendController $controller = null)
    {
        $this->controller = $controller ?? $GLOBALS['TSFE'];
    }

    /**
     * Resolve the page ID
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // First, resolve the root page of the site, the Page ID of the current domain
        if (($site = $request->getAttribute('site', null)) instanceof SiteInterface) {
            $this->controller->domainStartPage = $site->getRootPageId();
        }
        $language = $request->getAttribute('language', null);

        $hasSiteConfiguration = $language instanceof SiteLanguage && $site instanceof Site;

        // Resolve the page ID based on TYPO3's native routing functionality
        if ($hasSiteConfiguration) {
            /** @var SiteRouteResult $previousResult */
            $previousResult = $request->getAttribute('routing', null);
            if (!$previousResult) {
                return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                    $request,
                    'The requested page does not exist',
                    ['code' => PageAccessFailureReasons::PAGE_NOT_FOUND]
                );
            }

            $requestId = (string)($request->getQueryParams()['id'] ?? '');
            if (!empty($requestId)) {
                $page = $this->resolvePageId($requestId);
                if ($page === null) {
                    return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                        $request,
                        'The requested page does not exist',
                        ['code' => PageAccessFailureReasons::PAGE_NOT_FOUND]
                    );
                }
                // Legacy URIs (?id=12345) takes precedence, not matter if a route is given
                $pageArguments = new PageArguments(
                    (int)($page['l10n_parent'] ?: $page['uid']),
                    (string)($request->getQueryParams()['type'] ?? '0'),
                    [],
                    [],
                    $request->getQueryParams()
                );
            } else {
                // Check for the route
                try {
                    $pageArguments = $site->getRouter()->matchRequest($request, $previousResult);
                } catch (RouteNotFoundException $e) {
                    return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                        $request,
                        'The requested page does not exist',
                        ['code' => PageAccessFailureReasons::PAGE_NOT_FOUND]
                    );
                }
            }
            if (!$pageArguments->getPageId()) {
                return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                    $request,
                    'The requested page does not exist',
                    ['code' => PageAccessFailureReasons::PAGE_NOT_FOUND]
                );
            }

            $this->controller->id = $pageArguments->getPageId();
            $this->controller->type = $pageArguments->getPageType() ?? $this->controller->type;
            $this->controller->MP = $pageArguments->getArguments()['MP'] ?? $this->controller->MP;
            $request = $request->withAttribute('routing', $pageArguments);
            // stop in case arguments are dirty (=defined twice in route and GET query parameters)
            if ($pageArguments->areDirty()) {
                return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                    $request,
                    'The requested URL is not distinct',
                    ['code' => PageAccessFailureReasons::PAGE_NOT_FOUND]
                );
            }

            // merge the PageArguments with the request query parameters
            $queryParams = array_replace_recursive($request->getQueryParams(), $pageArguments->getArguments());
            $request = $request->withQueryParams($queryParams);
            $this->controller->setPageArguments($pageArguments);

            // At this point, we later get further route modifiers
            // for bw-compat we update $GLOBALS[TYPO3_REQUEST] to be used later in TSFE.
            $GLOBALS['TYPO3_REQUEST'] = $request;
        } else {
            // old-school page resolving for realurl, cooluri etc.
            $this->controller->siteScript = $request->getAttribute('normalizedParams')->getSiteScript();
            if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkAlternativeIdMethods-PostProc'])) {
                trigger_error('The "checkAlternativeIdMethods-PostProc" hook will be removed in TYPO3 v10.0 in favor of PSR-15. Use a middleware instead.', E_USER_DEPRECATED);
                $this->checkAlternativeIdMethods($this->controller);
            }
        }

        $this->controller->determineId();

        // No access? Then remove user & Re-evaluate the page-id
        if ($this->controller->isBackendUserLoggedIn() && !$GLOBALS['BE_USER']->doesUserHaveAccess($this->controller->page, Permission::PAGE_SHOW)) {
            unset($GLOBALS['BE_USER']);
            // Register an empty backend user as aspect
            $this->setBackendUserAspect(GeneralUtility::makeInstance(Context::class), null);
            if (!$hasSiteConfiguration) {
                $this->checkAlternativeIdMethods($this->controller);
            }
            $this->controller->determineId();
        }

        return $handler->handle($request);
    }

    /**
     * Provides ways to bypass the '?id=[xxx]&type=[xx]' format, using either PATH_INFO or Server Rewrites
     *
     * Two options:
     * 1) Use PATH_INFO (also Apache) to extract id and type from that var. Does not require any special modules compiled with apache. (less typical)
     * 2) Using hook which enables features like those provided from "realurl" extension (AKA "Speaking URLs")
     *
     * @param TypoScriptFrontendController $tsfe
     */
    protected function checkAlternativeIdMethods(TypoScriptFrontendController $tsfe)
    {
        // Call post processing function for custom URL methods.
        $_params = ['pObj' => &$tsfe];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkAlternativeIdMethods-PostProc'] ?? [] as $_funcRef) {
            GeneralUtility::callUserFunction($_funcRef, $_params, $tsfe);
        }
    }

    /**
     * @param string $pageId
     * @return array|null
     */
    protected function resolvePageId(string $pageId): ?array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(FrontendWorkspaceRestriction::class));

        if (MathUtility::canBeInterpretedAsInteger($pageId)) {
            $constraint = $queryBuilder->expr()->eq(
                'uid',
                $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT)
            );
        } else {
            $constraint = $queryBuilder->expr()->eq(
                'alias',
                $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_STR)
            );
        }

        $statement = $queryBuilder
            ->select('uid', 'l10n_parent', 'pid')
            ->from('pages')
            ->where($constraint)
            ->execute();

        $page = $statement->fetch();
        if (empty($page)) {
            return null;
        }
        return $page;
    }

    /**
     * Register the backend user as aspect
     *
     * @param Context $context
     * @param BackendUserAuthentication $user
     */
    protected function setBackendUserAspect(Context $context, BackendUserAuthentication $user = null)
    {
        $context->setAspect('backend.user', GeneralUtility::makeInstance(UserAspect::class, $user));
        $context->setAspect('workspace', GeneralUtility::makeInstance(WorkspaceAspect::class, $user ? $user->workspace : 0));
    }
}

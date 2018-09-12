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
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Routing\PageRouter;
use TYPO3\CMS\Core\Routing\RouteResult;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
            /** @var RouteResult $previousResult */
            $previousResult = $request->getAttribute('routing', new RouteResult($request->getUri(), $site, $language));
            if (!empty($previousResult->getTail())) {
                // Check for the route
                $routeResult = $this->getPageRouter()->matchRoute($request, $previousResult->getTail(), $site, $language);
                $request = $request->withAttribute('routing', $routeResult);
                if (is_array($routeResult['page'])) {
                    $page = $routeResult['page'];
                    $this->controller->id = (int)($page['l10n_parent'] > 0 ? $page['l10n_parent'] : $page['uid']);
                    $tail = $routeResult->getTail();
                    $requestedUri = $request->getUri();
                    // the request was called with "/my-page" but it's actually called "/my-page/", let's do a redirect
                    if ($tail === '' && substr($requestedUri->getPath(), -1) !== substr($page['slug'], -1)) {
                        $uri = $requestedUri->withPath($requestedUri->getPath() . '/');
                        return new RedirectResponse($uri, 307);
                    }
                    if ($tail === '/') {
                        $uri = $requestedUri->withPath(rtrim($requestedUri->getPath(), '/'));
                        return new RedirectResponse($uri, 307);
                    }
                    if (!empty($tail)) {
                        // @todo: kick in the resolvers for the RouteEnhancers at this point
                        return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                            $request,
                            'The requested page does not exist',
                            ['code' => PageAccessFailureReasons::PAGE_NOT_FOUND]
                        );
                    }
                } else {
                    return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                        $request,
                        'The requested page does not exist',
                        ['code' => PageAccessFailureReasons::PAGE_NOT_FOUND]
                    );
                }
                // At this point, we later get further route modifiers
                // for bw-compat we update $GLOBALS[TYPO3_REQUEST] to be used later in TSFE.
                $GLOBALS['TYPO3_REQUEST'] = $request;
            }
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

        // Evaluate the cache hash parameter
        $this->controller->makeCacheHash($request);

        return $handler->handle($request);
    }

    /**
     * @return PageRouter
     */
    protected function getPageRouter(): PageRouter
    {
        return GeneralUtility::makeInstance(PageRouter::class);
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

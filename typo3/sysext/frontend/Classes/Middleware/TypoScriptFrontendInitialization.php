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
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Aspect\PreviewAspect;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page\PageAccessFailureReasons;

/**
 * Creates an instance of TypoScriptFrontendController and makes this globally available
 * via $GLOBALS['TSFE'].
 *
 * In addition, determineId builds up the rootline based on a valid frontend-user authentication and
 * Backend permissions if previewing.
 *
 * @internal this middleware might get removed in TYPO3 v11.0.
 */
class TypoScriptFrontendInitialization implements MiddlewareInterface
{
    /**
     * @var Context
     */
    protected $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * Creates an instance of TSFE and sets it as a global variable.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $GLOBALS['TYPO3_REQUEST'] = $request;
        /** @var Site $site */
        $site = $request->getAttribute('site', null);
        $pageArguments = $request->getAttribute('routing', null);
        if (!$pageArguments instanceof PageArguments) {
            // Page Arguments must be set in order to validate. This middleware only works if PageArguments
            // is available, and is usually combined with the Page Resolver middleware
            return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                $request,
                'Page Arguments could not be resolved',
                ['code' => PageAccessFailureReasons::INVALID_PAGE_ARGUMENTS]
            );
        }
        $this->context->setAspect('frontend.preview', GeneralUtility::makeInstance(PreviewAspect::class));

        $controller = GeneralUtility::makeInstance(
            TypoScriptFrontendController::class,
            $this->context,
            $site,
            $request->getAttribute('language', $site->getDefaultLanguage()),
            $pageArguments,
            $request->getAttribute('frontend.user', null)
        );
        if ($pageArguments->getArguments()['no_cache'] ?? $request->getParsedBody()['no_cache'] ?? false) {
            $controller->set_no_cache('&no_cache=1 has been supplied, so caching is disabled! URL: "' . (string)$request->getUri() . '"');
        }
        // Usually only set by the PageArgumentValidator
        if ($request->getAttribute('noCache', false)) {
            $controller->no_cache = 1;
        }

        $controller->determineId();

        // No access? Then remove user and re-evaluate the page id
        if ($controller->isBackendUserLoggedIn() && !$GLOBALS['BE_USER']->doesUserHaveAccess($controller->page, Permission::PAGE_SHOW)) {
            unset($GLOBALS['BE_USER']);
            // Register an empty backend user as aspect
            $this->setBackendUserAspect(null);
            $controller->determineId();
        }

        // Make TSFE globally available
        $GLOBALS['TSFE'] = $controller;
        return $handler->handle($request);
    }

    /**
     * Register the backend user as aspect
     *
     * @param BackendUserAuthentication|null $user
     */
    protected function setBackendUserAspect(BackendUserAuthentication $user): void
    {
        $this->context->setAspect('backend.user', GeneralUtility::makeInstance(UserAspect::class, $user));
        $this->context->setAspect('workspace', GeneralUtility::makeInstance(WorkspaceAspect::class, $user ? $user->workspace : 0));
    }
}

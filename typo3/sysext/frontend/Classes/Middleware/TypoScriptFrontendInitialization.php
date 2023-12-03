<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Frontend\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Aspect\PreviewAspect;
use TYPO3\CMS\Frontend\Cache\CacheInstruction;
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
 * @internal this middleware might get removed later.
 */
final class TypoScriptFrontendInitialization implements MiddlewareInterface
{
    public function __construct(
        private readonly Context $context
    ) {}

    /**
     * Creates an instance of TSFE and sets it as a global variable.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // The cache information attribute may be set by previous middlewares already. Make sure we have one from now on.
        $cacheInstruction = $request->getAttribute('frontend.cache.instruction', new CacheInstruction());
        $request = $request->withAttribute('frontend.cache.instruction', $cacheInstruction);

        // Make sure frontend.preview is given from now on.
        if (!$this->context->hasAspect('frontend.preview')) {
            $this->context->setAspect('frontend.preview', new PreviewAspect());
        }
        // If the frontend is showing a preview, caching MUST be disabled.
        if ($this->context->getPropertyFromAspect('frontend.preview', 'isPreview', false)) {
            // @todo: To disentangle this, the preview aspect could be dropped and middlewares that set isPreview true
            //        could directly set $cacheInstruction->disableCache() instead.
            $cacheInstruction->disableCache('EXT:frontend: Disabled cache due to enabled frontend.preview aspect isPreview.');
        }

        $GLOBALS['TYPO3_REQUEST'] = $request;
        /** @var Site $site */
        $site = $request->getAttribute('site');
        $pageArguments = $request->getAttribute('routing');
        if (!$pageArguments instanceof PageArguments) {
            // Page Arguments must be set in order to validate. This middleware only works if PageArguments
            // is available, and is usually combined with the Page Resolver middleware
            return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
                $request,
                'Page Arguments could not be resolved',
                ['code' => PageAccessFailureReasons::INVALID_PAGE_ARGUMENTS]
            );
        }

        $controller = GeneralUtility::makeInstance(
            TypoScriptFrontendController::class,
            $this->context,
            $site,
            $request->getAttribute('language', $site->getDefaultLanguage()),
            $pageArguments
        );
        $directResponse = $controller->determineId($request);
        if ($directResponse) {
            return $directResponse;
        }
        // Check if backend user has read access to this page.
        if ($this->context->getPropertyFromAspect('backend.user', 'isLoggedIn', false)
            && $this->context->getPropertyFromAspect('frontend.preview', 'isPreview', false)
            && !$GLOBALS['BE_USER']->doesUserHaveAccess($controller->page, Permission::PAGE_SHOW)
        ) {
            return GeneralUtility::makeInstance(ErrorController::class)->accessDeniedAction(
                $request,
                'ID was not an accessible page',
                $controller->getPageAccessFailureReasons(PageAccessFailureReasons::ACCESS_DENIED_PAGE_NOT_RESOLVED)
            );
        }

        $request = $request->withAttribute('frontend.controller', $controller);
        // Make TSFE globally available
        // @todo deprecate $GLOBALS['TSFE'] once TSFE is retrieved from the
        //       PSR-7 request attribute frontend.controller throughout TYPO3 core
        $GLOBALS['TSFE'] = $controller;
        return $handler->handle($request);
    }
}

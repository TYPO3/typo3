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
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Aspect\PreviewAspect;
use TYPO3\CMS\Frontend\Cache\CacheInstruction;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page\PageAccessFailureReasons;
use TYPO3\CMS\Frontend\Page\PageInformationCreationFailedException;
use TYPO3\CMS\Frontend\Page\PageInformationFactory;

/**
 * Create and fill PageInformation object and attach as
 * 'frontend.page.information' Request attribute.
 *
 * This middleware does all main access checks to the target page, resolves shortcut
 * pages and languages and workspace overlays. When all goes well, it dispatches to
 * other middleware below. In case of failed access checks or other errors, it returns
 * an early response before dispatching to main page rendering below.
 *
 * @internal
 */
final readonly class TypoScriptFrontendInitialization implements MiddlewareInterface
{
    public function __construct(
        private Context $context,
        private TimeTracker $timeTracker,
        private PageInformationFactory $pageInformationFactory,
        private LoggerInterface $logger,
        private ErrorController $errorController,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Make sure frontend.preview aspect is given from now on.
        if (!$this->context->hasAspect('frontend.preview')) {
            $this->context->setAspect('frontend.preview', new PreviewAspect());
        }
        // Cache instruction attribute may have been set by previous middlewares.
        $cacheInstruction = $request->getAttribute('frontend.cache.instruction', new CacheInstruction());
        if ($this->context->getPropertyFromAspect('frontend.preview', 'isPreview', false)) {
            // If the frontend is showing a preview, caching MUST be disabled.
            $cacheInstruction->disableCache('EXT:frontend: Disabled cache due to enabled frontend.preview aspect isPreview.');
        }
        // Make sure cache instruction attribute is always set from now on.
        $request = $request->withAttribute('frontend.cache.instruction', $cacheInstruction);

        if (!$request->getAttribute('routing') instanceof PageArguments
            || !$request->getAttribute('normalizedParams') instanceof NormalizedParams
        ) {
            // Ensure some crucial attributes exist at this point.
            throw new \RuntimeException(
                'Request attribute "routing" or "normalizedParams" not found. Error in previous middleware.',
                1703150865
            );
        }

        try {
            $this->timeTracker->push('Create PageInformation');
            $pageInformation = $this->pageInformationFactory->create($request);
        } catch (PageInformationCreationFailedException $exception) {
            return $exception->getResponse();
        } finally {
            $this->timeTracker->pull();
        }
        $site = $request->getAttribute('site');
        if (!$site->isTypoScriptRoot() && $pageInformation->getSysTemplateRows() === []) {
            // Early exception if there is no typoscript definition in current site and no sys_template at all.
            // @todo improve message?
            $message = 'No TypoScript record found!';
            $this->logger->error($message);
            $response = $this->errorController->internalErrorAction(
                $request,
                $message,
                ['code' => PageAccessFailureReasons::RENDERING_INSTRUCTIONS_NOT_FOUND]
            );
            throw new PageInformationCreationFailedException($response, 1705656657);
        }
        $request = $request->withAttribute('frontend.page.information', $pageInformation);

        $controller = GeneralUtility::makeInstance(TypoScriptFrontendController::class);
        $controller->initializePageRenderer($request);
        $controller->id = $pageInformation->getId();
        $controller->page = $pageInformation->getPageRecord();
        $controller->contentPid = $pageInformation->getContentFromPid();
        $controller->rootLine = $pageInformation->getRootLine();
        $controller->config['rootLine'] = $pageInformation->getLocalRootLine();
        // Update SYS_LASTCHANGED at the very last, when page record was finally resolved.
        // Is also called when a translated page is in use, so the register reflects
        // the state of the translated page, not the page in the default language.
        $controller->register['SYS_LASTCHANGED'] = (int)$pageInformation->getPageRecord()['tstamp'];
        if ($controller->register['SYS_LASTCHANGED'] < (int)$pageInformation->getPageRecord()['SYS_LASTCHANGED']) {
            $controller->register['SYS_LASTCHANGED'] = (int)$pageInformation->getPageRecord()['SYS_LASTCHANGED'];
        }
        $request = $request->withAttribute('frontend.controller', $controller);
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $GLOBALS['TSFE'] = $controller;

        return $handler->handle($request);
    }
}

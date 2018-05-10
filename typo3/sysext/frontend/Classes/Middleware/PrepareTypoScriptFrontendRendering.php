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
use Psr\Http\Server\RequestHandlerInterface as PsrRequestHandlerInterface;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Initialization of TypoScriptFrontendController
 *
 * Do all necessary preparation steps for rendering
 */
class PrepareTypoScriptFrontendRendering implements MiddlewareInterface
{
    /**
     * @var TypoScriptFrontendController
     */
    protected $controller;

    /**
     * @var TimeTracker
     */
    protected $timeTracker;

    public function __construct(TypoScriptFrontendController $controller = null, TimeTracker $timeTracker = null)
    {
        $this->controller = $controller ?: $GLOBALS['TSFE'];
        $this->timeTracker = $timeTracker ?: GeneralUtility::makeInstance(TimeTracker::class);
    }

    /**
     * Initialize TypoScriptFrontendController to the point right before rendering of the page is triggered
     *
     * @param ServerRequestInterface $request
     * @param PsrRequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, PsrRequestHandlerInterface $handler): ResponseInterface
    {
        // Starts the template
        $this->timeTracker->push('Start Template');
        $this->controller->initTemplate();
        $this->timeTracker->pull();
        // Get from cache
        $this->timeTracker->push('Get Page from cache');
        // Locks may be acquired here
        $this->controller->getFromCache();
        $this->timeTracker->pull();
        // Get config if not already gotten
        // After this, we should have a valid config-array ready
        $this->controller->getConfigArray();
        // Setting language and locale
        $this->timeTracker->push('Setting language and locale');
        $this->controller->settingLanguage();
        $this->controller->settingLocale();
        $this->timeTracker->pull();

        // Convert POST data to utf-8 for internal processing if metaCharset is different
        $this->controller->convPOSTCharset();

        $this->controller->initializeRedirectUrlHandlers();
        $this->controller->handleDataSubmission();

        return $handler->handle($request);
    }
}

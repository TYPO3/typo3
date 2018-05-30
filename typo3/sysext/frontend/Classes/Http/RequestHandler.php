<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Frontend\Http;

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
use Psr\Http\Server\RequestHandlerInterface as PsrRequestHandlerInterface;
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Core\Http\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page\PageGenerator;

/**
 * This is the main entry point of the TypoScript driven standard front-end
 *
 * Basically put, this is the script which all requests for TYPO3 delivered pages goes to in the
 * frontend (the website). The script instantiates a $TSFE object, includes libraries and does a little logic here
 * and there in order to instantiate the right classes to create the webpage.
 * Previously, this was called index_ts.php and also included the logic for the lightweight "eID" concept,
 * which is now handled in a separate middleware (EidHandler).
 */
class RequestHandler implements RequestHandlerInterface, PsrRequestHandlerInterface
{
    /**
     * Instance of the timetracker
     * @var TimeTracker
     */
    protected $timeTracker;

    /**
     * Handles a frontend request
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        return $this->handle($request);
    }

    /**
     * Handles a frontend request, after finishing running middlewares
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface|null
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Fetch the initialized time tracker object
        $this->timeTracker = GeneralUtility::makeInstance(TimeTracker::class);
        /** @var TypoScriptFrontendController $controller */
        $controller = $GLOBALS['TSFE'];

        // Generate page
        if ($controller->isGeneratePage()) {
            $this->timeTracker->push('Page generation');
            $controller->generatePage_preProcessing();
            $controller->preparePageContentGeneration();
            // Content generation
            PageGenerator::renderContent();
            $controller->setAbsRefPrefix();
            $controller->generatePage_postProcessing();
            $this->timeTracker->pull();
        }
        $controller->releaseLocks();

        // Render non-cached page parts by replacing placeholders which are taken from cache or added during page generation
        if ($controller->isINTincScript()) {
            if (!$controller->isGeneratePage()) {
                // When page was generated, this was already called. Avoid calling this twice.
                $controller->preparePageContentGeneration();
            }
            $this->timeTracker->push('Non-cached objects');
            $controller->INTincScript();
            $this->timeTracker->pull();
        }

        // Create a Response object when sending content
        $response = new Response();

        // Output content
        $isOutputting = $controller->isOutputting();
        if ($isOutputting) {
            $this->timeTracker->push('Print Content');
            $controller->processOutput();
            $this->timeTracker->pull();
        }
        // Store session data for fe_users
        $controller->storeSessionData();

        // @deprecated since TYPO3 v9.3, will be removed in TYPO3 v10.0.
        $redirectResponse = $controller->redirectToExternalUrl(true);
        if ($redirectResponse instanceof ResponseInterface) {
            return $redirectResponse;
        }

        // Statistics
        $GLOBALS['TYPO3_MISC']['microtime_end'] = microtime(true);
        if ($isOutputting && ($controller->config['config']['debug'] ?? !empty($GLOBALS['TYPO3_CONF_VARS']['FE']['debug']))) {
            $response = $response->withHeader('X-TYPO3-Parsetime', $this->timeTracker->getParseTime() . 'ms');
        }

        // Preview info
        $controller->previewInfo();
        // Hook for end-of-frontend
        $controller->hook_eofe();
        // Finish timetracking
        $this->timeTracker->pull();

        if ($isOutputting) {
            $response->getBody()->write($controller->content);
        }

        return $isOutputting ? $response : new NullResponse();
    }

    /**
     * This request handler can handle any frontend request.
     *
     * @param ServerRequestInterface $request
     * @return bool If the request is not an eID request, TRUE otherwise FALSE
     */
    public function canHandleRequest(ServerRequestInterface $request): bool
    {
        return true;
    }

    /**
     * Returns the priority - how eager the handler is to actually handle the
     * request.
     *
     * @return int The priority of the request handler.
     */
    public function getPriority(): int
    {
        return 50;
    }
}

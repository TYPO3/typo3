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
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Initialization of TypoScriptFrontendController
 *
 * Do all necessary preparation steps for rendering
 *
 * @internal this middleware might get removed in TYPO3 v10.x.
 */
class PrepareTypoScriptFrontendRendering implements MiddlewareInterface
{
    /**
     * @var TimeTracker
     */
    protected $timeTracker;

    public function __construct(TimeTracker $timeTracker)
    {
        $this->timeTracker = $timeTracker;
    }

    /**
     * Initialize TypoScriptFrontendController to the point right before rendering of the page is triggered
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var TypoScriptFrontendController */
        $controller = $request->getAttribute('frontend.controller');

        // as long as TSFE throws errors with the global object, this needs to be set, but
        // should be removed later-on once TypoScript Condition Matcher is built with the current request object.
        $GLOBALS['TYPO3_REQUEST'] = $request;
        // Get from cache
        $this->timeTracker->push('Get Page from cache');
        // Locks may be acquired here
        $controller->getFromCache($request);
        $this->timeTracker->pull();
        // Get config if not already gotten
        // After this, we should have a valid config-array ready
        $controller->getConfigArray($request);

        // Convert POST data to utf-8 for internal processing if metaCharset is different
        if ($controller->metaCharset !== 'utf-8' && $request->getMethod() === 'POST') {
            $parsedBody = $request->getParsedBody();
            if (is_array($parsedBody) && !empty($parsedBody)) {
                $this->convertCharsetRecursivelyToUtf8($parsedBody, $controller->metaCharset);
                $request = $request->withParsedBody($parsedBody);
            }
        }
        $response = $handler->handle($request);

        /**
         * Release TSFE locks. They have been acquired in the above call to controller->getFromCache().
         * TSFE locks are usually released by the RequestHandler 'final' middleware.
         * However, when some middlewares returns early (e.g. Shortcut and MountPointRedirect,
         * which both skip inner middlewares), or due to Exceptions, locks still need to be released explicitly.
         */
        $controller->releaseLocks();

        return $response;
    }

    /**
     * Small helper function to convert charsets for arrays to UTF-8
     *
     * @param mixed $data given by reference (string/array usually)
     * @param string $fromCharset convert FROM this charset
     */
    protected function convertCharsetRecursivelyToUtf8(&$data, string $fromCharset)
    {
        foreach ($data as $key => $value) {
            if (is_array($data[$key])) {
                $this->convertCharsetRecursivelyToUtf8($data[$key], $fromCharset);
            } elseif (is_string($data[$key])) {
                $data[$key] = mb_convert_encoding($data[$key], 'utf-8', $fromCharset);
            }
        }
    }
}

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

/**
 * Initialization of TypoScriptFrontendController
 *
 * Do all necessary preparation steps for rendering
 *
 * @internal this middleware might get removed later.
 */
final class PrepareTypoScriptFrontendRendering implements MiddlewareInterface
{
    public function __construct(
        private readonly TimeTracker $timeTracker
    ) {}

    /**
     * Initialize TypoScriptFrontendController to the point right before rendering of the page is triggered
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $controller = $request->getAttribute('frontend.controller');

        // as long as TSFE throws errors with the global object, this needs to be set, but
        // should be removed later-on once TypoScript Condition Matcher is built with the current request object.
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $this->timeTracker->push('Get Page from cache');
        // Get from cache. Locks may be acquired here. After this, we should have a valid config-array ready.
        $request = $controller->getFromCache($request);
        $this->timeTracker->pull();

        // Set new request which now has the frontend.typoscript attribute
        $GLOBALS['TYPO3_REQUEST'] = $request;

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
}

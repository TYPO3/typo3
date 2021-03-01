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
 * Initializes the time tracker (singleton) for the whole TYPO3 Frontend
 *
 * @internal
 */
class TimeTrackerInitialization implements MiddlewareInterface
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
     * Starting time tracking (by setting up a singleton object)
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $timeTrackingEnabled = $this->isBackendUserCookieSet($request);
        $this->timeTracker->setEnabled($timeTrackingEnabled);
        $this->timeTracker->start(microtime(true));
        $this->timeTracker->push('');

        $response = $handler->handle($request);

        // Finish time tracking
        $this->timeTracker->pull();
        $this->timeTracker->finish();

        if ($this->isDebugModeEnabled()) {
            return $response->withHeader('X-TYPO3-Parsetime', $this->timeTracker->getParseTime() . 'ms');
        }
        return $response;
    }

    protected function isBackendUserCookieSet(ServerRequestInterface $request): bool
    {
        $configuredCookieName = trim($GLOBALS['TYPO3_CONF_VARS']['BE']['cookieName']) ?: 'be_typo_user';
        return !empty($request->getCookieParams()[$configuredCookieName]);
    }

    protected function isDebugModeEnabled(): bool
    {
        $controller = $GLOBALS['TSFE'] ?? null;
        if ($controller instanceof TypoScriptFrontendController && !empty($controller->config['config']['debug'] ?? false)) {
            return true;
        }
        return !empty($GLOBALS['TYPO3_CONF_VARS']['FE']['debug']);
    }
}

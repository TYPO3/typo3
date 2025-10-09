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

/**
 * Handle cache timeout that is set in typoscript.
 *
 * @internal
 */
class CacheTimeout implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        $config = $request->getAttribute('frontend.typoscript')?->getConfigArray() ?? [];
        if ($config['cache_clearAtMidnight'] ?? false) {
            // @todo: We should probably decide to deprecate or remove cache_clearAtMidnight
            //        altogether since it is a flawed concept based on server timezone
            //        "when is midnight?".
            $cacheDataCollector = $request->getAttribute('frontend.cache.collector');
            $timeOutTime = min($GLOBALS['EXEC_TIME'] + $cacheDataCollector->resolveLifetime(), PHP_INT_MAX);
            $midnightTime = mktime(0, 0, 0, (int)date('m', $timeOutTime), (int)date('d', $timeOutTime), (int)date('Y', $timeOutTime));
            // If the midnight time of the expire-day is greater than the current time,
            // we may set the timeOutTime to the new midnighttime.
            if ($midnightTime > $GLOBALS['EXEC_TIME']) {
                $cacheDataCollector->restrictMaximumLifetime($midnightTime - $GLOBALS['EXEC_TIME']);
            }
        }
        return $response;
    }
}

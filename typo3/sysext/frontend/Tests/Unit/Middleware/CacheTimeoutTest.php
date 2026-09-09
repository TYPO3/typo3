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

namespace TYPO3\CMS\Frontend\Tests\Unit\Middleware;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Cache\CacheDataCollector;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Frontend\Middleware\CacheTimeout;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class CacheTimeoutTest extends UnitTestCase
{
    #[Test]
    public function cacheLifetimeIsRestrictedToUpcomingMidnight(): void
    {
        $GLOBALS['EXEC_TIME'] = mktime(12, 0, 0, 1, 15, 2026);
        $cacheDataCollector = new CacheDataCollector();
        $cacheDataCollector->restrictMaximumLifetime(365 * 86400);
        $frontendTypoScript = new class {
            public function getConfigArray(): array
            {
                return ['cache_clearAtMidnight' => true];
            }
        };
        $request = new ServerRequest()
            ->withAttribute('frontend.typoscript', $frontendTypoScript)
            ->withAttribute('frontend.cache.collector', $cacheDataCollector);
        $requestHandler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response();
            }
        };

        new CacheTimeout()->process($request, $requestHandler);

        self::assertSame(12 * 3600, $cacheDataCollector->resolveLifetime());
    }
}

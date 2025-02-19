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

namespace TYPO3\CMS\Core\Tests\Unit\Middleware;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Cache\CacheDataCollectorInterface;
use TYPO3\CMS\Core\Cache\CacheTag;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Middleware\CacheDataCollectorAttribute;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class CacheTagsAttributeTest extends UnitTestCase
{
    #[Test]
    public function cacheTagsAttributeIsAddedToRequest(): void
    {
        $requestMock = $this->createMock(ServerRequestInterface::class);
        $requestMock
            ->expects(self::once())
            ->method('withAttribute')
            ->with('frontend.cache.collector', self::isInstanceOf(CacheDataCollectorInterface::class))
            ->willReturnSelf();
        $requestHandlerMock = $this->createMock(RequestHandlerInterface::class);
        $requestHandlerMock
            ->expects(self::once())
            ->method('handle')
            ->with(self::isInstanceOf(ServerRequestInterface::class))
            ->willReturn($this->createMock(ResponseInterface::class));
        $middleware = new CacheDataCollectorAttribute();
        $middleware->process($requestMock, $requestHandlerMock);
    }

    #[Test]
    public function cacheTagsAreExposedWithHeader(): void
    {
        $request = new ServerRequest();
        $requestHandlerMock = $this->createMock(RequestHandlerInterface::class);
        $requestHandlerMock
            ->expects(self::once())
            ->method('handle')
            ->willReturnCallback(
                function (ServerRequestInterface $request) {
                    $cacheDataCollector = $request->getAttribute('frontend.cache.collector');
                    $cacheDataCollector->addCacheTags(
                        ...array_map(
                            fn(int $n): CacheTag => new CacheTag(
                                sprintf('tx_meineextension_domain_model_mitglied_%d', $n)
                            ),
                            range(0, 100)
                        )
                    );
                    return new Response();
                }
            );

        $GLOBALS['TYPO3_CONF_VARS']['FE']['debug'] = true;
        $middleware = new CacheDataCollectorAttribute();
        $response = $middleware->process($request, $requestHandlerMock);
        $GLOBALS['TYPO3_CONF_VARS']['FE']['debug'] = false;

        self::assertTrue($response->hasHeader('X-TYPO3-Cache-Tags'));
        self::assertFalse($response->hasHeader('X-TYPO3-Cache-Tags-1'));
        self::assertTrue(strlen($response->getHeaderLine('X-TYPO3-Cache-Tags')) < 8000);
    }

    #[Test]
    public function cacheTagsAreSplitIntoMultipleHeader(): void
    {
        $request = new ServerRequest();
        $requestHandlerMock = $this->createMock(RequestHandlerInterface::class);
        $requestHandlerMock
            ->expects(self::once())
            ->method('handle')
            ->willReturnCallback(
                function (ServerRequestInterface $request) {
                    $cacheDataCollector = $request->getAttribute('frontend.cache.collector');
                    $cacheDataCollector->addCacheTags(
                        ...array_map(
                            fn(int $n): CacheTag => new CacheTag(
                                sprintf('tx_meineextension_domain_model_mitglied_%d', $n)
                            ),
                            range(0, 1000)
                        )
                    );
                    return new Response();
                }
            );

        $GLOBALS['TYPO3_CONF_VARS']['FE']['debug'] = true;
        $middleware = new CacheDataCollectorAttribute();
        $response = $middleware->process($request, $requestHandlerMock);
        $GLOBALS['TYPO3_CONF_VARS']['FE']['debug'] = false;

        self::assertTrue($response->hasHeader('X-TYPO3-Cache-Tags'));
        self::assertTrue($response->hasHeader('X-TYPO3-Cache-Tags-1'));
        self::assertTrue($response->hasHeader('X-TYPO3-Cache-Tags-2'));
        self::assertTrue($response->hasHeader('X-TYPO3-Cache-Tags-3'));
        self::assertTrue($response->hasHeader('X-TYPO3-Cache-Tags-3'));
        self::assertTrue($response->hasHeader('X-TYPO3-Cache-Tags-5'));
        self::assertTrue(strlen($response->getHeaderLine('X-TYPO3-Cache-Tags')) < 8000);
    }

}

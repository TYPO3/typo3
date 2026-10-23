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

namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Remote;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Extensionmanager\Remote\ApiRequestFailedException;
use TYPO3\CMS\Extensionmanager\Remote\FairPackageClient;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class FairPackageClientTest extends UnitTestCase
{
    private function createResponse(string $body, int $status = 200): ResponseInterface
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('getContents')->willReturn($body);
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn($status);
        $response->method('getBody')->willReturn($stream);
        return $response;
    }

    private function createNullCache(): FrontendInterface
    {
        $cache = $this->createMock(FrontendInterface::class);
        $cache->method('get')->willReturn(false);
        $cache->method('has')->willReturn(false);
        return $cache;
    }

    #[Test]
    public function thePackageDocumentIsReadByExtensionKey(): void
    {
        $requestedUri = '';
        $requestFactory = $this->createMock(RequestFactory::class);
        $requestFactory->method('request')->willReturnCallback(function (string $uri) use (&$requestedUri): ResponseInterface {
            $requestedUri = $uri;
            return $this->createResponse(json_encode([
                'id' => 'did:web:extensions.typo3.org:news',
                'extension_key' => 'news',
                'releases' => [['version' => '14.1.2']],
            ]));
        });

        $package = (new FairPackageClient($requestFactory, $this->createNullCache(), new Typo3Version()))->getPackage('News');

        self::assertSame('https://extensions.typo3.org/fair/v1/packages/news', $requestedUri);
        self::assertSame('14.1.2', $package->getNewestRelease()->version);
    }

    #[Test]
    public function anUnknownExtensionIsAnAnswerAndNotAFailure(): void
    {
        $requestFactory = $this->createMock(RequestFactory::class);
        $requestFactory->method('request')->willReturn($this->createResponse('{"error":"Package not found","status":404}', 404));

        self::assertNull((new FairPackageClient($requestFactory, $this->createNullCache(), new Typo3Version()))->getPackage('nope'));
    }

    #[Test]
    public function anUnreachableRemoteFails(): void
    {
        $requestFactory = $this->createMock(RequestFactory::class);
        $requestFactory->method('request')->willThrowException(new \RuntimeException('connection refused'));

        $this->expectException(ApiRequestFailedException::class);
        $this->expectExceptionCode(1789035174);

        (new FairPackageClient($requestFactory, $this->createNullCache(), new Typo3Version()))->getPackage('news');
    }
}

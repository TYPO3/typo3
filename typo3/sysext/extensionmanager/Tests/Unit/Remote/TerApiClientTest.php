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

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Extensionmanager\Remote\ApiRequestFailedException;
use TYPO3\CMS\Extensionmanager\Remote\TerApiClient;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[AllowMockObjectsWithoutExpectations]
final class TerApiClientTest extends UnitTestCase
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
        return $cache;
    }

    private function createMemoryCache(): FrontendInterface
    {
        $entries = [];
        $cache = $this->createMock(FrontendInterface::class);
        $cache->method('set')->willReturnCallback(static function (string $identifier, mixed $data) use (&$entries): void {
            $entries[$identifier] = $data;
        });
        $cache->method('get')->willReturnCallback(static function (string $identifier) use (&$entries): mixed {
            return $entries[$identifier] ?? false;
        });
        $cache->method('has')->willReturnCallback(static function (string $identifier) use (&$entries): bool {
            return isset($entries[$identifier]);
        });
        return $cache;
    }

    #[Test]
    public function queryOmitsTypo3VersionFilterWhenMajorVersionIsZero(): void
    {
        $requestedUri = '';
        $requestFactory = $this->createMock(RequestFactory::class);
        $requestFactory->method('request')->willReturnCallback(function (string $uri) use (&$requestedUri): ResponseInterface {
            $requestedUri = $uri;
            return $this->createResponse(json_encode(['results' => 0, 'extensions' => []]));
        });

        $subject = new TerApiClient($requestFactory, $this->createNullCache(), new Typo3Version());
        $subject->query('', 0);

        self::assertStringNotContainsString('typo3_version', $requestedUri);
    }

    #[Test]
    public function queryIncludesTypo3VersionFilterWhenMajorVersionGiven(): void
    {
        $requestedUri = '';
        $requestFactory = $this->createMock(RequestFactory::class);
        $requestFactory->method('request')->willReturnCallback(function (string $uri) use (&$requestedUri): ResponseInterface {
            $requestedUri = $uri;
            return $this->createResponse(json_encode(['results' => 0, 'extensions' => []]));
        });

        $subject = new TerApiClient($requestFactory, $this->createNullCache(), new Typo3Version());
        $subject->query('', 14);

        self::assertStringContainsString('typo3_version', $requestedUri);
        self::assertStringContainsString('14', $requestedUri);
    }

    /**
     * "title" only searches title and description; "search" also matches the
     * extension key, author, owner and composer name, and ranks an exact key
     * match first - which is what the imported list's search did.
     */
    #[Test]
    public function aSearchTermIsSentAsTheSearchFilter(): void
    {
        $requestedUri = '';
        $requestFactory = $this->createMock(RequestFactory::class);
        $requestFactory->method('request')->willReturnCallback(function (string $uri) use (&$requestedUri): ResponseInterface {
            $requestedUri = $uri;
            return $this->createResponse(json_encode(['results' => 0, 'extensions' => []]));
        });

        $subject = new TerApiClient($requestFactory, $this->createNullCache(), new Typo3Version());
        $subject->query('news', 14);

        self::assertStringContainsString('filter%5Bsearch%5D=news', $requestedUri);
        self::assertStringNotContainsString('filter%5Btitle%5D', $requestedUri);
    }

    #[Test]
    public function requestsNameTheExactTypo3Version(): void
    {
        $options = [];
        $requestFactory = $this->createMock(RequestFactory::class);
        $requestFactory->method('request')->willReturnCallback(function (string $uri, string $method, array $requestOptions) use (&$options): ResponseInterface {
            $options = $requestOptions;
            return $this->createResponse(json_encode(['results' => 0, 'extensions' => []]));
        });
        $typo3Version = new Typo3Version();

        $subject = new TerApiClient($requestFactory, $this->createNullCache(), $typo3Version);
        $subject->query('', 14);

        self::assertSame('TYPO3/' . $typo3Version->getVersion(), $options['headers']['User-Agent']);
    }

    #[Test]
    public function getVersionsUnwrapsAndSortsNewestFirst(): void
    {
        $requestFactory = $this->createMock(RequestFactory::class);
        $requestFactory->method('request')->willReturn($this->createResponse(json_encode([
            [
                ['number' => '1.0.0'],
                ['number' => '2.1.0'],
                ['number' => '2.0.0'],
            ],
        ])));

        $subject = new TerApiClient($requestFactory, $this->createNullCache(), new Typo3Version());
        $versions = $subject->getVersions('news');

        self::assertSame(['2.1.0', '2.0.0', '1.0.0'], array_map(static fn($v) => $v->number, $versions));
    }

    #[Test]
    public function cachedResponseSkipsTheHttpRequest(): void
    {
        $requestFactory = $this->createMock(RequestFactory::class);
        $requestFactory->expects($this->never())->method('request');
        $cache = $this->createMock(FrontendInterface::class);
        $cache->method('get')->willReturn(['key' => 'news', 'current_version' => ['number' => '9.9.9']]);

        $subject = new TerApiClient($requestFactory, $cache, new Typo3Version());
        $extension = $subject->getExtension('news');

        self::assertSame('news', $extension->key);
        self::assertSame('9.9.9', $extension->currentVersion->number);
    }

    /**
     * The fallback to the imported list hinges on these three: a remote that
     * cannot answer has to say so, or an outage looks like an empty catalogue.
     */
    #[Test]
    public function anUnreachableRemoteFails(): void
    {
        $requestFactory = $this->createMock(RequestFactory::class);
        $requestFactory->method('request')->willThrowException(new \RuntimeException('connection refused', 1789035178));

        $subject = new TerApiClient($requestFactory, $this->createNullCache(), new Typo3Version());

        $this->expectException(ApiRequestFailedException::class);
        $this->expectExceptionCode(1789035174);
        $subject->query('', 13);
    }

    #[Test]
    public function aRefusedRequestFails(): void
    {
        $requestFactory = $this->createMock(RequestFactory::class);
        $requestFactory->method('request')->willReturn($this->createResponse('{"error":"rate limit"}', 429));

        $subject = new TerApiClient($requestFactory, $this->createNullCache(), new Typo3Version());

        $this->expectException(ApiRequestFailedException::class);
        $this->expectExceptionCode(1789035175);
        $subject->query('', 13);
    }

    /**
     * "No such extension" is an answer, so it must not send the caller to the
     * imported list - the remote genuinely does not have it.
     */
    #[Test]
    public function anUnknownExtensionIsAnAnswerAndNotAFailure(): void
    {
        $requestFactory = $this->createMock(RequestFactory::class);
        $requestFactory->method('request')->willReturn($this->createResponse('{"status":404}', 404));

        $subject = new TerApiClient($requestFactory, $this->createNullCache(), new Typo3Version());

        self::assertNull($subject->getExtension('no_such_extension'));
    }

    /**
     * Guzzle throws on any 4xx by default, which would turn "no such extension"
     * into a failure. And the global HTTP timeout is unlimited by default, which
     * would let a slow remote hold the module hostage instead of falling back.
     */
    #[Test]
    public function requestsHandleErrorStatusesThemselvesAndHaveATimeout(): void
    {
        $options = [];
        $requestFactory = $this->createMock(RequestFactory::class);
        $requestFactory->method('request')->willReturnCallback(function (string $uri, string $method, array $requestOptions) use (&$options): ResponseInterface {
            $options = $requestOptions;
            return $this->createResponse('{"status":404}', 404);
        });

        $subject = new TerApiClient($requestFactory, $this->createNullCache(), new Typo3Version());
        $subject->getExtension('no_such_extension');

        self::assertFalse($options['http_errors']);
        self::assertGreaterThan(0, $options['timeout']);
        self::assertGreaterThan(0, $options['connect_timeout']);
    }

    /**
     * An offline installation would otherwise wait for the connect timeout on
     * every single question the module asks, on every view.
     */
    #[Test]
    public function aRemoteThatCouldNotAnswerIsNotAskedAgainForAWhile(): void
    {
        $requestFactory = $this->createMock(RequestFactory::class);
        $requestFactory->expects($this->once())
            ->method('request')
            ->willThrowException(new \RuntimeException('connection timed out', 1791628831));
        $subject = new TerApiClient(
            $requestFactory,
            $this->createMemoryCache(),
            new Typo3Version()
        );
        try {
            $subject->query('', 14);
        } catch (ApiRequestFailedException) {
        }

        $this->expectException(ApiRequestFailedException::class);
        $subject->getExtension('news');
    }
}

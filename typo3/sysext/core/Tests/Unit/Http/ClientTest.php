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

namespace TYPO3\CMS\Core\Tests\Unit\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException as GuzzleConnectException;
use GuzzleHttp\Exception\GuzzleException as GuzzleExceptionInterface;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use GuzzleHttp\Handler\MockHandler as GuzzleMockHandler;
use GuzzleHttp\HandlerStack as GuzzleHandlerStack;
use GuzzleHttp\Middleware as GuzzleMiddleware;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Client\RequestExceptionInterface;
use TYPO3\CMS\Core\Http\Request;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for \GuzzleHTTP\Client compatibility with TYPO3 PSR-7 Stack
 */
class ClientTest extends UnitTestCase
{
    /**
     * @test
     */
    public function implementsPsr18ClientInterface(): void
    {
        $client = new Client();
        self::assertInstanceOf(ClientInterface::class, $client);
    }

    /**
     * @test
     */
    public function sendRequest(): void
    {
        $transactions = [];
        // Create a guzzle mock and queue two responses.
        $mock = new GuzzleMockHandler([
            new GuzzleResponse(200, ['X-Foo' => 'Bar']),
            new GuzzleResponse(202, ['X-Foo' => 'Baz']),
        ]);
        $handler = GuzzleHandlerStack::create($mock);
        $handler->push(GuzzleMiddleware::history($transactions));
        $client = new Client(['handler' => $handler]);

        $request1 = new Request('https://example.com', 'GET', 'php://temp');
        $response1 = $client->sendRequest($request1);
        $request2 = new Request('https://example.com/action', 'POST', 'php://temp');
        $response2 = $client->sendRequest($request2);

        self::assertCount(2, $transactions);

        self::assertSame('GET', $transactions[0]['request']->getMethod());
        self::assertSame('https://example.com', $transactions[0]['request']->getUri()->__toString());
        self::assertSame(200, $response1->getStatusCode());
        self::assertSame('Bar', $response1->getHeaderLine('X-Foo'));

        self::assertSame('POST', $transactions[1]['request']->getMethod());
        self::assertSame('https://example.com/action', $transactions[1]['request']->getUri()->__toString());
        self::assertSame(202, $response2->getStatusCode());
        self::assertSame('Baz', $response2->getHeaderLine('X-Foo'));
    }

    /**
     * @test
     */
    public function requestException(): void
    {
        $request = new Request('https://example.com', 'GET', 'php://temp');
        $exception = $this->createMock(GuzzleRequestException::class);
        $exception->method('getRequest')->willReturn($request);
        $mock = new GuzzleMockHandler([$exception]);
        $handler = GuzzleHandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $this->expectException(RequestExceptionInterface::class);
        $client->sendRequest($request);
    }

    /**
     * @test
     */
    public function networkException(): void
    {
        $request = new Request('https://example.com', 'GET', 'php://temp');
        $exception = $this->createMock(GuzzleConnectException::class);
        $exception->method('getRequest')->willReturn($request);
        $mock = new GuzzleMockHandler([$exception]);
        $handler = GuzzleHandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $this->expectException(NetworkExceptionInterface::class);
        $client->sendRequest($request);
    }

    /**
     * @test
     */
    public function genericGuzzleException(): void
    {
        $request = new Request('https://example.com', 'GET', 'php://temp');
        $mock = new GuzzleMockHandler([
            new class () extends \RuntimeException implements GuzzleExceptionInterface {
            },
        ]);
        $handler = GuzzleHandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $this->expectException(ClientExceptionInterface::class);
        $client->sendRequest($request);
    }

    /**
     * @test
     */
    public function redirectIsNotHandledRecursivelyButReturnedAsResponse(): void
    {
        $transactions = [];
        $mock = new GuzzleMockHandler([
            new GuzzleResponse(303, ['Location' => 'https://example.com']),
        ]);
        $handler = GuzzleHandlerStack::create($mock);
        $handler->push(GuzzleMiddleware::history($transactions));
        $client = new Client(['handler' => $handler]);

        $request = new Request('https://example.com', 'GET', 'php://temp');
        $response = $client->sendRequest($request);

        self::assertCount(1, $transactions);
        self::assertSame(303, $response->getStatusCode());
        self::assertSame('https://example.com', $response->getHeaderLine('Location'));
    }

    /**
     * @test
     */
    public function errorResponsesDoNotThrowAnException(): void
    {
        $mock = new GuzzleMockHandler([
            new GuzzleResponse(404),
            new GuzzleResponse(500),
        ]);
        $handler = GuzzleHandlerStack::create($mock);
        $client = new Client(['handler' => $handler]);

        $request = new Request('https://example.com', 'GET', 'php://temp');
        $response1 = $client->sendRequest($request);
        $response2 = $client->sendRequest($request);

        self::assertSame(404, $response1->getStatusCode());
        self::assertSame(500, $response2->getStatusCode());
    }
}

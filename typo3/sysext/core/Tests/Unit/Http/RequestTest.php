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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\StreamInterface;
use TYPO3\CMS\Core\Http\Request;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class RequestTest extends UnitTestCase
{
    #[Test]
    public function getMethodIsGetByDefault(): void
    {
        self::assertEquals('GET', (new Request())->getMethod());
    }

    #[Test]
    public function getMethodMutatorReturnsCloneWithChangedMethod(): void
    {
        $request1 = new Request();
        $request2 = $request1->withMethod('GET');
        self::assertNotSame($request1, $request2);
        self::assertEquals('GET', $request2->getMethod());
    }

    #[Test]
    public function withUriReturnsNewInstanceWithNewUri(): void
    {
        $request1 = (new Request())->withUri(new Uri('https://example.com:10082/foo/bar?baz=bat'));
        $request2 = $request1->withUri(new Uri('/baz/bat?foo=bar'));
        self::assertNotSame($request2, $request1);
        self::assertEquals('/baz/bat?foo=bar', (string)$request2->getUri());
    }

    #[Test]
    public function constructorCanAcceptAllMessageParts(): void
    {
        $uri = new Uri('http://example.com/');
        $body = new Stream('php://memory');
        $headers = [
            'x-foo' => ['bar'],
        ];
        $request = new Request(
            $uri,
            'POST',
            $body,
            $headers
        );
        self::assertSame($uri, $request->getUri());
        self::assertEquals('POST', $request->getMethod());
        self::assertSame($body, $request->getBody());
        $testHeaders = $request->getHeaders();
        foreach ($headers as $key => $value) {
            self::assertArrayHasKey($key, $testHeaders);
            self::assertEquals($value, $testHeaders[$key]);
        }
    }

    #[Test]
    public function constructorRaisesExceptionForInvalidMethodByString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717275);
        new Request(null, 'BOGUS-METHOD');
    }

    public static function invalidRequestBodyDataProvider(): array
    {
        return [
            'true'     => [true],
            'false'    => [false],
            'int'      => [1],
            'float'    => [1.1],
            'array'    => [['BODY']],
            'stdClass' => [(object)['body' => 'BODY']],
        ];
    }

    #[DataProvider('invalidRequestBodyDataProvider')]
    #[Test]
    public function constructorRaisesExceptionForInvalidBody($body): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717271);
        new Request(null, 'GET', $body);
    }

    public static function validRequestBodyDataProvider(): array
    {
        return [
            'stringResourceIdentifier' => ['php://input'],
            'streamResource' => [fopen('php://memory', 'r')],
            'streamInterface' => [new Stream(fopen('php://memory', 'r'))],
            'null' => [null],
        ];
    }

    /**
     * @param resource|StreamInterface|string|null $body
     */
    #[DataProvider('validRequestBodyDataProvider')]
    #[Test]
    #[DoesNotPerformAssertions]
    public function constructorDoesNotRaiseExceptionForValidBody(mixed $body): void
    {
        new Request(null, 'GET', $body);
    }

    #[Test]
    public function constructorIgnoresInvalidHeaders(): void
    {
        $headers = [
            ['INVALID'],
            'x-invalid-null'   => null,
            'x-invalid-object' => (object)['INVALID'],
            'x-valid-true'   => true,
            'x-valid-false'  => false,
            'x-valid-int'    => 1,
            'x-valid-float'    => 1.5,
            'x-valid-string'   => 'VALID',
            'x-valid-array'    => ['VALID'],
        ];
        $expected = [
            'x-valid-true' => ['1'],
            'x-valid-false' => [''],
            'x-valid-int' => ['1'],
            'x-valid-float' => ['1.5'],
            'x-valid-string' => ['VALID'],
            'x-valid-array'  => ['VALID'],
        ];
        $request = new Request(null, 'GET', 'php://memory', $headers);
        self::assertEquals($expected, $request->getHeaders());
    }

    #[Test]
    public function getRequestTargetIsSlashWhenNoUriPresent(): void
    {
        $request = new Request();
        self::assertEquals('/', $request->getRequestTarget());
    }

    #[Test]
    public function getRequestTargetIsSlashWhenUriHasNoPathOrQuery(): void
    {
        $request = (new Request())->withUri(new Uri('http://example.com'));
        self::assertEquals('/', $request->getRequestTarget());
    }

    public static function requestsWithUriDataProvider(): array
    {
        return [
            'absolute-uri'            => [
                (new Request())
                    ->withUri(new Uri('https://api.example.com/user'))
                    ->withMethod('POST'),
                '/user',
            ],
            'absolute-uri-with-query' => [
                (new Request())
                    ->withUri(new Uri('https://api.example.com/user?foo=bar'))
                    ->withMethod('POST'),
                '/user?foo=bar',
            ],
            'relative-uri'            => [
                (new Request())
                    ->withUri(new Uri('/user'))
                    ->withMethod('GET'),
                '/user',
            ],
            'relative-uri-with-query' => [
                (new Request())
                    ->withUri(new Uri('/user?foo=bar'))
                    ->withMethod('GET'),
                '/user?foo=bar',
            ],
        ];
    }

    #[DataProvider('requestsWithUriDataProvider')]
    #[Test]
    public function getRequestTargetWhenUriIsPresent($request, $expected): void
    {
        self::assertEquals($expected, $request->getRequestTarget());
    }

    public static function validRequestTargetsDataProvider(): array
    {
        return [
            'asterisk-form'         => ['*'],
            'authority-form'        => ['api.example.com'],
            'absolute-form'         => ['https://api.example.com/users'],
            'absolute-form-query'   => ['https://api.example.com/users?foo=bar'],
            'origin-form-path-only' => ['/users'],
            'origin-form'           => ['/users?id=foo'],
        ];
    }

    #[DataProvider('validRequestTargetsDataProvider')]
    #[Test]
    public function getRequestTargetCanProvideARequestTarget($requestTarget): void
    {
        $request = (new Request())->withRequestTarget($requestTarget);
        self::assertEquals($requestTarget, $request->getRequestTarget());
    }

    #[Test]
    public function withRequestTargetCannotContainWhitespace(): void
    {
        $request = new Request();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717273);
        $request->withRequestTarget('foo bar baz');
    }

    #[Test]
    public function getRequestTargetDoesNotCacheBetweenInstances(): void
    {
        $request = (new Request())->withUri(new Uri('https://example.com/foo/bar'));
        $original = $request->getRequestTarget();
        $newRequest = $request->withUri(new Uri('http://mwop.net/bar/baz'));
        self::assertNotEquals($original, $newRequest->getRequestTarget());
    }

    #[Test]
    public function getRequestTargetIsResetWithNewUri(): void
    {
        $request1 = (new Request())->withUri(new Uri('https://example.com/foo/bar'));
        $target1 = $request1->getRequestTarget();
        $request2 = $request1->withUri(new Uri('http://mwop.net/bar/baz'));
        $target2 = $request2->getRequestTarget();
        self::assertNotEquals($target1, $target2);
    }

    #[Test]
    public function getHeadersContainsHostHeaderIfUriWithHostIsPresent(): void
    {
        $request = new Request('http://example.com');
        $headers = $request->getHeaders();
        self::assertArrayHasKey('host', $headers);
        self::assertContains('example.com', $headers['host']);
    }

    #[Test]
    public function getHeadersContainsNoHostHeaderIfNoUriPresent(): void
    {
        $request = new Request();
        $headers = $request->getHeaders();
        self::assertArrayNotHasKey('host', $headers);
    }

    #[Test]
    public function getHeadersContainsNoHostHeaderIfUriDoesNotContainHost(): void
    {
        $request = new Request(new Uri());
        $headers = $request->getHeaders();
        self::assertArrayNotHasKey('host', $headers);
    }

    #[Test]
    public function getHeaderWithHostReturnsUriHostWhenPresent(): void
    {
        $request = new Request('http://example.com');
        $header = $request->getHeader('host');
        self::assertEquals(['example.com'], $header);
    }

    #[Test]
    public function getHeaderWithHostReturnsEmptyArrayIfNoUriPresent(): void
    {
        $request = new Request();
        self::assertSame([], $request->getHeader('host'));
    }

    #[Test]
    public function getHeaderWithHostReturnsEmptyArrayIfUriDoesNotContainHost(): void
    {
        $request = new Request(new Uri());
        self::assertSame([], $request->getHeader('host'));
    }

    #[Test]
    public function getHeaderLineWithHostReturnsUriHostWhenPresent(): void
    {
        $request = new Request('http://example.com');
        $header = $request->getHeaderLine('host');
        self::assertStringContainsString('example.com', $header);
    }

    #[Test]
    public function getHeaderLineWithHostReturnsEmptyStringIfNoUriPresent(): void
    {
        $request = new Request();
        self::assertSame('', $request->getHeaderLine('host'));
    }

    #[Test]
    public function getHeaderLineWithHostReturnsEmptyStringIfUriDoesNotContainHost(): void
    {
        $request = new Request(new Uri());
        self::assertSame('', $request->getHeaderLine('host'));
    }

    #[Test]
    public function getHeaderLineWithHostTakesPrecedenceOverModifiedUri(): void
    {
        $request = (new Request())->withAddedHeader('Host', 'example.com');
        $uri = (new Uri())->withHost('www.example.com');
        $new = $request->withUri($uri, true);
        self::assertEquals('example.com', $new->getHeaderLine('Host'));
    }

    #[Test]
    public function getHeaderLineWithHostTakesPrecedenceOverEmptyUri(): void
    {
        $request = (new Request())->withAddedHeader('Host', 'example.com');
        $uri = new Uri();
        $new = $request->withUri($uri);
        self::assertEquals('example.com', $new->getHeaderLine('Host'));
    }

    #[Test]
    public function getHeaderLineWithHostDoesNotTakePrecedenceOverHostWithPortFromUri(): void
    {
        $request = (new Request())->withAddedHeader('Host', 'example.com');
        $uri = (new Uri())
            ->withHost('www.example.com')
            ->withPort(10081);
        $new = $request->withUri($uri);
        self::assertEquals('www.example.com:10081', $new->getHeaderLine('Host'));
    }

    public static function headersWithUpperAndLowerCaseValuesDataProvider(): array
    {
        // 'name' => [$headerName, $headerValue, $expectedValue]
        return [
            'Foo'             => ['Foo', 'bar', 'bar'],
            'foo'             => ['foo', 'bar', 'bar'],
            'Foo-with-array'  => ['Foo', ['bar'], 'bar'],
            'foo-with-array'  => ['foo', ['bar'], 'bar'],
        ];
    }

    #[DataProvider('headersWithUpperAndLowerCaseValuesDataProvider')]
    #[Test]
    public function headerCanBeRetrieved($header, $value, $expected): void
    {
        $request = new Request(null, 'GET', 'php://memory', [$header => $value]);
        self::assertEquals([$expected], $request->getHeader(strtolower($header)));
        self::assertEquals([$expected], $request->getHeader(strtoupper($header)));
    }

    public static function headersWithInjectionVectorsDataProvider(): array
    {
        return [
            'name-with-cr'           => ["X-Foo\r-Bar", 'value'],
            'name-with-lf'           => ["X-Foo\n-Bar", 'value'],
            'name-with-crlf'         => ["X-Foo\r\n-Bar", 'value'],
            'name-with-2crlf'        => ["X-Foo\r\n\r\n-Bar", 'value'],
            'value-with-cr'          => ['X-Foo-Bar', "value\rinjection"],
            'value-with-lf'          => ['X-Foo-Bar', "value\ninjection"],
            'value-with-crlf'        => ['X-Foo-Bar', "value\r\ninjection"],
            'value-with-2crlf'       => ['X-Foo-Bar', "value\r\n\r\ninjection"],
            'array-value-with-cr'    => ['X-Foo-Bar', ["value\rinjection"]],
            'array-value-with-lf'    => ['X-Foo-Bar', ["value\ninjection"]],
            'array-value-with-crlf'  => ['X-Foo-Bar', ["value\r\ninjection"]],
            'array-value-with-2crlf' => ['X-Foo-Bar', ["value\r\n\r\ninjection"]],
        ];
    }

    #[DataProvider('headersWithInjectionVectorsDataProvider')]
    #[Test]
    public function constructorRaisesExceptionForHeadersWithCRLFVectors($name, $value): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Request(null, 'GET', 'php://memory', [$name => $value]);
    }

    #[Test]
    public function supportedRequestMethodsWork(): void
    {
        $request = new Request('some-uri', 'PURGE');
        self::assertEquals('PURGE', $request->getMethod());
    }

    #[Test]
    public function nonSupportedRequestMethodsRaisesException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Request('some-uri', 'UNSUPPORTED');
    }
}

<?php
namespace TYPO3\CMS\Core\Tests\Unit\Http;

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

use TYPO3\CMS\Core\Http\Request;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Http\Uri;

/**
 * Testcase for \TYPO3\CMS\Core\Http\Request
 *
 * Adapted from https://github.com/phly/http/
 */
class RequestTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var Request
     */
    protected $request;

    protected function setUp()
    {
        $this->request = new Request();
    }

    /**
     * @test
     */
    public function getMethodIsGetByDefault()
    {
        $this->assertEquals('GET', $this->request->getMethod());
    }

    /**
     * @test
     */
    public function getMethodMutatorReturnsCloneWithChangedMethod()
    {
        $request = $this->request->withMethod('GET');
        $this->assertNotSame($this->request, $request);
        $this->assertEquals('GET', $request->getMethod());
    }

    /**
     * @test
     */
    public function getUriIsNullByDefault()
    {
        $this->assertNull($this->request->getUri());
    }

    /**
     * @test
     */
    public function constructorRaisesExceptionForInvalidStream()
    {
        $this->setExpectedException('InvalidArgumentException');
        new Request(['TOTALLY INVALID']);
    }

    /**
     * @test
     */
    public function withUriReturnsNewInstanceWithNewUri()
    {
        $request = $this->request->withUri(new Uri('https://example.com:10082/foo/bar?baz=bat'));
        $this->assertNotSame($this->request, $request);
        $request2 = $request->withUri(new Uri('/baz/bat?foo=bar'));
        $this->assertNotSame($this->request, $request2);
        $this->assertNotSame($request, $request2);
        $this->assertEquals('/baz/bat?foo=bar', (string) $request2->getUri());
    }

    /**
     * @test
     */
    public function constructorCanAcceptAllMessageParts()
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

        $this->assertSame($uri, $request->getUri());
        $this->assertEquals('POST', $request->getMethod());
        $this->assertSame($body, $request->getBody());
        $testHeaders = $request->getHeaders();
        foreach ($headers as $key => $value) {
            $this->assertArrayHasKey($key, $testHeaders);
            $this->assertEquals($value, $testHeaders[$key]);
        }
    }

    /**
     * @return array
     */
    public function invalidRequestUriDataProvider()
    {
        return [
            'true'     => [true],
            'false'    => [false],
            'int'      => [1],
            'float'    => [1.1],
            'array'    => [['http://example.com']],
            'stdClass' => [(object) ['href' => 'http://example.com']],
        ];
    }

    /**
     * @dataProvider invalidRequestUriDataProvider
     * @test
     */
    public function constructorRaisesExceptionForInvalidUri($uri)
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid URI');
        new Request($uri);
    }

    /**
     * @return array
     */
    public function invalidRequestMethodDataProvider()
    {
        return [
            'true'       => [true],
            'false'      => [false],
            'int'        => [1],
            'float'      => [1.1],
            'bad-string' => ['BOGUS-METHOD'],
            'array'      => [['POST']],
            'stdClass'   => [(object) ['method' => 'POST']],
        ];
    }

    /**
     * @dataProvider invalidRequestMethodDataProvider
     * @test
     */
    public function constructorRaisesExceptionForInvalidMethod($method)
    {
        $this->setExpectedException('InvalidArgumentException', 'Unsupported HTTP method');
        new Request(null, $method);
    }

    /**
     * @return array
     */
    public function invalidRequestBodyDataProvider()
    {
        return [
            'true'     => [true],
            'false'    => [false],
            'int'      => [1],
            'float'    => [1.1],
            'array'    => [['BODY']],
            'stdClass' => [(object) ['body' => 'BODY']],
        ];
    }

    /**
     * @dataProvider invalidRequestBodyDataProvider
     * @test
     */
    public function constructorRaisesExceptionForInvalidBody($body)
    {
        $this->setExpectedException('InvalidArgumentException', 'stream');
        new Request(null, null, $body);
    }

    /**
     * @test
     */
    public function constructorIgnoresInvalidHeaders()
    {
        $headers = [
            ['INVALID'],
            'x-invalid-null'   => null,
            'x-invalid-true'   => true,
            'x-invalid-false'  => false,
            'x-invalid-int'    => 1,
            'x-invalid-object' => (object) ['INVALID'],
            'x-valid-string'   => 'VALID',
            'x-valid-array'    => ['VALID'],
        ];
        $expected = [
            'x-valid-string' => ['VALID'],
            'x-valid-array'  => ['VALID'],
        ];
        $request = new Request(null, null, 'php://memory', $headers);
        $this->assertEquals($expected, $request->getHeaders());
    }

    /**
     * @test
     */
    public function getRequestTargetIsSlashWhenNoUriPresent()
    {
        $request = new Request();
        $this->assertEquals('/', $request->getRequestTarget());
    }

    /**
     * @test
     */
    public function getRequestTargetIsSlashWhenUriHasNoPathOrQuery()
    {
        $request = (new Request())
            ->withUri(new Uri('http://example.com'));
        $this->assertEquals('/', $request->getRequestTarget());
    }

    /**
     * @return array
     */
    public function requestsWithUriDataProvider()
    {
        return [
            'absolute-uri'            => [
                (new Request())
                    ->withUri(new Uri('https://api.example.com/user'))
                    ->withMethod('POST'),
                '/user'
            ],
            'absolute-uri-with-query' => [
                (new Request())
                    ->withUri(new Uri('https://api.example.com/user?foo=bar'))
                    ->withMethod('POST'),
                '/user?foo=bar'
            ],
            'relative-uri'            => [
                (new Request())
                    ->withUri(new Uri('/user'))
                    ->withMethod('GET'),
                '/user'
            ],
            'relative-uri-with-query' => [
                (new Request())
                    ->withUri(new Uri('/user?foo=bar'))
                    ->withMethod('GET'),
                '/user?foo=bar'
            ],
        ];
    }

    /**
     * @dataProvider requestsWithUriDataProvider
     * @test
     */
    public function getRequestTargetWhenUriIsPresent($request, $expected)
    {
        $this->assertEquals($expected, $request->getRequestTarget());
    }

    /**
     * @return array
     */
    public function validRequestTargetsDataProvider()
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

    /**
     * @dataProvider validRequestTargetsDataProvider
     * @test
     */
    public function getRequestTargetCanProvideARequestTarget($requestTarget)
    {
        $request = (new Request())->withRequestTarget($requestTarget);
        $this->assertEquals($requestTarget, $request->getRequestTarget());
    }

    /**
     * @test
     */
    public function withRequestTargetCannotContainWhitespace()
    {
        $request = new Request();
        $this->setExpectedException('InvalidArgumentException', 'Invalid request target');
        $request->withRequestTarget('foo bar baz');
    }

    /**
     * @test
     */
    public function getRequestTargetDoesNotCacheBetweenInstances()
    {
        $request = (new Request())->withUri(new Uri('https://example.com/foo/bar'));
        $original = $request->getRequestTarget();
        $newRequest = $request->withUri(new Uri('http://mwop.net/bar/baz'));
        $this->assertNotEquals($original, $newRequest->getRequestTarget());
    }

    /**
     * @test
     */
    public function getRequestTargetIsResetWithNewUri()
    {
        $request = (new Request())->withUri(new Uri('https://example.com/foo/bar'));
        $original = $request->getRequestTarget();
        $newRequest = $request->withUri(new Uri('http://mwop.net/bar/baz'));
    }

    /**
     * @test
     */
    public function getHeadersContainsHostHeaderIfUriWithHostIsPresent()
    {
        $request = new Request('http://example.com');
        $headers = $request->getHeaders();
        $this->assertArrayHasKey('host', $headers);
        $this->assertContains('example.com', $headers['host']);
    }

    /**
     * @test
     */
    public function getHeadersContainsNoHostHeaderIfNoUriPresent()
    {
        $request = new Request();
        $headers = $request->getHeaders();
        $this->assertArrayNotHasKey('host', $headers);
    }

    /**
     * @test
     */
    public function getHeadersContainsNoHostHeaderIfUriDoesNotContainHost()
    {
        $request = new Request(new Uri());
        $headers = $request->getHeaders();
        $this->assertArrayNotHasKey('host', $headers);
    }

    /**
     * @test
     */
    public function getHeaderWithHostReturnsUriHostWhenPresent()
    {
        $request = new Request('http://example.com');
        $header = $request->getHeader('host');
        $this->assertEquals(['example.com'], $header);
    }

    /**
     * @test
     */
    public function getHeaderWithHostReturnsEmptyArrayIfNoUriPresent()
    {
        $request = new Request();
        $this->assertSame([], $request->getHeader('host'));
    }

    /**
     * @test
     */
    public function getHeaderWithHostReturnsEmptyArrayIfUriDoesNotContainHost()
    {
        $request = new Request(new Uri());
        $this->assertSame([], $request->getHeader('host'));
    }

    /**
     * @test
     */
    public function getHeaderLineWithHostReturnsUriHostWhenPresent()
    {
        $request = new Request('http://example.com');
        $header = $request->getHeaderLine('host');
        $this->assertContains('example.com', $header);
    }

    /**
     * @test
     */
    public function getHeaderLineWithHostReturnsEmptyStringIfNoUriPresent()
    {
        $request = new Request();
        $this->assertSame('', $request->getHeaderLine('host'));
    }

    /**
     * @test
     */
    public function getHeaderLineWithHostReturnsEmptyStringIfUriDoesNotContainHost()
    {
        $request = new Request(new Uri());
        $this->assertSame('', $request->getHeaderLine('host'));
    }

    /**
     * @test
     */
    public function getHeaderLineWithHostTakesPrecedenceOverModifiedUri()
    {
        $request = (new Request())
            ->withAddedHeader('Host', 'example.com');

        $uri = (new Uri())->withHost('www.example.com');
        $new = $request->withUri($uri, true);

        $this->assertEquals('example.com', $new->getHeaderLine('Host'));
    }

    /**
     * @test
     */
    public function getHeaderLineWithHostTakesPrecedenceOverEmptyUri()
    {
        $request = (new Request())
            ->withAddedHeader('Host', 'example.com');

        $uri = new Uri();
        $new = $request->withUri($uri);

        $this->assertEquals('example.com', $new->getHeaderLine('Host'));
    }

    /**
     * @test
     */
    public function getHeaderLineWithHostDoesNotTakePrecedenceOverHostWithPortFromUri()
    {
        $request = (new Request())
            ->withAddedHeader('Host', 'example.com');

        $uri = (new Uri())
            ->withHost('www.example.com')
            ->withPort(10081);
        $new = $request->withUri($uri);

        $this->assertEquals('www.example.com:10081', $new->getHeaderLine('Host'));
    }

    /**
     * @return array
     */
    public function headersWithUpperAndLowerCaseValuesDataProvider()
    {
        // 'name' => [$headerName, $headerValue, $expectedValue]
        return [
            'Foo'             => ['Foo', 'bar', 'bar'],
            'foo'             => ['foo', 'bar', 'bar'],
            'Foo-with-array'  => ['Foo', ['bar'], 'bar'],
            'foo-with-array'  => ['foo', ['bar'], 'bar'],
        ];
    }

    /**
     * @test
     * @dataProvider headersWithUpperAndLowerCaseValuesDataProvider
     */
    public function headerCanBeRetrieved($header, $value, $expected)
    {
        $request = new Request(null, null, 'php://memory', [$header => $value]);
        $this->assertEquals([$expected], $request->getHeader(strtolower($header)));
        $this->assertEquals([$expected], $request->getHeader(strtoupper($header)));
    }

    /**
     * @return array
     */
    public function headersWithInjectionVectorsDataProvider()
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

    /**
     * @test
     * @dataProvider headersWithInjectionVectorsDataProvider
     */
    public function constructorRaisesExceptionForHeadersWithCRLFVectors($name, $value)
    {
        $this->setExpectedException('InvalidArgumentException');
        $request = new Request(null, null, 'php://memory', [$name => $value]);
    }
}

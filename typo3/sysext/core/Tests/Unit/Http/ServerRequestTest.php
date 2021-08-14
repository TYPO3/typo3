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

use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for \TYPO3\CMS\Core\Http\ServerRequest
 *
 * Adapted from https://github.com/phly/http/
 */
class ServerRequestTest extends UnitTestCase
{
    protected ?ServerRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new ServerRequest();
    }

    /**
     * @test
     */
    public function getServerParamsAreEmptyByDefault(): void
    {
        self::assertEmpty($this->request->getServerParams());
    }

    /**
     * @test
     */
    public function getQueryParamsAreEmptyByDefault(): void
    {
        self::assertEmpty($this->request->getQueryParams());
    }

    /**
     * @test
     */
    public function withQueryParamsMutatorReturnsCloneWithChanges(): void
    {
        $value = ['foo' => 'bar'];
        $request = $this->request->withQueryParams($value);
        self::assertNotSame($this->request, $request);
        self::assertEquals($value, $request->getQueryParams());
    }

    /**
     * @test
     */
    public function getCookieParamsAreEmptyByDefault(): void
    {
        self::assertEmpty($this->request->getCookieParams());
    }

    /**
     * @test
     */
    public function withCookieParamsMutatorReturnsCloneWithChanges(): void
    {
        $value = ['foo' => 'bar'];
        $request = $this->request->withCookieParams($value);
        self::assertNotSame($this->request, $request);
        self::assertEquals($value, $request->getCookieParams());
    }

    /**
     * @test
     */
    public function getUploadedFilesAreEmptyByDefault(): void
    {
        self::assertEmpty($this->request->getUploadedFiles());
    }

    /**
     * @test
     */
    public function getParsedBodyIsEmptyByDefault(): void
    {
        self::assertEmpty($this->request->getParsedBody());
    }

    /**
     * @test
     */
    public function withParsedBodyMutatorReturnsCloneWithChanges(): void
    {
        $value = ['foo' => 'bar'];
        $request = $this->request->withParsedBody($value);
        self::assertNotSame($this->request, $request);
        self::assertEquals($value, $request->getParsedBody());
    }

    /**
     * @test
     */
    public function getAttributesAreEmptyByDefault(): void
    {
        self::assertEmpty($this->request->getAttributes());
    }

    /**
     * @test
     */
    public function withAttributeMutatorReturnsCloneWithChanges(): ServerRequest
    {
        $request = $this->request->withAttribute('foo', 'bar');
        self::assertNotSame($this->request, $request);
        self::assertEquals('bar', $request->getAttribute('foo'));

        return $request;
    }

    /**
     * @test
     */
    public function withoutAttributeReturnsCloneWithoutAttribute(): void
    {
        $request = $this->request;
        $new = $request->withoutAttribute('foo');
        self::assertNotSame($request, $new);
        self::assertNull($new->getAttribute('foo', null));
    }

    /**
     * @test
     */
    public function constructorUsesProvidedArguments(): void
    {
        $server = [
            'foo' => 'bar',
            'baz' => 'bat',
        ];

        $server['server'] = true;

        $files = [
            'files' => new UploadedFile('php://temp', 0, 0),
        ];

        $uri = new Uri('http://example.com');
        $method = 'POST';
        $headers = [
            'host' => ['example.com'],
        ];

        $request = new ServerRequest(
            $uri,
            $method,
            'php://memory',
            $headers,
            $server,
            $files
        );

        self::assertEquals($server, $request->getServerParams());
        self::assertEquals($files, $request->getUploadedFiles());

        self::assertSame($uri, $request->getUri());
        self::assertEquals($method, $request->getMethod());
        self::assertEquals($headers, $request->getHeaders());

        $body = $request->getBody();
        $r = new \ReflectionProperty($body, 'stream');
        $r->setAccessible(true);
        $stream = $r->getValue($body);
        self::assertEquals('php://memory', $stream);
    }
}

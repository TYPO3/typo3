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

use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for \TYPO3\CMS\Core\Http\Response
 *
 * Adapted from https://github.com/phly/http/
 */
class ResponseTest extends UnitTestCase
{
    protected ?Response $response;

    protected function setUp(): void
    {
        parent::setUp();
        $this->response = new Response();
    }

    /**
     * @test
     */
    public function statusCodeIs200ByDefault(): void
    {
        self::assertEquals(200, $this->response->getStatusCode());
    }

    /**
     * @test
     */
    public function statusCodeMutatorReturnsCloneWithChanges(): void
    {
        $response = $this->response->withStatus(400);
        self::assertNotSame($this->response, $response);
        self::assertEquals(400, $response->getStatusCode());
    }

    public function invalidStatusCodesDataProvider(): array
    {
        return [
            'too-low'  => [99],
            'too-high' => [600],
            'null'     => [null],
            'bool'     => [true],
            'string'   => ['foo'],
            'array'    => [[200]],
            'object'   => [(object)[200]],
        ];
    }

    /**
     * @test
     * @dataProvider invalidStatusCodesDataProvider
     */
    public function cannotSetInvalidStatusCode($code): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->response->withStatus($code);
    }

    /**
     * @test
     */
    public function reasonPhraseDefaultsToStandards(): void
    {
        $response = $this->response->withStatus(422);
        self::assertEquals('Unprocessable Entity', $response->getReasonPhrase());
    }

    /**
     * @test
     */
    public function canSetCustomReasonPhrase(): void
    {
        $response = $this->response->withStatus(422, 'Foo Bar!');
        self::assertEquals('Foo Bar!', $response->getReasonPhrase());
    }

    /**
     * @test
     */
    public function constructorRaisesExceptionForInvalidStream(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Response(['TOTALLY INVALID']);
    }

    /**
     * @test
     */
    public function constructorCanAcceptAllMessageParts(): void
    {
        $body = new Stream('php://memory');
        $status = 302;
        $headers = [
            'location' => ['http://example.com/'],
        ];

        $response = new Response($body, $status, $headers);
        self::assertSame($body, $response->getBody());
        self::assertEquals(302, $response->getStatusCode());
        self::assertEquals($headers, $response->getHeaders());
    }

    public function invalidStatusDataProvider(): array
    {
        return [
            'true'       => [true],
            'false'      => [false],
            'float'      => [100.1],
            'bad-string' => ['Two hundred'],
            'array'      => [[200]],
            'object'     => [(object)['statusCode' => 200]],
            'too-small'  => [1],
            'too-big'    => [600],
        ];
    }

    /**
     * @test
     * @dataProvider invalidStatusDataProvider
     */
    public function constructorRaisesExceptionForInvalidStatus($code): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717278);
        new Response('php://memory', $code);
    }

    public function invalidResponseBodyDataProvider(): array
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

    /**
     * @test
     * @dataProvider invalidResponseBodyDataProvider
     */
    public function constructorRaisesExceptionForInvalidBody($body): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717277);
        new Response($body);
    }

    /**
     * @test
     */
    public function constructorIgnoresInvalidHeaders(): void
    {
        $headers = [
            ['INVALID'],
            'x-invalid-null'   => null,
            'x-invalid-true'   => true,
            'x-invalid-false'  => false,
            'x-invalid-int'    => 1,
            'x-invalid-object' => (object)['INVALID'],
            'x-valid-string'   => 'VALID',
            'x-valid-array'    => ['VALID'],
        ];
        $expected = [
            'x-valid-string' => ['VALID'],
            'x-valid-array'  => ['VALID'],
        ];
        $response = new Response('php://memory', 200, $headers);
        self::assertEquals($expected, $response->getHeaders());
    }

    public function headersWithInjectionVectorsDataProvider(): array
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
    public function constructorRaisesExceptionForHeadersWithCRLFVectors($name, $value): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Response('php://memory', 200, [$name => $value]);
    }

    /**
     * @test
     */
    public function getHeaderReturnsHeaderSetByConstructorArgument(): void
    {
        $subject = new Response('php://memory', 200, ['location' => 'foo']);
        $expected = [
            0 => 'foo',
        ];
        self::assertSame($expected, $subject->getHeader('location'));
    }
}

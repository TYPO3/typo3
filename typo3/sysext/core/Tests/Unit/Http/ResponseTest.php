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
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ResponseTest extends UnitTestCase
{
    protected ?Response $response;

    protected function setUp(): void
    {
        parent::setUp();
        $this->response = new Response();
    }

    #[Test]
    public function statusCodeIs200ByDefault(): void
    {
        self::assertEquals(200, $this->response->getStatusCode());
    }

    #[Test]
    public function statusCodeMutatorReturnsCloneWithChanges(): void
    {
        $response = $this->response->withStatus(400);
        self::assertNotSame($this->response, $response);
        self::assertEquals(400, $response->getStatusCode());
    }

    public static function invalidStatusCodesDataProvider(): array
    {
        return [
            'too-low'  => [99],
            'too-high' => [600],
        ];
    }

    #[DataProvider('invalidStatusCodesDataProvider')]
    #[Test]
    public function cannotSetInvalidStatusCode($code): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->response->withStatus($code);
    }

    #[Test]
    public function reasonPhraseDefaultsToStandards(): void
    {
        $response = $this->response->withStatus(422);
        self::assertEquals('Unprocessable Entity', $response->getReasonPhrase());
    }

    #[Test]
    public function canSetCustomReasonPhrase(): void
    {
        $response = $this->response->withStatus(422, 'Foo Bar!');
        self::assertEquals('Foo Bar!', $response->getReasonPhrase());
    }

    #[Test]
    public function constructorRaisesExceptionForInvalidStream(): void
    {
        $this->expectException(\TypeError::class);
        new Response(['TOTALLY INVALID']);
    }

    #[Test]
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

    public static function invalidStatusDataProvider(): array
    {
        return [
            'too-small'  => [1],
            'too-big'    => [600],
        ];
    }

    #[DataProvider('invalidStatusDataProvider')]
    #[Test]
    public function constructorRaisesExceptionForInvalidStatus($code): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717278);
        new Response('php://memory', $code);
    }

    public static function invalidResponseBodyDataProvider(): array
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

    #[DataProvider('invalidResponseBodyDataProvider')]
    #[Test]
    public function constructorRaisesExceptionForInvalidBody($body): void
    {
        $this->expectException(\TypeError::class);
        new Response($body);
    }

    #[Test]
    public function constructorIgnoresInvalidHeaders(): void
    {
        $headers = [
            ['INVALID'],
            'x-invalid-null'   => null,
            'x-valid-true'   => true,
            'x-valid-false'  => false,
            'x-valid-int'    => 1,
            'x-valid-float'    => 1.5,
            'x-invalid-object' => (object)['INVALID'],
            'x-valid-string'   => 'VALID',
            'x-valid-array'    => ['VALID'],
        ];
        $expected = [
            'x-valid-true'   => ['1'],
            'x-valid-false'  => [''],
            'x-valid-int'    => ['1'],
            'x-valid-float'    => ['1.5'],
            'x-valid-string' => ['VALID'],
            'x-valid-array'  => ['VALID'],
        ];
        $response = new Response('php://memory', 200, $headers);
        self::assertEquals($expected, $response->getHeaders());
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
        new Response('php://memory', 200, [$name => $value]);
    }

    #[Test]
    public function getHeaderReturnsHeaderSetByConstructorArgument(): void
    {
        $subject = new Response('php://memory', 200, ['location' => 'foo']);
        $expected = [
            0 => 'foo',
        ];
        self::assertSame($expected, $subject->getHeader('location'));
    }
}

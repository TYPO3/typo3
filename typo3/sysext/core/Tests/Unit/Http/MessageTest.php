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

use TYPO3\CMS\Core\Http\Message;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for \TYPO3\CMS\Core\Http\Message
 *
 * Adapted from https://github.com/phly/http/
 */
class MessageTest extends UnitTestCase
{
    protected ?Stream $stream;
    protected ?Message $message;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stream = new Stream('php://memory', 'wb+');
        $this->message = (new Message())->withBody($this->stream);
    }

    /**
     * @test
     */
    public function protocolHasAcceptableDefault(): void
    {
        self::assertEquals('1.1', $this->message->getProtocolVersion());
    }

    /**
     * @test
     */
    public function protocolMutatorReturnsCloneWithChanges(): void
    {
        $message = $this->message->withProtocolVersion('1.0');
        self::assertNotSame($this->message, $message);
        self::assertEquals('1.0', $message->getProtocolVersion());
    }

    /**
     * @test
     */
    public function usesStreamProvidedInConstructorAsBody(): void
    {
        self::assertSame($this->stream, $this->message->getBody());
    }

    /**
     * @test
     */
    public function bodyMutatorReturnsCloneWithChanges(): void
    {
        $stream = new Stream('php://memory', 'wb+');
        $message = $this->message->withBody($stream);
        self::assertNotSame($this->message, $message);
        self::assertSame($stream, $message->getBody());
    }

    /**
     * @test
     */
    public function getHeaderReturnsHeaderValueAsArray(): void
    {
        $message = $this->message->withHeader('X-Foo', ['Foo', 'Bar']);
        self::assertNotSame($this->message, $message);
        self::assertEquals(['Foo', 'Bar'], $message->getHeader('X-Foo'));
    }

    /**
     * @test
     */
    public function getHeaderLineReturnsHeaderValueAsCommaConcatenatedString(): void
    {
        $message = $this->message->withHeader('X-Foo', ['Foo', 'Bar']);
        self::assertNotSame($this->message, $message);
        self::assertEquals('Foo,Bar', $message->getHeaderLine('X-Foo'));
    }

    /**
     * @test
     */
    public function getHeadersKeepsHeaderCaseSensitivity(): void
    {
        $message = $this->message->withHeader('X-Foo', ['Foo', 'Bar']);
        self::assertNotSame($this->message, $message);
        self::assertEquals(['X-Foo' => ['Foo', 'Bar']], $message->getHeaders());
    }

    /**
     * @test
     */
    public function getHeadersReturnsCaseWithWhichHeaderFirstRegistered(): void
    {
        $message = $this->message
            ->withHeader('X-Foo', 'Foo')
            ->withAddedHeader('x-foo', 'Bar');
        self::assertNotSame($this->message, $message);
        self::assertEquals(['X-Foo' => ['Foo', 'Bar']], $message->getHeaders());
    }

    /**
     * @test
     */
    public function hasHeaderReturnsFalseIfHeaderIsNotPresent(): void
    {
        self::assertFalse($this->message->hasHeader('X-Foo'));
    }

    /**
     * @test
     */
    public function hasHeaderReturnsTrueIfHeaderIsPresent(): void
    {
        $message = $this->message->withHeader('X-Foo', 'Foo');
        self::assertNotSame($this->message, $message);
        self::assertTrue($message->hasHeader('X-Foo'));
    }

    /**
     * @test
     */
    public function addHeaderAppendsToExistingHeader(): void
    {
        $message = $this->message->withHeader('X-Foo', 'Foo');
        self::assertNotSame($this->message, $message);
        $message2 = $message->withAddedHeader('X-Foo', 'Bar');
        self::assertNotSame($message, $message2);
        self::assertEquals('Foo,Bar', $message2->getHeaderLine('X-Foo'));
    }

    /**
     * @test
     */
    public function canRemoveHeaders(): void
    {
        $message = $this->message->withHeader('X-Foo', 'Foo');
        self::assertNotSame($this->message, $message);
        self::assertTrue($message->hasHeader('x-foo'));
        $message2 = $message->withoutHeader('x-foo');
        self::assertNotSame($this->message, $message2);
        self::assertNotSame($message, $message2);
        self::assertFalse($message2->hasHeader('X-Foo'));
    }

    /**
     * @test
     */
    public function headerRemovalIsCaseInsensitive(): void
    {
        $message = $this->message
            ->withHeader('X-Foo', 'Foo')
            ->withAddedHeader('x-foo', 'Bar')
            ->withAddedHeader('X-FOO', 'Baz');
        self::assertNotSame($this->message, $message);
        self::assertTrue($message->hasHeader('x-foo'));
        $message2 = $message->withoutHeader('x-foo');
        self::assertNotSame($this->message, $message2);
        self::assertNotSame($message, $message2);
        self::assertFalse($message2->hasHeader('X-Foo'));
        $headers = $message2->getHeaders();
        self::assertCount(0, $headers);
    }

    /**
     * @return array
     */
    public function invalidGeneralHeaderValuesDataProvider(): array
    {
        return [
            'null'   => [null],
            'true'   => [true],
            'false'  => [false],
            'int'    => [1],
            'float'  => [1.1],
            'array'  => [['foo' => ['bar']]],
            'object' => [(object)['foo' => 'bar']],
        ];
    }

    /**
     * @test
     * @dataProvider invalidGeneralHeaderValuesDataProvider
     */
    public function withHeaderRaisesExceptionForInvalidNestedHeaderValue($value): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717266);
        $this->message->withHeader('X-Foo', [$value]);
    }

    /**
     * @return array
     */
    public function invalidHeaderValuesDataProvider(): array
    {
        return [
            'null'   => [null],
            'true'   => [true],
            'false'  => [false],
            'int'    => [1],
            'float'  => [1.1],
            'object' => [(object)['foo' => 'bar']],
        ];
    }

    /**
     * @dataProvider invalidHeaderValuesDataProvider
     */
    public function withHeaderRaisesExceptionForInvalidValueType($value): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717266);
        $this->message->withHeader('X-Foo', $value);
    }

    /**
     * @dataProvider invalidHeaderValuesDataProvider
     */
    public function withAddedHeaderRaisesExceptionForNonStringNonArrayValue($value): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717267);
        $this->message->withAddedHeader('X-Foo', $value);
    }

    /**
     * @test
     */
    public function withoutHeaderDoesNothingIfHeaderDoesNotExist(): void
    {
        self::assertFalse($this->message->hasHeader('X-Foo'));
        $message = $this->message->withoutHeader('X-Foo');
        self::assertNotSame($this->message, $message);
        self::assertFalse($message->hasHeader('X-Foo'));
    }

    /**
     * @test
     */
    public function getHeaderReturnsAnEmptyArrayWhenHeaderDoesNotExist(): void
    {
        self::assertSame([], $this->message->getHeader('X-Foo-Bar'));
    }

    /**
     * @test
     */
    public function getHeaderLineReturnsEmptyStringWhenHeaderDoesNotExist(): void
    {
        self::assertSame('', $this->message->getHeaderLine('X-Foo-Bar'));
    }

    /**
     * @return array
     */
    public function headersWithInjectionVectorsDataProvider(): array
    {
        return [
            'name-with-cr'            => ["X-Foo\r-Bar", 'value'],
            'name-with-lf'            => ["X-Foo\n-Bar", 'value'],
            'name-with-crlf'          => ["X-Foo\r\n-Bar", 'value'],
            'name-with-2crlf'         => ["X-Foo\r\n\r\n-Bar", 'value'],
            'value-with-cr'           => ['X-Foo-Bar', "value\rinjection"],
            'value-with-lf'           => ['X-Foo-Bar', "value\ninjection"],
            'value-with-crlf'         => ['X-Foo-Bar', "value\r\ninjection"],
            'value-with-2crlf'        => ['X-Foo-Bar', "value\r\n\r\ninjection"],
            'array-value-with-cr'     => ['X-Foo-Bar', ["value\rinjection"]],
            'array-value-with-lf'     => ['X-Foo-Bar', ["value\ninjection"]],
            'array-value-with-crlf'   => ['X-Foo-Bar', ["value\r\ninjection"]],
            'array-value-with-2crlf'  => ['X-Foo-Bar', ["value\r\n\r\ninjection"]],
            'multi-line-header-space' => ['X-Foo-Bar', "value\r\n injection"],
            'multi-line-header-tab'   => ['X-Foo-Bar', "value\r\n\tinjection"],
            'value-with-EOT'          => ['X-Foo-Bar', "value\x03injection"],
            'value-with-vert-tab'     => ['X-Foo-Bar', "value\x11injection"],
            'value-with-form-feed'    => ['X-Foo-Bar', "value\x12injection"],
            'value-with-null-byte'    => ['X-Foo-Bar', "value\x00injection"],
            'value-with-del-byte'     => ['X-Foo-Bar', "value\x7finjection"],
        ];
    }

    /**
     * @dataProvider headersWithInjectionVectorsDataProvider
     * @test
     */
    public function doesNotAllowCRLFInjectionWhenCallingWithHeader($name, $value): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->message->withHeader($name, $value);
    }

    /**
     * @dataProvider headersWithInjectionVectorsDataProvider
     * @test
     */
    public function doesNotAllowCRLFInjectionWhenCallingWithAddedHeader($name, $value): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->message->withAddedHeader($name, $value);
    }
}

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

use TYPO3\CMS\Core\Http\Message;
use TYPO3\CMS\Core\Http\Stream;

/**
 * Testcase for \TYPO3\CMS\Core\Http\Message
 *
 * Adapted from https://github.com/phly/http/
 */
class MessageTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var Stream
     */
    protected $stream;

    /**
     * @var Message
     */
    protected $message;

    protected function setUp()
    {
        $this->stream = new Stream('php://memory', 'wb+');
        $this->message = (new Message())->withBody($this->stream);
    }

    /**
     * @test
     */
    public function protocolHasAcceptableDefault()
    {
        $this->assertEquals('1.1', $this->message->getProtocolVersion());
    }

    /**
     * @test
     */
    public function protocolMutatorReturnsCloneWithChanges()
    {
        $message = $this->message->withProtocolVersion('1.0');
        $this->assertNotSame($this->message, $message);
        $this->assertEquals('1.0', $message->getProtocolVersion());
    }

    /**
     * @test
     */
    public function usesStreamProvidedInConstructorAsBody()
    {
        $this->assertSame($this->stream, $this->message->getBody());
    }

    /**
     * @test
     */
    public function bodyMutatorReturnsCloneWithChanges()
    {
        $stream = new Stream('php://memory', 'wb+');
        $message = $this->message->withBody($stream);
        $this->assertNotSame($this->message, $message);
        $this->assertSame($stream, $message->getBody());
    }

    /**
     * @test
     */
    public function getHeaderReturnsHeaderValueAsArray()
    {
        $message = $this->message->withHeader('X-Foo', ['Foo', 'Bar']);
        $this->assertNotSame($this->message, $message);
        $this->assertEquals(['Foo', 'Bar'], $message->getHeader('X-Foo'));
    }

    /**
     * @test
     */
    public function getHeaderLineReturnsHeaderValueAsCommaConcatenatedString()
    {
        $message = $this->message->withHeader('X-Foo', ['Foo', 'Bar']);
        $this->assertNotSame($this->message, $message);
        $this->assertEquals('Foo,Bar', $message->getHeaderLine('X-Foo'));
    }

    /**
     * @test
     */
    public function getHeadersKeepsHeaderCaseSensitivity()
    {
        $message = $this->message->withHeader('X-Foo', ['Foo', 'Bar']);
        $this->assertNotSame($this->message, $message);
        $this->assertEquals(['X-Foo' => ['Foo', 'Bar']], $message->getHeaders());
    }

    /**
     * @test
     */
    public function getHeadersReturnsCaseWithWhichHeaderFirstRegistered()
    {
        $message = $this->message
            ->withHeader('X-Foo', 'Foo')
            ->withAddedHeader('x-foo', 'Bar');
        $this->assertNotSame($this->message, $message);
        $this->assertEquals(['X-Foo' => ['Foo', 'Bar']], $message->getHeaders());
    }

    /**
     * @test
     */
    public function hasHeaderReturnsFalseIfHeaderIsNotPresent()
    {
        $this->assertFalse($this->message->hasHeader('X-Foo'));
    }

    /**
     * @test
     */
    public function hasHeaderReturnsTrueIfHeaderIsPresent()
    {
        $message = $this->message->withHeader('X-Foo', 'Foo');
        $this->assertNotSame($this->message, $message);
        $this->assertTrue($message->hasHeader('X-Foo'));
    }

    /**
     * @test
     */
    public function addHeaderAppendsToExistingHeader()
    {
        $message = $this->message->withHeader('X-Foo', 'Foo');
        $this->assertNotSame($this->message, $message);
        $message2 = $message->withAddedHeader('X-Foo', 'Bar');
        $this->assertNotSame($message, $message2);
        $this->assertEquals('Foo,Bar', $message2->getHeaderLine('X-Foo'));
    }

    /**
     * @test
     */
    public function canRemoveHeaders()
    {
        $message = $this->message->withHeader('X-Foo', 'Foo');
        $this->assertNotSame($this->message, $message);
        $this->assertTrue($message->hasHeader('x-foo'));
        $message2 = $message->withoutHeader('x-foo');
        $this->assertNotSame($this->message, $message2);
        $this->assertNotSame($message, $message2);
        $this->assertFalse($message2->hasHeader('X-Foo'));
    }

    /**
     * @test
     */
    public function headerRemovalIsCaseInsensitive()
    {
        $message = $this->message
            ->withHeader('X-Foo', 'Foo')
            ->withAddedHeader('x-foo', 'Bar')
            ->withAddedHeader('X-FOO', 'Baz');
        $this->assertNotSame($this->message, $message);
        $this->assertTrue($message->hasHeader('x-foo'));
        $message2 = $message->withoutHeader('x-foo');
        $this->assertNotSame($this->message, $message2);
        $this->assertNotSame($message, $message2);
        $this->assertFalse($message2->hasHeader('X-Foo'));
        $headers = $message2->getHeaders();
        $this->assertEquals(0, count($headers));
    }

    /**
     * @return array
     */
    public function invalidGeneralHeaderValuesDataProvider()
    {
        return [
            'null'   => [null],
            'true'   => [true],
            'false'  => [false],
            'int'    => [1],
            'float'  => [1.1],
            'array'  => [['foo' => ['bar']]],
            'object' => [(object) ['foo' => 'bar']],
        ];
    }

    /**
     * @dataProvider invalidGeneralHeaderValuesDataProvider
     */
    public function testWithHeaderRaisesExceptionForInvalidNestedHeaderValue($value)
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid header value');
        $message = $this->message->withHeader('X-Foo', [$value]);
    }

    /**
     * @return array
     */
    public function invalidHeaderValuesDataProvider()
    {
        return [
            'null'   => [null],
            'true'   => [true],
            'false'  => [false],
            'int'    => [1],
            'float'  => [1.1],
            'object' => [(object) ['foo' => 'bar']],
        ];
    }

    /**
     * @dataProvider invalidHeaderValuesDataProvider
     */
    public function withHeaderRaisesExceptionForInvalidValueType($value)
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid header value');
        $message = $this->message->withHeader('X-Foo', $value);
    }

    /**
     * @dataProvider invalidHeaderValuesDataProvider
     */
    public function withAddedHeaderRaisesExceptionForNonStringNonArrayValue($value)
    {
        $this->setExpectedException('InvalidArgumentException', 'must be a string');
        $message = $this->message->withAddedHeader('X-Foo', $value);
    }

    /**
     * @test
     */
    public function withoutHeaderDoesNothingIfHeaderDoesNotExist()
    {
        $this->assertFalse($this->message->hasHeader('X-Foo'));
        $message = $this->message->withoutHeader('X-Foo');
        $this->assertNotSame($this->message, $message);
        $this->assertFalse($message->hasHeader('X-Foo'));
    }

    /**
     * @test
     */
    public function getHeaderReturnsAnEmptyArrayWhenHeaderDoesNotExist()
    {
        $this->assertSame([], $this->message->getHeader('X-Foo-Bar'));
    }

    /**
     * @test
     */
    public function getHeaderLineReturnsEmptyStringWhenHeaderDoesNotExist()
    {
        $this->assertSame('', $this->message->getHeaderLine('X-Foo-Bar'));
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
            'multi-line-header-space' => ['X-Foo-Bar', "value\r\n injection"],
            'multi-line-header-tab' => ['X-Foo-Bar', "value\r\n\tinjection"],
        ];
    }

    /**
     * @dataProvider headersWithInjectionVectorsDataProvider
     * @test
     */
    public function doesNotAllowCRLFInjectionWhenCallingWithHeader($name, $value)
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->message->withHeader($name, $value);
    }

    /**
     * @dataProvider headersWithInjectionVectorsDataProvider
     * @test
     */
    public function doesNotAllowCRLFInjectionWhenCallingWithAddedHeader($name, $value)
    {
        $this->setExpectedException('InvalidArgumentException');
        $this->message->withAddedHeader($name, $value);
    }
}

<?php
declare(strict_types = 1);
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

use Psr\Http\Message\StreamFactoryInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\StreamFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for \TYPO3\CMS\Core\Http\StreamFactory
 */
class StreamFactoryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function implementsPsr17FactoryInterface()
    {
        $factory = new StreamFactory();
        $this->assertInstanceOf(StreamFactoryInterface::class, $factory);
    }

    /**
     * @test
     */
    public function testCreateStreamReturnsEmptyStreamByDefault()
    {
        $factory = new StreamFactory();
        $stream = $factory->createStream();
        $this->assertSame('', $stream->__toString());
    }

    /**
     * @test
     */
    public function testCreateStreamFromEmptyString()
    {
        $factory = new StreamFactory();
        $stream = $factory->createStream('');
        $this->assertSame('', $stream->__toString());
    }

    /**
     * @test
     */
    public function testCreateStreamFromNonEmptyString()
    {
        $factory = new StreamFactory();
        $stream = $factory->createStream('Foo');
        $this->assertSame('Foo', $stream->__toString());
    }

    /**
     * @test
     */
    public function testCreateStreamReturnsWritableStream()
    {
        $factory = new StreamFactory();
        $stream = $factory->createStream();
        $stream->write('Foo');
        $this->assertSame('Foo', $stream->__toString());
    }

    /**
     * @test
     */
    public function testCreateStreamReturnsAppendableStream()
    {
        $factory = new StreamFactory();
        $stream = $factory->createStream('Foo');
        $stream->write('Bar');
        $this->assertSame('FooBar', $stream->__toString());
    }

    /**
     * @test
     */
    public function testCreateStreamFromFile()
    {
        $fileName = Environment::getVarPath() . '/tests/' . $this->getUniqueId('test_');
        file_put_contents($fileName, 'Foo');

        $factory = new StreamFactory();
        $stream = $factory->createStreamFromFile($fileName);
        $this->assertSame('Foo', $stream->__toString());
    }

    /**
     * @test
     */
    public function testCreateStreamFromFileWithMode()
    {
        $fileName = Environment::getVarPath() . '/tests/' . $this->getUniqueId('test_');

        $factory = new StreamFactory();
        $stream = $factory->createStreamFromFile($fileName, 'w');
        $stream->write('Foo');

        $contents = file_get_contents($fileName);
        $this->assertSame('Foo', $contents);
    }

    /**
     * @test
     */
    public function testCreateStreamFromFileWithInvalidMode()
    {
        $fileName = Environment::getVarPath() . '/tests/' . $this->getUniqueId('test_');
        touch($fileName);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1566823434);
        $factory = new StreamFactory();
        $factory->createStreamFromFile($fileName, 'z');
    }

    /**
     * @test
     */
    public function testCreateStreamFromFileWithMissingFile()
    {
        $unavailableFileName = Environment::getVarPath() . '/tests/' . $this->getUniqueId('test_');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1566823435);
        $factory = new StreamFactory();
        $factory->createStreamFromFile($unavailableFileName, 'r');
    }

    /**
     * @test
     */
    public function testCreateStreamFromResource()
    {
        $fileName = Environment::getVarPath() . '/tests/' . $this->getUniqueId('test_');
        touch($fileName);
        file_put_contents($fileName, 'Foo');

        $resource = fopen($fileName, 'r');

        $factory = new StreamFactory();
        $stream = $factory->createStreamFromResource($resource);
        $this->assertSame('Foo', $stream->__toString());
    }

    /**
     * @test
     */
    public function testCreateStreamResourceFromInvalidResource()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1566853697);
        $resource = xml_parser_create();

        $factory = new StreamFactory();
        $factory->createStreamFromResource($resource);
    }
}

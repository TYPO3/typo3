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

use Psr\Http\Message\StreamFactoryInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\StreamFactory;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for \TYPO3\CMS\Core\Http\StreamFactory
 */
class StreamFactoryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function implementsPsr17FactoryInterface(): void
    {
        $factory = new StreamFactory();
        self::assertInstanceOf(StreamFactoryInterface::class, $factory);
    }

    /**
     * @test
     */
    public function createStreamReturnsEmptyStreamByDefault(): void
    {
        $factory = new StreamFactory();
        $stream = $factory->createStream();
        self::assertSame('', $stream->__toString());
    }

    /**
     * @test
     */
    public function createStreamFromEmptyString(): void
    {
        $factory = new StreamFactory();
        $stream = $factory->createStream('');
        self::assertSame('', $stream->__toString());
    }

    /**
     * @test
     */
    public function createStreamFromNonEmptyString(): void
    {
        $factory = new StreamFactory();
        $stream = $factory->createStream('Foo');
        self::assertSame('Foo', $stream->__toString());
    }

    /**
     * @test
     */
    public function createStreamReturnsWritableStream(): void
    {
        $factory = new StreamFactory();
        $stream = $factory->createStream();
        $stream->write('Foo');
        self::assertSame('Foo', $stream->__toString());
    }

    /**
     * @test
     */
    public function createStreamReturnsAppendableStream(): void
    {
        $factory = new StreamFactory();
        $stream = $factory->createStream('Foo');
        $stream->write('Bar');
        self::assertSame('FooBar', $stream->__toString());
    }

    /**
     * @test
     */
    public function createStreamFromFile(): void
    {
        $fileName = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_');
        file_put_contents($fileName, 'Foo');

        $factory = new StreamFactory();
        $stream = $factory->createStreamFromFile($fileName);
        self::assertSame('Foo', $stream->__toString());
    }

    /**
     * @test
     */
    public function createStreamFromFileWithMode(): void
    {
        $fileName = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_');

        $factory = new StreamFactory();
        $stream = $factory->createStreamFromFile($fileName, 'w');
        $stream->write('Foo');

        $contents = file_get_contents($fileName);
        self::assertSame('Foo', $contents);
    }

    /**
     * @test
     */
    public function createStreamFromFileWithInvalidMode(): void
    {
        $fileName = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_');
        touch($fileName);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1566823434);
        $factory = new StreamFactory();
        $factory->createStreamFromFile($fileName, 'z');
    }

    /**
     * @test
     */
    public function createStreamFromFileWithMissingFile(): void
    {
        $unavailableFileName = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1566823435);
        $factory = new StreamFactory();
        $factory->createStreamFromFile($unavailableFileName, 'r');
    }

    /**
     * @test
     */
    public function createStreamFromResource(): void
    {
        $fileName = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_');
        touch($fileName);
        file_put_contents($fileName, 'Foo');

        $resource = fopen($fileName, 'r');

        $factory = new StreamFactory();
        $stream = $factory->createStreamFromResource($resource);
        self::assertSame('Foo', $stream->__toString());
    }

    /**
     * @test
     */
    public function createStreamResourceFromInvalidResource(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1566853697);
        $resource = xml_parser_create();

        $factory = new StreamFactory();
        $factory->createStreamFromResource($resource);
    }
}

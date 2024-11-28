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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\StreamFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class StreamFactoryTest extends UnitTestCase
{
    /**
     * Helper method to create a random directory and return the path.
     * The path will be registered for deletion upon test ending
     */
    private function getTestDirectory(): string
    {
        $path = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('root_');
        GeneralUtility::mkdir_deep($path);
        $this->testFilesToDelete[] = $path;
        return $path;
    }

    #[Test]
    public function createStreamReturnsEmptyStreamByDefault(): void
    {
        $factory = new StreamFactory();
        $stream = $factory->createStream();
        self::assertSame('', $stream->__toString());
    }

    #[Test]
    public function createStreamFromEmptyString(): void
    {
        $factory = new StreamFactory();
        $stream = $factory->createStream('');
        self::assertSame('', $stream->__toString());
    }

    #[Test]
    public function createStreamFromNonEmptyString(): void
    {
        $factory = new StreamFactory();
        $stream = $factory->createStream('Foo');
        self::assertSame('Foo', $stream->__toString());
    }

    #[Test]
    public function createStreamReturnsWritableStream(): void
    {
        $factory = new StreamFactory();
        $stream = $factory->createStream();
        $stream->write('Foo');
        self::assertSame('Foo', $stream->__toString());
    }

    #[Test]
    public function createStreamReturnsAppendableStream(): void
    {
        $factory = new StreamFactory();
        $stream = $factory->createStream('Foo');
        $stream->write('Bar');
        self::assertSame('FooBar', $stream->__toString());
    }

    #[Test]
    public function createStreamFromFile(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        file_put_contents($fileName, 'Foo');

        $factory = new StreamFactory();
        $stream = $factory->createStreamFromFile($fileName);
        self::assertSame('Foo', $stream->__toString());
    }

    #[Test]
    public function createStreamFromFileWithMode(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');

        $factory = new StreamFactory();
        $stream = $factory->createStreamFromFile($fileName, 'w');
        $stream->write('Foo');

        $contents = file_get_contents($fileName);
        self::assertSame('Foo', $contents);
    }

    #[Test]
    public function createStreamFromFileWithInvalidMode(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        touch($fileName);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1566823434);
        $factory = new StreamFactory();
        $factory->createStreamFromFile($fileName, 'z');
    }

    #[Test]
    public function createStreamFromFileWithMissingFile(): void
    {
        $unavailableFileName = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_');
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1566823435);
        $factory = new StreamFactory();
        $factory->createStreamFromFile($unavailableFileName, 'r');
    }

    #[Test]
    public function createStreamFromResource(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        touch($fileName);
        file_put_contents($fileName, 'Foo');

        $resource = fopen($fileName, 'r');

        $factory = new StreamFactory();
        $stream = $factory->createStreamFromResource($resource);
        self::assertSame('Foo', $stream->__toString());
    }

    #[Test]
    public function createStreamResourceFromInvalidResource(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1566853697);
        $resource = xml_parser_create();

        $factory = new StreamFactory();
        $factory->createStreamFromResource($resource);
    }
}

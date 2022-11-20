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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Adapted from https://github.com/phly/http/
 */
class StreamTest extends UnitTestCase
{
    protected ?Stream $stream;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stream = new Stream('php://memory', 'wb+');
    }

    /**
     * Helper method to create a random directory and return the path.
     * The path will be registered for deletion upon test ending
     */
    protected function getTestDirectory(string $prefix = 'root_'): string
    {
        $path = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId($prefix);
        $this->testFilesToDelete[] = $path;
        GeneralUtility::mkdir_deep($path);
        return $path;
    }

    /**
     * @test
     */
    public function canInstantiateWithStreamIdentifier(): void
    {
        self::assertInstanceOf(Stream::class, $this->stream);
    }

    /**
     * @test
     */
    public function canInstantiateWithStreamResource(): void
    {
        $resource = fopen('php://memory', 'wb+');
        $stream = new Stream($resource);
        self::assertInstanceOf(Stream::class, $stream);
    }

    /**
     * @test
     */
    public function isReadableReturnsFalseIfStreamIsNotReadable(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        touch($fileName);
        $stream = new Stream($fileName, 'w');
        self::assertFalse($stream->isReadable());
    }

    /**
     * @test
     */
    public function isWritableReturnsFalseIfStreamIsNotWritable(): void
    {
        $stream = new Stream('php://memory', 'r');
        self::assertFalse($stream->isWritable());
    }

    /**
     * @test
     */
    public function toStringRetrievesFullContentsOfStream(): void
    {
        $message = 'foo bar';
        $this->stream->write($message);
        self::assertEquals($message, (string)$this->stream);
    }

    /**
     * @test
     */
    public function detachReturnsResource(): void
    {
        $resource = fopen('php://memory', 'wb+');
        $stream = new Stream($resource);
        self::assertSame($resource, $stream->detach());
    }

    /**
     * @test
     */
    public function constructorRaisesExceptionWhenPassingInvalidStreamResource(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Stream(['  THIS WILL NOT WORK  ']);
    }

    /**
     * @test
     */
    public function toStringSerializationReturnsEmptyStringWhenStreamIsNotReadable(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        touch($fileName);
        file_put_contents($fileName, 'FOO BAR');
        $stream = new Stream($fileName, 'w');

        self::assertEquals('', $stream->__toString());
    }

    /**
     * @test
     */
    public function closeClosesResource(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        touch($fileName);
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);
        $stream->close();

        // Testing with a variable here, otherwise the suggested assertion would be assertIsNotResource
        // which fails.
        $isResource = is_resource($resource);
        self::assertFalse($isResource);
    }

    /**
     * @test
     */
    public function closeUnsetsResource(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        touch($fileName);
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);
        $stream->close();

        self::assertNull($stream->detach());
    }

    /**
     * @test
     */
    public function closeDoesNothingAfterDetach(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        touch($fileName);
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);
        $detached = $stream->detach();

        $stream->close();
        self::assertIsResource($detached);
        self::assertSame($resource, $detached);
    }

    /**
     * @test
     */
    public function getSizeReportsNullWhenNoResourcePresent(): void
    {
        $this->stream->detach();
        self::assertNull($this->stream->getSize());
    }

    /**
     * @test
     */
    public function tellReportsCurrentPositionInResource(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);

        fseek($resource, 2);

        self::assertEquals(2, $stream->tell());
    }

    /**
     * @test
     */
    public function tellRaisesExceptionIfResourceIsDetached(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);

        fseek($resource, 2);
        $stream->detach();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1436717285);
        $stream->tell();
    }

    /**
     * @test
     */
    public function eofReportsFalseWhenNotAtEndOfStream(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);

        fseek($resource, 2);
        self::assertFalse($stream->eof());
    }

    /**
     * @test
     */
    public function eofReportsTrueWhenAtEndOfStream(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);

        while (!feof($resource)) {
            fread($resource, 4096);
        }
        self::assertTrue($stream->eof());
    }

    /**
     * @test
     */
    public function eofReportsTrueWhenStreamIsDetached(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);

        fseek($resource, 2);
        $stream->detach();
        self::assertTrue($stream->eof());
    }

    /**
     * @test
     */
    public function isSeekableReturnsTrueForReadableStreams(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);
        self::assertTrue($stream->isSeekable());
    }

    /**
     * @test
     */
    public function isSeekableReturnsFalseForDetachedStreams(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);
        $stream->detach();
        self::assertFalse($stream->isSeekable());
    }

    /**
     * @test
     */
    public function seekAdvancesToGivenOffsetOfStream(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);
        $stream->seek(2);
        self::assertEquals(2, $stream->tell());
    }

    /**
     * @test
     */
    public function rewindResetsToStartOfStream(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);
        $stream->seek(2);
        $stream->rewind();
        self::assertEquals(0, $stream->tell());
    }

    /**
     * @test
     */
    public function seekRaisesExceptionWhenStreamIsDetached(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);
        $stream->detach();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1436717287);
        $stream->seek(2);
    }

    /**
     * @test
     */
    public function isWritableReturnsFalseWhenStreamIsDetached(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);
        $stream->detach();
        self::assertFalse($stream->isWritable());
    }

    /**
     * @test
     */
    public function writeRaisesExceptionWhenStreamIsDetached(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);
        $stream->detach();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1436717290);
        $stream->write('bar');
    }

    /**
     * @test
     */
    public function isReadableReturnsFalseWhenStreamIsDetached(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);
        $stream->detach();
        self::assertFalse($stream->isReadable());
    }

    /**
     * @test
     */
    public function readRaisesExceptionWhenStreamIsDetached(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'r');
        $stream = new Stream($resource);
        $stream->detach();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1436717292);
        $stream->read(4096);
    }

    /**
     * @test
     */
    public function readReturnsEmptyStringWhenAtEndOfFile(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'r');
        $stream = new Stream($resource);
        while (!feof($resource)) {
            fread($resource, 4096);
        }
        self::assertEquals('', $stream->read(4096));
    }

    /**
     * @test
     */
    public function getContentsReturnsEmptyStringIfStreamIsNotReadable(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'w');
        $stream = new Stream($resource);
        self::assertEquals('', $stream->getContents());
    }

    public function invalidResourcesDataProvider(): array
    {
        $fileName = tempnam(sys_get_temp_dir(), 'PHLY');
        $this->testFilesToDelete[] = $fileName;

        return [
            'null'                => [null],
            'false'               => [false],
            'true'                => [true],
            'int'                 => [1],
            'float'               => [1.1],
            'array'               => [[fopen($fileName, 'r+')]],
            'object'              => [(object)['resource' => fopen($fileName, 'r+')]],
        ];
    }

    /**
     * @dataProvider invalidResourcesDataProvider
     * @test
     */
    public function attachWithNonStringNonResourceRaisesExceptionByType($resource): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717297);
        $this->stream->attach($resource);
    }

    /**
     * @test
     */
    public function attachWithNonStringNonResourceRaisesExceptionByString(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717296);
        $this->stream->attach('foo-bar-baz');
    }

    /**
     * @test
     */
    public function attachWithResourceAttachesResource(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        touch($fileName);
        $resource = fopen($fileName, 'r+');
        $this->stream->attach($resource);

        $r = new \ReflectionProperty($this->stream, 'resource');
        $r->setAccessible(true);
        $test = $r->getValue($this->stream);
        self::assertSame($resource, $test);
    }

    /**
     * @test
     */
    public function attachWithStringRepresentingResourceCreatesAndAttachesResource(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        touch($fileName);
        $this->stream->attach($fileName);

        $resource = fopen($fileName, 'r+');
        fwrite($resource, 'FooBar');

        $this->stream->rewind();
        $test = (string)$this->stream;
        self::assertEquals('FooBar', $test);
    }

    /**
     * @test
     */
    public function getContentsShouldGetFullStreamContents(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        touch($fileName);
        $resource = fopen($fileName, 'r+');
        $this->stream->attach($resource);

        fwrite($resource, 'FooBar');

        // rewind, because current pointer is at end of stream!
        $this->stream->rewind();
        $test = $this->stream->getContents();
        self::assertEquals('FooBar', $test);
    }

    /**
     * @test
     */
    public function getContentsShouldReturnStreamContentsFromCurrentPointer(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        touch($fileName);
        $resource = fopen($fileName, 'r+');
        $this->stream->attach($resource);

        fwrite($resource, 'FooBar');

        // seek to position 3
        $this->stream->seek(3);
        $test = $this->stream->getContents();
        self::assertEquals('Bar', $test);
    }

    /**
     * @test
     */
    public function getMetadataReturnsAllMetadataWhenNoKeyPresent(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        touch($fileName);
        $resource = fopen($fileName, 'r+');
        $this->stream->attach($resource);

        $expected = stream_get_meta_data($resource);
        $test = $this->stream->getMetadata();

        self::assertEquals($expected, $test);
    }

    /**
     * @test
     */
    public function getMetadataReturnsDataForSpecifiedKey(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        touch($fileName);
        $resource = fopen($fileName, 'r+');
        $this->stream->attach($resource);

        $metadata = stream_get_meta_data($resource);
        $expected = $metadata['uri'];

        $test = $this->stream->getMetadata('uri');

        self::assertEquals($expected, $test);
    }

    /**
     * @test
     */
    public function getMetadataReturnsNullIfNoDataExistsForKey(): void
    {
        $fileName = $this->getTestDirectory() . '/' . StringUtility::getUniqueId('test_');
        touch($fileName);
        $resource = fopen($fileName, 'r+');
        $this->stream->attach($resource);

        self::assertNull($this->stream->getMetadata('TOTALLY_MADE_UP'));
    }

    /**
     * @test
     */
    public function getSizeReturnsStreamSize(): void
    {
        $resource = fopen(__FILE__, 'r');
        $expected = fstat($resource);
        $stream = new Stream($resource);
        self::assertEquals($expected['size'], $stream->getSize());
    }
}

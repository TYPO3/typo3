<?php

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
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 *
 * Adapted from https://github.com/phly/http/
 */
class StreamTest extends UnitTestCase
{
    /**
     * @var Stream
     */
    protected $stream;

    protected function setUp(): void
    {
        parent::setUp();
        $this->stream = new Stream('php://memory', 'wb+');
    }

    /**
     * @test
     */
    public function canInstantiateWithStreamIdentifier()
    {
        self::assertInstanceOf(Stream::class, $this->stream);
    }

    /**
     * @test
     */
    public function canInstantiateWithStreamResource()
    {
        $resource = fopen('php://memory', 'wb+');
        $stream = new Stream($resource);
        self::assertInstanceOf(Stream::class, $stream);
    }

    /**
     * @test
     */
    public function isReadableReturnsFalseIfStreamIsNotReadable()
    {
        $fileName = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_');
        touch($fileName);
        $this->testFilesToDelete[] = $fileName;
        $stream = new Stream($fileName, 'w');
        self::assertFalse($stream->isReadable());
    }

    /**
     * @test
     */
    public function isWritableReturnsFalseIfStreamIsNotWritable()
    {
        $stream = new Stream('php://memory', 'r');
        self::assertFalse($stream->isWritable());
    }

    /**
     * @test
     */
    public function toStringRetrievesFullContentsOfStream()
    {
        $message = 'foo bar';
        $this->stream->write($message);
        self::assertEquals($message, (string)$this->stream);
    }

    /**
     * @test
     */
    public function detachReturnsResource()
    {
        $resource = fopen('php://memory', 'wb+');
        $stream = new Stream($resource);
        self::assertSame($resource, $stream->detach());
    }

    /**
     * @test
     */
    public function constructorRaisesExceptionWhenPassingInvalidStreamResource()
    {
        $this->expectException(\InvalidArgumentException::class);
        new Stream(['  THIS WILL NOT WORK  ']);
    }

    /**
     * @test
     */
    public function toStringSerializationReturnsEmptyStringWhenStreamIsNotReadable()
    {
        $fileName = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_');
        touch($fileName);
        $this->testFilesToDelete[] = $fileName;
        file_put_contents($fileName, 'FOO BAR');
        $stream = new Stream($fileName, 'w');

        self::assertEquals('', $stream->__toString());
    }

    /**
     * @test
     */
    public function closeClosesResource()
    {
        $fileName = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_');
        touch($fileName);
        $this->testFilesToDelete[] = $fileName;
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);
        $stream->close();
        self::assertFalse(is_resource($resource));
    }

    /**
     * @test
     */
    public function closeUnsetsResource()
    {
        $fileName = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_');
        touch($fileName);
        $this->testFilesToDelete[] = $fileName;
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);
        $stream->close();

        self::assertNull($stream->detach());
    }

    /**
     * @test
     */
    public function closeDoesNothingAfterDetach()
    {
        $fileName = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_');
        touch($fileName);
        $this->testFilesToDelete[] = $fileName;
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);
        $detached = $stream->detach();

        $stream->close();
        self::assertTrue(is_resource($detached));
        self::assertSame($resource, $detached);
    }

    /**
     * @test
     */
    public function getSizeReportsNullWhenNoResourcePresent()
    {
        $this->stream->detach();
        self::assertNull($this->stream->getSize());
    }

    /**
     * @test
     */
    public function tellReportsCurrentPositionInResource()
    {
        $fileName = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_');
        $this->testFilesToDelete[] = $fileName;
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);

        fseek($resource, 2);

        self::assertEquals(2, $stream->tell());
    }

    /**
     * @test
     */
    public function tellRaisesExceptionIfResourceIsDetached()
    {
        $fileName = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_');
        $this->testFilesToDelete[] = $fileName;
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
    public function eofReportsFalseWhenNotAtEndOfStream()
    {
        $fileName = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_');
        $this->testFilesToDelete[] = $fileName;
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);

        fseek($resource, 2);
        self::assertFalse($stream->eof());
    }

    /**
     * @test
     */
    public function eofReportsTrueWhenAtEndOfStream()
    {
        $fileName = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_');
        $this->testFilesToDelete[] = $fileName;
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
    public function eofReportsTrueWhenStreamIsDetached()
    {
        $fileName = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_');
        $this->testFilesToDelete[] = $fileName;
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
    public function isSeekableReturnsTrueForReadableStreams()
    {
        $fileName = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_');
        $this->testFilesToDelete[] = $fileName;
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);
        self::assertTrue($stream->isSeekable());
    }

    /**
     * @test
     */
    public function isSeekableReturnsFalseForDetachedStreams()
    {
        $fileName = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_');
        $this->testFilesToDelete[] = $fileName;
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);
        $stream->detach();
        self::assertFalse($stream->isSeekable());
    }

    /**
     * @test
     */
    public function seekAdvancesToGivenOffsetOfStream()
    {
        $fileName = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_');
        $this->testFilesToDelete[] = $fileName;
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);
        $stream->seek(2);
        self::assertEquals(2, $stream->tell());
    }

    /**
     * @test
     */
    public function rewindResetsToStartOfStream()
    {
        $fileName = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_');
        $this->testFilesToDelete[] = $fileName;
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
    public function seekRaisesExceptionWhenStreamIsDetached()
    {
        $fileName = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_');
        $this->testFilesToDelete[] = $fileName;
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
    public function isWritableReturnsFalseWhenStreamIsDetached()
    {
        $fileName = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_');
        $this->testFilesToDelete[] = $fileName;
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);
        $stream->detach();
        self::assertFalse($stream->isWritable());
    }

    /**
     * @test
     */
    public function writeRaisesExceptionWhenStreamIsDetached()
    {
        $fileName = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_');
        $this->testFilesToDelete[] = $fileName;
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
    public function isReadableReturnsFalseWhenStreamIsDetached()
    {
        $fileName = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_');
        $this->testFilesToDelete[] = $fileName;
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);
        $stream->detach();
        self::assertFalse($stream->isReadable());
    }

    /**
     * @test
     */
    public function readRaisesExceptionWhenStreamIsDetached()
    {
        $fileName = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_');
        $this->testFilesToDelete[] = $fileName;
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
    public function readReturnsEmptyStringWhenAtEndOfFile()
    {
        $fileName = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_');
        $this->testFilesToDelete[] = $fileName;
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
    public function getContentsReturnsEmptyStringIfStreamIsNotReadable()
    {
        $fileName = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_');
        $this->testFilesToDelete[] = $fileName;
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'w');
        $stream = new Stream($resource);
        self::assertEquals('', $stream->getContents());
    }

    /**
     * @return array
     */
    public function invalidResourcesDataProvider()
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
    public function attachWithNonStringNonResourceRaisesExceptionByType($resource)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717297);
        $this->stream->attach($resource);
    }

    /**
     * @test
     */
    public function attachWithNonStringNonResourceRaisesExceptionByString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717296);
        $this->stream->attach('foo-bar-baz');
    }

    /**
     * @test
     */
    public function attachWithResourceAttachesResource()
    {
        $fileName = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_');
        touch($fileName);
        $this->testFilesToDelete[] = $fileName;
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
    public function attachWithStringRepresentingResourceCreatesAndAttachesResource()
    {
        $fileName = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_');
        touch($fileName);
        $this->testFilesToDelete[] = $fileName;
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
    public function getContentsShouldGetFullStreamContents()
    {
        $fileName = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_');
        touch($fileName);
        $this->testFilesToDelete[] = $fileName;
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
    public function getContentsShouldReturnStreamContentsFromCurrentPointer()
    {
        $fileName = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_');
        touch($fileName);
        $this->testFilesToDelete[] = $fileName;
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
    public function getMetadataReturnsAllMetadataWhenNoKeyPresent()
    {
        $fileName = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_');
        touch($fileName);
        $this->testFilesToDelete[] = $fileName;
        $resource = fopen($fileName, 'r+');
        $this->stream->attach($resource);

        $expected = stream_get_meta_data($resource);
        $test = $this->stream->getMetadata();

        self::assertEquals($expected, $test);
    }

    /**
     * @test
     */
    public function getMetadataReturnsDataForSpecifiedKey()
    {
        $fileName = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_');
        touch($fileName);
        $this->testFilesToDelete[] = $fileName;
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
    public function getMetadataReturnsNullIfNoDataExistsForKey()
    {
        $fileName = Environment::getVarPath() . '/tests/' . StringUtility::getUniqueId('test_');
        touch($fileName);
        $this->testFilesToDelete[] = $fileName;
        $resource = fopen($fileName, 'r+');
        $this->stream->attach($resource);

        self::assertNull($this->stream->getMetadata('TOTALLY_MADE_UP'));
    }

    /**
     * @test
     */
    public function getSizeReturnsStreamSize()
    {
        $resource = fopen(__FILE__, 'r');
        $expected = fstat($resource);
        $stream = new Stream($resource);
        self::assertEquals($expected['size'], $stream->getSize());
    }
}

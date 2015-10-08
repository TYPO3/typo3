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

use TYPO3\CMS\Core\Http\Stream;

/**
 * Testcase for \TYPO3\CMS\Core\Http\StreamTest
 *
 * Adapted from https://github.com/phly/http/
 */
class StreamTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var Stream
     */
    protected $stream;

    protected function setUp()
    {
        $this->stream = new Stream('php://memory', 'wb+');
    }

    /**
     * @test
     */
    public function canInstantiateWithStreamIdentifier()
    {
        $this->assertInstanceOf(Stream::class, $this->stream);
    }

    /**
     * @test
     */
    public function canInstantiteWithStreamResource()
    {
        $resource = fopen('php://memory', 'wb+');
        $stream = new Stream($resource);
        $this->assertInstanceOf(Stream::class, $stream);
    }

    /**
     * @test
     */
    public function isReadableReturnsFalseIfStreamIsNotReadable()
    {
        $fileName = PATH_site . 'typo3temp/' . $this->getUniqueId('test_');
        touch($fileName);
        $this->testFilesToDelete[] = $fileName;
        $stream = new Stream($fileName, 'w');
        $this->assertFalse($stream->isReadable());
    }

    /**
     * @test
     */
    public function isWritableReturnsFalseIfStreamIsNotWritable()
    {
        $stream = new Stream('php://memory', 'r');
        $this->assertFalse($stream->isWritable());
    }

    /**
     * @test
     */
    public function toStringRetrievesFullContentsOfStream()
    {
        $message = 'foo bar';
        $this->stream->write($message);
        $this->assertEquals($message, (string) $this->stream);
    }

    /**
     * @test
     */
    public function detachReturnsResource()
    {
        $resource = fopen('php://memory', 'wb+');
        $stream = new Stream($resource);
        $this->assertSame($resource, $stream->detach());
    }

    /**
     * @test
     */
    public function constructorRaisesExceptionWhenPassingInvalidStreamResource()
    {
        $this->setExpectedException('InvalidArgumentException');
        $stream = new Stream(['  THIS WILL NOT WORK  ']);
    }

    /**
     * @test
     */
    public function toStringSerializationReturnsEmptyStringWhenStreamIsNotReadable()
    {
        $fileName = PATH_site . 'typo3temp/' . $this->getUniqueId('test_');
        touch($fileName);
        $this->testFilesToDelete[] = $fileName;
        file_put_contents($fileName, 'FOO BAR');
        $stream = new Stream($fileName, 'w');

        $this->assertEquals('', $stream->__toString());
    }

    /**
     * @test
     */
    public function closeClosesResource()
    {
        $fileName = PATH_site . 'typo3temp/' . $this->getUniqueId('test_');
        touch($fileName);
        $this->testFilesToDelete[] = $fileName;
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);
        $stream->close();
        $this->assertFalse(is_resource($resource));
    }

    /**
     * @test
     */
    public function closeUnsetsResource()
    {
        $fileName = PATH_site . 'typo3temp/' . $this->getUniqueId('test_');
        touch($fileName);
        $this->testFilesToDelete[] = $fileName;
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);
        $stream->close();

        $this->assertNull($stream->detach());
    }

    /**
     * @test
     */
    public function closeDoesNothingAfterDetach()
    {
        $fileName = PATH_site . 'typo3temp/' . $this->getUniqueId('test_');
        touch($fileName);
        $this->testFilesToDelete[] = $fileName;
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);
        $detached = $stream->detach();

        $stream->close();
        $this->assertTrue(is_resource($detached));
        $this->assertSame($resource, $detached);
    }

    /**
     * @test
     */
    public function getSizeReportsNullWhenNoResourcePresent()
    {
        $this->stream->detach();
        $this->assertNull($this->stream->getSize());
    }

    /**
     * @test
     */
    public function tellReportsCurrentPositionInResource()
    {
        $fileName = PATH_site . 'typo3temp/' . $this->getUniqueId('test_');
        $this->testFilesToDelete[] = $fileName;
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);

        fseek($resource, 2);

        $this->assertEquals(2, $stream->tell());
    }

    /**
     * @test
     */
    public function tellRaisesExceptionIfResourceIsDetached()
    {
        $fileName = PATH_site . 'typo3temp/' . $this->getUniqueId('test_');
        $this->testFilesToDelete[] = $fileName;
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);

        fseek($resource, 2);
        $stream->detach();
        $this->setExpectedException('RuntimeException', 'No resource');
        $stream->tell();
    }

    /**
     * @test
     */
    public function eofReportsFalseWhenNotAtEndOfStream()
    {
        $fileName = PATH_site . 'typo3temp/' . $this->getUniqueId('test_');
        $this->testFilesToDelete[] = $fileName;
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);

        fseek($resource, 2);
        $this->assertFalse($stream->eof());
    }

    /**
     * @test
     */
    public function eofReportsTrueWhenAtEndOfStream()
    {
        $fileName = PATH_site . 'typo3temp/' . $this->getUniqueId('test_');
        $this->testFilesToDelete[] = $fileName;
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);

        while (!feof($resource)) {
            fread($resource, 4096);
        }
        $this->assertTrue($stream->eof());
    }

    /**
     * @test
     */
    public function eofReportsTrueWhenStreamIsDetached()
    {
        $fileName = PATH_site . 'typo3temp/' . $this->getUniqueId('test_');
        $this->testFilesToDelete[] = $fileName;
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);

        fseek($resource, 2);
        $stream->detach();
        $this->assertTrue($stream->eof());
    }

    /**
     * @test
     */
    public function isSeekableReturnsTrueForReadableStreams()
    {
        $fileName = PATH_site . 'typo3temp/' . $this->getUniqueId('test_');
        $this->testFilesToDelete[] = $fileName;
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);
        $this->assertTrue($stream->isSeekable());
    }

    /**
     * @test
     */
    public function isSeekableReturnsFalseForDetachedStreams()
    {
        $fileName = PATH_site . 'typo3temp/' . $this->getUniqueId('test_');
        $this->testFilesToDelete[] = $fileName;
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);
        $stream->detach();
        $this->assertFalse($stream->isSeekable());
    }

    /**
     * @test
     */
    public function seekAdvancesToGivenOffsetOfStream()
    {
        $fileName = PATH_site . 'typo3temp/' . $this->getUniqueId('test_');
        $this->testFilesToDelete[] = $fileName;
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);
        $stream->seek(2);
        $this->assertEquals(2, $stream->tell());
    }

    /**
     * @test
     */
    public function rewindResetsToStartOfStream()
    {
        $fileName = PATH_site . 'typo3temp/' . $this->getUniqueId('test_');
        $this->testFilesToDelete[] = $fileName;
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);
        $stream->seek(2);
        $stream->rewind();
        $this->assertEquals(0, $stream->tell());
    }

    /**
     * @test
     */
    public function seekRaisesExceptionWhenStreamIsDetached()
    {
        $fileName = PATH_site . 'typo3temp/' . $this->getUniqueId('test_');
        $this->testFilesToDelete[] = $fileName;
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);
        $stream->detach();
        $this->setExpectedException('RuntimeException', 'No resource');
        $stream->seek(2);
    }

    /**
     * @test
     */
    public function isWritableReturnsFalseWhenStreamIsDetached()
    {
        $fileName = PATH_site . 'typo3temp/' . $this->getUniqueId('test_');
        $this->testFilesToDelete[] = $fileName;
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);
        $stream->detach();
        $this->assertFalse($stream->isWritable());
    }

    /**
     * @test
     */
    public function writeRaisesExceptionWhenStreamIsDetached()
    {
        $fileName = PATH_site . 'typo3temp/' . $this->getUniqueId('test_');
        $this->testFilesToDelete[] = $fileName;
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);
        $stream->detach();
        $this->setExpectedException('RuntimeException', 'No resource');
        $stream->write('bar');
    }

    /**
     * @test
     */
    public function isReadableReturnsFalseWhenStreamIsDetached()
    {
        $fileName = PATH_site . 'typo3temp/' . $this->getUniqueId('test_');
        $this->testFilesToDelete[] = $fileName;
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'wb+');
        $stream = new Stream($resource);
        $stream->detach();
        $this->assertFalse($stream->isReadable());
    }

    /**
     * @test
     */
    public function readRaisesExceptionWhenStreamIsDetached()
    {
        $fileName = PATH_site . 'typo3temp/' . $this->getUniqueId('test_');
        $this->testFilesToDelete[] = $fileName;
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'r');
        $stream = new Stream($resource);
        $stream->detach();
        $this->setExpectedException('RuntimeException', 'No resource');
        $stream->read(4096);
    }

    /**
     * @test
     */
    public function readReturnsEmptyStringWhenAtEndOfFile()
    {
        $fileName = PATH_site . 'typo3temp/' . $this->getUniqueId('test_');
        $this->testFilesToDelete[] = $fileName;
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'r');
        $stream = new Stream($resource);
        while (!feof($resource)) {
            fread($resource, 4096);
        }
        $this->assertEquals('', $stream->read(4096));
    }

    /**
     * @test
     */
    public function getContentsReturnsEmptyStringIfStreamIsNotReadable()
    {
        $fileName = PATH_site . 'typo3temp/' . $this->getUniqueId('test_');
        $this->testFilesToDelete[] = $fileName;
        file_put_contents($fileName, 'FOO BAR');
        $resource = fopen($fileName, 'w');
        $stream = new Stream($resource);
        $this->assertEquals('', $stream->getContents());
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
            'string-non-resource' => ['foo-bar-baz'],
            'array'               => [[fopen($fileName, 'r+')]],
            'object'              => [(object) ['resource' => fopen($fileName, 'r+')]],
        ];
    }

    /**
     * @dataProvider invalidResourcesDataProvider
     * @test
     */
    public function attachWithNonStringNonResourceRaisesException($resource)
    {
        $this->setExpectedException('InvalidArgumentException', 'Invalid stream');
        $this->stream->attach($resource);
    }

    /**
     * @test
     */
    public function attachWithResourceAttachesResource()
    {
        $fileName = PATH_site . 'typo3temp/' . $this->getUniqueId('test_');
        touch($fileName);
        $this->testFilesToDelete[] = $fileName;
        $resource = fopen($fileName, 'r+');
        $this->stream->attach($resource);

        $r = new \ReflectionProperty($this->stream, 'resource');
        $r->setAccessible(true);
        $test = $r->getValue($this->stream);
        $this->assertSame($resource, $test);
    }

    /**
     * @test
     */
    public function attachWithStringRepresentingResourceCreatesAndAttachesResource()
    {
        $fileName = PATH_site . 'typo3temp/' . $this->getUniqueId('test_');
        touch($fileName);
        $this->testFilesToDelete[] = $fileName;
        $this->stream->attach($fileName);

        $resource = fopen($fileName, 'r+');
        fwrite($resource, 'FooBar');

        $this->stream->rewind();
        $test = (string) $this->stream;
        $this->assertEquals('FooBar', $test);
    }

    /**
     * @test
     */
    public function getContentsShouldGetFullStreamContents()
    {
        $fileName = PATH_site . 'typo3temp/' . $this->getUniqueId('test_');
        touch($fileName);
        $this->testFilesToDelete[] = $fileName;
        $resource = fopen($fileName, 'r+');
        $this->stream->attach($resource);

        fwrite($resource, 'FooBar');

        // rewind, because current pointer is at end of stream!
        $this->stream->rewind();
        $test = $this->stream->getContents();
        $this->assertEquals('FooBar', $test);
    }

    /**
     * @test
     */
    public function getContentsShouldReturnStreamContentsFromCurrentPointer()
    {
        $fileName = PATH_site . 'typo3temp/' . $this->getUniqueId('test_');
        touch($fileName);
        $this->testFilesToDelete[] = $fileName;
        $resource = fopen($fileName, 'r+');
        $this->stream->attach($resource);

        fwrite($resource, 'FooBar');

        // seek to position 3
        $this->stream->seek(3);
        $test = $this->stream->getContents();
        $this->assertEquals('Bar', $test);
    }

    /**
     * @test
     */
    public function getMetadataReturnsAllMetadataWhenNoKeyPresent()
    {
        $fileName = PATH_site . 'typo3temp/' . $this->getUniqueId('test_');
        touch($fileName);
        $this->testFilesToDelete[] = $fileName;
        $resource = fopen($fileName, 'r+');
        $this->stream->attach($resource);

        $expected = stream_get_meta_data($resource);
        $test = $this->stream->getMetadata();

        $this->assertEquals($expected, $test);
    }

    /**
     * @test
     */
    public function getMetadataReturnsDataForSpecifiedKey()
    {
        $fileName = PATH_site . 'typo3temp/' . $this->getUniqueId('test_');
        touch($fileName);
        $this->testFilesToDelete[] = $fileName;
        $resource = fopen($fileName, 'r+');
        $this->stream->attach($resource);

        $metadata = stream_get_meta_data($resource);
        $expected = $metadata['uri'];

        $test = $this->stream->getMetadata('uri');

        $this->assertEquals($expected, $test);
    }

    /**
     * @test
     */
    public function getMetadataReturnsNullIfNoDataExistsForKey()
    {
        $fileName = PATH_site . 'typo3temp/' . $this->getUniqueId('test_');
        touch($fileName);
        $this->testFilesToDelete[] = $fileName;
        $resource = fopen($fileName, 'r+');
        $this->stream->attach($resource);

        $this->assertNull($this->stream->getMetadata('TOTALLY_MADE_UP'));
    }

    /**
     * @test
     */
    public function getSizeReturnsStreamSize()
    {
        $resource = fopen(__FILE__, 'r');
        $expected = fstat($resource);
        $stream = new Stream($resource);
        $this->assertEquals($expected['size'], $stream->getSize());
    }
}

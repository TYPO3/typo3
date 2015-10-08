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
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Testcase for \TYPO3\CMS\Core\Http\UploadedFile
 *
 * Adapted from https://github.com/phly/http/
 */
class UploadedFileTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    protected $tmpFile;

    protected function setUp()
    {
        $this->tmpfile = null;
    }

    protected function tearDown()
    {
        if (is_scalar($this->tmpFile) && file_exists($this->tmpFile)) {
            unlink($this->tmpFile);
        }
    }

    /**
     * @return array
     */
    public function invalidStreamsDataProvider()
    {
        return [
            'null'   => [null],
            'true'   => [true],
            'false'  => [false],
            'int'    => [1],
            'float'  => [1.1],
            /* Have not figured out a valid way to test an invalid path yet; null byte injection
             * appears to get caught by fopen()
            'invalid-path' => [ ('WIN' === strtoupper(substr(PHP_OS, 0, 3))) ? '[:]' : 'foo' . chr(0) ],
             */
            'array'  => [['filename']],
            'object' => [(object) ['filename']],
        ];
    }

    /**
     * @dataProvider invalidStreamsDataProvider
     * @test
     */
    public function constructorRaisesExceptionOnInvalidStreamOrFile($streamOrFile)
    {
        $this->setExpectedException('InvalidArgumentException');
        new UploadedFile($streamOrFile, 0, UPLOAD_ERR_OK);
    }

    /**
     * @return array
     */
    public function invalidSizesDataProvider()
    {
        return [
            'null'   => [null],
            'true'   => [true],
            'false'  => [false],
            'float'  => [1.1],
            'string' => ['1'],
            'array'  => [[1]],
            'object' => [(object) [1]],
        ];
    }

    /**
     * @dataProvider invalidSizesDataProvider
     * @test
     */
    public function constructorRaisesExceptionOnInvalidSize($size)
    {
        $this->setExpectedException('InvalidArgumentException', 'size');
        new UploadedFile(fopen('php://temp', 'wb+'), $size, UPLOAD_ERR_OK);
    }

    /**
     * @return array
     */
    public function invalidErrorStatusesDataProvider()
    {
        return [
            'null'     => [null],
            'true'     => [true],
            'false'    => [false],
            'float'    => [1.1],
            'string'   => ['1'],
            'array'    => [[1]],
            'object'   => [(object) [1]],
            'negative' => [-1],
            'too-big'  => [9],
        ];
    }

    /**
     * @dataProvider invalidErrorStatusesDataProvider
     * @test
     */
    public function constructorRaisesExceptionOnInvalidErrorStatus($status)
    {
        $this->setExpectedException('InvalidArgumentException', 'status');
        new UploadedFile(fopen('php://temp', 'wb+'), 0, $status);
    }

    /**
     * @return array
     */
    public function invalidFilenamesAndMediaTypesDataProvider()
    {
        return [
            'true'   => [true],
            'false'  => [false],
            'int'    => [1],
            'float'  => [1.1],
            'array'  => [['string']],
            'object' => [(object) ['string']],
        ];
    }

    /**
     * @dataProvider invalidFilenamesAndMediaTypesDataProvider
     * @test
     */
    public function constructorRaisesExceptionOnInvalidClientFilename($filename)
    {
        $this->setExpectedException('InvalidArgumentException', 'filename');
        new UploadedFile(fopen('php://temp', 'wb+'), 0, UPLOAD_ERR_OK, $filename);
    }

    /**
     * @dataProvider invalidFilenamesAndMediaTypesDataProvider
     * @test
     */
    public function constructorRaisesExceptionOnInvalidClientMediaType($mediaType)
    {
        $this->setExpectedException('InvalidArgumentException', 'media type');
        new UploadedFile(fopen('php://temp', 'wb+'), 0, UPLOAD_ERR_OK, 'foobar.baz', $mediaType);
    }

    /**
     * @test
     */
    public function getStreamReturnsOriginalStreamObject()
    {
        $stream = new Stream('php://temp');
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);
        $this->assertSame($stream, $upload->getStream());
    }

    /**
     * @test
     */
    public function getStreamReturnsWrappedPhpStream()
    {
        $stream = fopen('php://temp', 'wb+');
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);
        $uploadStream = $upload->getStream()->detach();
        $this->assertSame($stream, $uploadStream);
    }

    /**
     * @test
     */
    public function getStreamReturnsStreamForFile()
    {
        $this->tmpFile = $stream = tempnam(sys_get_temp_dir(), 'phly');
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);
        $uploadStream = $upload->getStream();
        $r = new \ReflectionProperty($uploadStream, 'stream');
        $r->setAccessible(true);
        $this->assertSame($stream, $r->getValue($uploadStream));
    }

    /**
     * @test
     */
    public function moveToMovesFileToDesignatedPath()
    {
        $stream = new Stream('php://temp', 'wb+');
        $stream->write('Foo bar!');
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);

        $this->tmpFile = $to = GeneralUtility::tempnam('psr7');
        $upload->moveTo($to);
        $this->assertTrue(file_exists($to));
        $contents = file_get_contents($to);
        $this->assertEquals($stream->__toString(), $contents);
    }

    /**
     * @return array
     */
    public function invalidMovePathsDataProvider()
    {
        return [
            'null'   => [null],
            'true'   => [true],
            'false'  => [false],
            'int'    => [1],
            'float'  => [1.1],
            'empty'  => [''],
            'array'  => [['filename']],
            'object' => [(object) ['filename']],
        ];
    }

    /**
     * @dataProvider invalidMovePathsDataProvider
     * @test
     */
    public function moveToRaisesExceptionForInvalidPath($path)
    {
        $stream = new Stream('php://temp', 'wb+');
        $stream->write('Foo bar!');
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);

        $this->tmpFile = $path;
        $this->setExpectedException('InvalidArgumentException', 'path');
        $upload->moveTo($path);
    }

    /**
     * @test
     */
    public function moveToCannotBeCalledMoreThanOnce()
    {
        $stream = new Stream('php://temp', 'wb+');
        $stream->write('Foo bar!');
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);

        $this->tmpFile = $to = GeneralUtility::tempnam('psr7');
        $upload->moveTo($to);
        $this->assertTrue(file_exists($to));

        $this->setExpectedException('RuntimeException', 'moved');
        $upload->moveTo($to);
    }

    /**
     * @test
     */
    public function getGetStreamRaisesExceptionAfterMove()
    {
        $stream = new Stream('php://temp', 'wb+');
        $stream->write('Foo bar!');
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);

        $this->tmpFile = $to = GeneralUtility::tempnam('psr7');
        $upload->moveTo($to);
        $this->assertTrue(file_exists($to));

        $this->setExpectedException('RuntimeException', 'moved');
        $upload->getStream();
    }
}

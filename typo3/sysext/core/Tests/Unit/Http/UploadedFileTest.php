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

use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for \TYPO3\CMS\Core\Http\UploadedFile
 *
 * Adapted from https://github.com/phly/http/
 */
class UploadedFileTest extends UnitTestCase
{
    protected $tmpFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpFile = null;
    }

    protected function tearDown(): void
    {
        if (is_string($this->tmpFile) && file_exists($this->tmpFile)) {
            unlink($this->tmpFile);
        }
        parent::tearDown();
    }

    /**
     * @return array
     */
    public function invalidStreamsDataProvider(): array
    {
        return [
            'null'   => [null],
            'true'   => [true],
            'false'  => [false],
            'int'    => [1],
            'float'  => [1.1],
            /* Have not figured out a valid way to test an invalid path yet; null byte injection
             * appears to get caught by fopen()
            'invalid-path' => [ ('WIN' === strtoupper(substr(PHP_OS, 0, 3))) ? '[:]' : 'foo' . "\0" ],
             */
            'array'  => [['filename']],
            'object' => [(object)['filename']],
        ];
    }

    /**
     * @dataProvider invalidStreamsDataProvider
     * @test
     */
    public function constructorRaisesExceptionOnInvalidStreamOrFile($streamOrFile): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new UploadedFile($streamOrFile, 0, UPLOAD_ERR_OK);
    }

    /**
     * @return array
     */
    public function invalidSizesDataProvider(): array
    {
        return [
            'null'   => [null],
            'true'   => [true],
            'false'  => [false],
            'float'  => [1.1],
            'string' => ['1'],
            'array'  => [[1]],
            'object' => [(object)[1]],
        ];
    }

    /**
     * @dataProvider invalidSizesDataProvider
     * @test
     */
    public function constructorRaisesExceptionOnInvalidSize($size): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717302);
        new UploadedFile(fopen('php://temp', 'wb+'), $size, UPLOAD_ERR_OK);
    }

    /**
     * @return array
     */
    public function invalidErrorStatusesDataProvider(): array
    {
        return [
            'null'     => [null],
            'true'     => [true],
            'false'    => [false],
            'float'    => [1.1],
            'string'   => ['1'],
            'array'    => [[1]],
            'object'   => [(object)[1]],
            'negative' => [-1],
            'too-big'  => [9],
        ];
    }

    /**
     * @dataProvider invalidErrorStatusesDataProvider
     * @test
     */
    public function constructorRaisesExceptionOnInvalidErrorStatus($status): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717303);
        new UploadedFile(fopen('php://temp', 'wb+'), 0, $status);
    }

    /**
     * @return array
     */
    public function invalidFilenamesAndMediaTypesDataProvider(): array
    {
        return [
            'true'   => [true],
            'false'  => [false],
            'int'    => [1],
            'float'  => [1.1],
            'array'  => [['string']],
            'object' => [(object)['string']],
        ];
    }

    /**
     * @dataProvider invalidFilenamesAndMediaTypesDataProvider
     * @test
     */
    public function constructorRaisesExceptionOnInvalidClientFilename($filename): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717304);
        new UploadedFile(fopen('php://temp', 'wb+'), 0, UPLOAD_ERR_OK, $filename);
    }

    /**
     * @dataProvider invalidFilenamesAndMediaTypesDataProvider
     * @test
     */
    public function constructorRaisesExceptionOnInvalidClientMediaType($mediaType): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717305);
        new UploadedFile(fopen('php://temp', 'wb+'), 0, UPLOAD_ERR_OK, 'foobar.baz', $mediaType);
    }

    /**
     * @test
     */
    public function getStreamReturnsOriginalStreamObject(): void
    {
        $stream = new Stream('php://temp');
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);
        self::assertSame($stream, $upload->getStream());
    }

    /**
     * @test
     */
    public function getStreamReturnsWrappedPhpStream(): void
    {
        $stream = fopen('php://temp', 'wb+');
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);
        $uploadStream = $upload->getStream()->detach();
        self::assertSame($stream, $uploadStream);
    }

    /**
     * @test
     */
    public function getStreamReturnsStreamForFile(): void
    {
        $this->tmpFile = $stream = tempnam(sys_get_temp_dir(), 'phly');
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);
        $uploadStream = $upload->getStream();
        $r = new \ReflectionProperty($uploadStream, 'stream');
        $r->setAccessible(true);
        self::assertSame($stream, $r->getValue($uploadStream));
    }

    /**
     * @test
     */
    public function moveToMovesFileToDesignatedPath(): void
    {
        $stream = new Stream('php://temp', 'wb+');
        $stream->write('Foo bar!');
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);

        $this->tmpFile = $to = GeneralUtility::tempnam('psr7');
        $upload->moveTo($to);
        self::assertFileExists($to);
        $contents = file_get_contents($to);
        self::assertEquals($stream->__toString(), $contents);
    }

    /**
     * @return array
     */
    public function invalidMovePathsDataProvider(): array
    {
        return [
            'null'   => [null],
            'true'   => [true],
            'false'  => [false],
            'int'    => [1],
            'float'  => [1.1],
            'empty'  => [''],
            'array'  => [['filename']],
            'object' => [(object)['filename']],
        ];
    }

    /**
     * @dataProvider invalidMovePathsDataProvider
     * @test
     */
    public function moveToRaisesExceptionForInvalidPath($path): void
    {
        $stream = new Stream('php://temp', 'wb+');
        $stream->write('Foo bar!');
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);

        $this->tmpFile = $path;
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717307);
        $upload->moveTo($path);
    }

    /**
     * @test
     */
    public function moveToCannotBeCalledMoreThanOnce(): void
    {
        $stream = new Stream('php://temp', 'wb+');
        $stream->write('Foo bar!');
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);

        $this->tmpFile = $to = GeneralUtility::tempnam('psr7');
        $upload->moveTo($to);
        self::assertFileExists($to);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1436717308);
        $upload->moveTo($to);
    }

    /**
     * @test
     */
    public function getGetStreamRaisesExceptionAfterMove(): void
    {
        $stream = new Stream('php://temp', 'wb+');
        $stream->write('Foo bar!');
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);

        $this->tmpFile = $to = GeneralUtility::tempnam('psr7');
        $upload->moveTo($to);
        self::assertFileExists($to);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1436717306);
        $upload->getStream();
    }
}

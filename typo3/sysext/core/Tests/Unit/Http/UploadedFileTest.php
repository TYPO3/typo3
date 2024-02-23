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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Http\UploadedFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class UploadedFileTest extends UnitTestCase
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

    public static function invalidStreamsDataProvider(): array
    {
        return [
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

    #[DataProvider('invalidStreamsDataProvider')]
    #[Test]
    public function constructorRaisesExceptionOnInvalidStreamOrFile($streamOrFile): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new UploadedFile($streamOrFile, 0, UPLOAD_ERR_OK);
    }

    public static function invalidErrorStatusesDataProvider(): array
    {
        return [
            'negative' => [-1],
            'too-big'  => [9],
        ];
    }

    #[DataProvider('invalidErrorStatusesDataProvider')]
    #[Test]
    public function constructorRaisesExceptionOnInvalidErrorStatus($status): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717303);
        new UploadedFile(fopen('php://temp', 'wb+'), 0, $status);
    }

    #[Test]
    public function getStreamReturnsOriginalStreamObject(): void
    {
        $stream = new Stream('php://temp');
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);
        self::assertSame($stream, $upload->getStream());
    }

    #[Test]
    public function getStreamReturnsWrappedPhpStream(): void
    {
        $stream = fopen('php://temp', 'wb+');
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);
        $uploadStream = $upload->getStream()->detach();
        self::assertSame($stream, $uploadStream);
    }

    #[Test]
    public function getStreamReturnsStreamForFile(): void
    {
        $this->tmpFile = $stream = tempnam(sys_get_temp_dir(), 'phly');
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);
        $uploadStream = $upload->getStream();
        $r = new \ReflectionProperty($uploadStream, 'stream');
        self::assertSame($stream, $r->getValue($uploadStream));
    }

    #[Test]
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

    #[Test]
    public function moveToRaisesExceptionForEmptyPath(): void
    {
        $stream = new Stream('php://temp', 'wb+');
        $stream->write('Foo bar!');
        $upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1436717307);
        $upload->moveTo('');
    }

    #[Test]
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

    #[Test]
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

    /**
     * see https://en.wikipedia.org/wiki/Unicode_equivalence#Normalization, "NFD"
     */
    #[Test]
    public function nfdFileNameIsNormalized(): void
    {
        $clientFileName = hex2bin('6fcc88') . '.png';
        $subject = new UploadedFile(fopen('php://temp', 'wb+'), 0, 0, $clientFileName);
        self::assertSame(hex2bin('c3b6') . '.png', $subject->getClientFilename());
    }
}

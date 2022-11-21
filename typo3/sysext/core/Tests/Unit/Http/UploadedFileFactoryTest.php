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

use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Http\UploadedFileFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class UploadedFileFactoryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function implementsPsr17FactoryInterface(): void
    {
        $factory = new UploadedFileFactory();
        self::assertInstanceOf(UploadedFileFactoryInterface::class, $factory);
    }

    /**
     * @test
     */
    public function createUploadedFile(): void
    {
        $stream = new Stream('php://memory');
        $factory = new UploadedFileFactory();
        $uploadedFile = $factory->createUploadedFile($stream, 0);

        self::assertInstanceOf(UploadedFileInterface::class, $uploadedFile);
        self::assertSame(UPLOAD_ERR_OK, $uploadedFile->getError());
        self::assertNull($uploadedFile->getClientFileName());
        self::assertNull($uploadedFile->getClientMediaType());
    }

    /**
     * @test
     */
    public function createUploadedFileWithParams(): void
    {
        $stream = new Stream('php://memory');
        $factory = new UploadedFileFactory();
        $uploadedFile = $factory->createUploadedFile($stream, 0, UPLOAD_ERR_NO_FILE, 'filename.html', 'text/html');

        self::assertInstanceOf(UploadedFileInterface::class, $uploadedFile);
        self::assertSame(UPLOAD_ERR_NO_FILE, $uploadedFile->getError());
        self::assertSame('filename.html', $uploadedFile->getClientFileName());
        self::assertSame('text/html', $uploadedFile->getClientMediaType());
    }

    /**
     * @test
     */
    public function createUploadedFileCreateSizeFromStreamSize(): void
    {
        $stream = new Stream('php://memory', 'rw');
        $stream->write('12345');

        $factory = new UploadedFileFactory();
        $uploadedFile = $factory->createUploadedFile($stream);

        self::assertSame(5, $uploadedFile->getSize());
    }

    /**
     * @test
     */
    public function createUploadedFileThrowsExceptionWhenStreamSizeCanNotBeDetermined(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1566823423);

        $stream = new Stream('php://memory');
        $stream->detach();

        $factory = new UploadedFileFactory();
        $uploadedFile = $factory->createUploadedFile($stream);

        self::assertSame(3, $uploadedFile->getSize());
    }
}

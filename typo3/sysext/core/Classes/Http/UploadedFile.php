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

namespace TYPO3\CMS\Core\Http;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use TYPO3\CMS\Core\Resource\Exception\UploadSizeException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class UploadedFile which represents one uploaded file, usually coming
 * from $_FILES, according to PSR-7 standard.
 *
 * Highly inspired by https://github.com/phly/http/
 *
 * @internal Note that this is not public API yet.
 */
class UploadedFile implements UploadedFileInterface
{
    protected ?string $file = null;
    protected ?StreamInterface $stream = null;
    protected ?string $clientFilename;
    protected ?string $clientMediaType;
    protected int $error;
    protected bool $moved = false;
    protected int $size;

    /**
     * Constructor method
     *
     * @param string|resource|StreamInterface $input is either a stream or a filename
     * @param int $size see $_FILES['size'] from PHP
     * @param int $errorStatus see $_FILES['error']
     * @param string|null $clientFilename the original filename handed over from the client
     * @param string|null $clientMediaType the media type (optional)
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($input, int $size, int $errorStatus, ?string $clientFilename = null, ?string $clientMediaType = null)
    {
        if (is_string($input)) {
            $this->file = $input;
        }

        if (is_resource($input)) {
            $this->stream = new Stream($input);
        } elseif ($input instanceof StreamInterface) {
            $this->stream = $input;
        }

        if (!$this->file && !$this->stream) {
            throw new \InvalidArgumentException('The input given was not a valid stream or file.', 1436717301);
        }

        $this->size = $size;

        if ($errorStatus < 0 || $errorStatus > 8) {
            throw new \InvalidArgumentException('Invalid error status for an uploaded file. See UPLOAD_ERR_* constant in PHP.', 1436717303);
        }
        $this->error = $errorStatus;

        if ($clientFilename !== null) {
            $clientFilename = \Normalizer::normalize($clientFilename);
        }
        $this->clientFilename = is_string($clientFilename) ? $clientFilename : null;
        $this->clientMediaType = $clientMediaType;
    }

    /**
     * Retrieve a stream representing the uploaded file.
     * Returns a StreamInterface instance, representing the uploaded file. The purpose of this method
     * is to allow utilizing native PHP stream functionality to manipulate the file upload, such as
     * stream_copy_to_stream() (though the result will need to be decorated in a native PHP stream wrapper
     * to work with such functions).
     *
     * If the moveTo() method has been called previously, this method raises an exception.
     *
     * @return StreamInterface Stream representation of the uploaded file.
     * @throws \RuntimeException in cases when no stream is available or can be created.
     */
    public function getStream(): StreamInterface
    {
        if ($this->moved) {
            throw new \RuntimeException('Cannot retrieve stream as it was moved.', 1436717306);
        }

        if ($this->stream instanceof StreamInterface) {
            return $this->stream;
        }

        $this->stream = new Stream((string)$this->file);
        return $this->stream;
    }

    /**
     * Move the uploaded file to a new location.
     *
     * Use this method as an alternative to move_uploaded_file(). This method is
     * guaranteed to work in both SAPI and non-SAPI environments.
     * Implementations must determine which environment they are in, and use the
     * appropriate method (move_uploaded_file(), rename(), or a stream
     * operation) to perform the operation.
     *
     * $targetPath may be an absolute path, or a relative path. If it is a
     * relative path, resolution should be the same as used by PHP's rename()
     * function.
     *
     * The original file or stream MUST be removed on completion.
     *
     * If this method is called more than once, any subsequent calls MUST raise
     * an exception.
     *
     * When used in an SAPI environment where $_FILES is populated, when writing
     * files via moveTo(), is_uploaded_file() and move_uploaded_file() SHOULD be
     * used to ensure permissions and upload status are verified correctly.
     *
     * If you wish to move to a stream, use getStream(), as SAPI operations
     * cannot guarantee writing to stream destinations.
     *
     * @see https://php.net/is_uploaded_file
     * @see https://php.net/move_uploaded_file
     * @param string $targetPath Path to which to move the uploaded file.
     * @throws \InvalidArgumentException if the $path specified is invalid.
     * @throws \RuntimeException on any error during the move operation, or on the second or subsequent call to the method.
     */
    public function moveTo(string $targetPath): void
    {
        if (empty($targetPath)) {
            throw new \InvalidArgumentException('Invalid path while moving an uploaded file.', 1436717307);
        }

        if ($this->moved) {
            throw new \RuntimeException('Cannot move uploaded file, as it was already moved.', 1436717308);
        }

        // Check if the target path is inside the allowed paths of TYPO3, and make it absolute.
        $targetPath = GeneralUtility::getFileAbsFileName($targetPath);
        if (empty($targetPath)) {
            throw new \RuntimeException('Cannot move uploaded file, as the target path is empty or invalid.', 1436717309);
        }

        // Max upload size (kb) for files.
        $maxUploadFileSize = GeneralUtility::getMaxUploadFileSize() * 1024;
        if ($this->size > 0 && $maxUploadFileSize > 0 && $this->size >= $maxUploadFileSize) {
            unlink($this->file);
            throw new UploadSizeException('The uploaded file exceeds the size-limit of ' . $maxUploadFileSize . ' bytes', 1647338094);
        }

        if (!empty($this->file) && is_uploaded_file($this->file)) {
            if (GeneralUtility::upload_copy_move($this->file, $targetPath) === false) {
                throw new \RuntimeException('An error occurred while moving uploaded file', 1436717310);
            }
        } elseif ($this->stream) {
            $handle = fopen($targetPath, 'wb+');
            if ($handle === false) {
                throw new \RuntimeException('Unable to write to target path.', 1436717311);
            }

            $this->stream->rewind();
            while (!$this->stream->eof()) {
                fwrite($handle, $this->stream->read(4096));
            }

            fclose($handle);
        }

        $this->moved = true;
    }

    /**
     * Retrieve the file size.
     * Usually returns the value stored in the "size" key of
     * the file in the $_FILES array if available, as PHP calculates this based
     * on the actual size transmitted.
     *
     * @return int|null The file size in bytes or null if unknown.
     */
    public function getSize(): ?int
    {
        return $this->size;
    }

    /**
     * Retrieve the error associated with the uploaded file.
     * Usually returns the value stored in the "error" key of
     * the file in the $_FILES array.
     *
     * The return value MUST be one of PHP's UPLOAD_ERR_XXX constants.
     *
     * If the file was uploaded successfully, this method MUST return
     * UPLOAD_ERR_OK.
     *
     * @see https://php.net/manual/en/features.file-upload.errors.php
     * @return int One of PHP's UPLOAD_ERR_XXX constants.
     */
    public function getError(): int
    {
        return $this->error;
    }

    /**
     * Retrieve the filename sent by the client.
     * Usually returns the value stored in the "name" key of
     * the file in the $_FILES array.
     *
     * Do not trust the value returned by this method. A client could send
     * a malicious filename with the intention to corrupt or hack your
     * application.
     *
     * @return string|null The filename sent by the client or null if none was provided.
     */
    public function getClientFilename(): ?string
    {
        return $this->clientFilename;
    }

    /**
     * Retrieve the temporary file name (for example /tmp/tmp_foo_filexyz
     * If the file has been moved (by moveTo) an exception is thrown.
     *
     * @internal Not part of the PSR interface - used for legacy code in the core
     */
    public function getTemporaryFileName(): ?string
    {
        if ($this->moved) {
            throw new \RuntimeException('Cannot return temporary file name, as it was already moved.', 1436717337);
        }
        return $this->file;
    }

    /**
     * Retrieve the media type sent by the client.
     * Usually returns the value stored in the "type" key of
     * the file in the $_FILES array.
     *
     * Do not trust the value returned by this method. A client could send
     * a malicious media type with the intention to corrupt or hack your
     * application.
     *
     * @return string|null The media type sent by the client or null if none was provided.
     */
    public function getClientMediaType(): ?string
    {
        return $this->clientMediaType;
    }
}

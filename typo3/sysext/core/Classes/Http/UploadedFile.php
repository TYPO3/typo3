<?php
namespace TYPO3\CMS\Core\Http;

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

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

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
    /**
     * @var string|null
     */
    protected $file;

    /**
     * @var StreamInterface|null
     */
    protected $stream;

    /**
     * @var string
     */
    protected $clientFilename;

    /**
     * @var string
     */
    protected $clientMediaType;

    /**
     * @var int
     */
    protected $error;

    /**
     * @var bool
     */
    protected $moved = false;

    /**
     * @var int
     */
    protected $size;

    /**
     * Constructor method
     *
     * @param string|resource $input is either a stream or a filename
     * @param int $size see $_FILES['size'] from PHP
     * @param int $errorStatus see $_FILES['error']
     * @param string $clientFilename the original filename handed over from the client
     * @param string $clientMediaType the media type (optional)
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($input, $size, $errorStatus, $clientFilename = null, $clientMediaType = null)
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

        if (!is_int($size)) {
            throw new \InvalidArgumentException('The size provided for an uploaded file must be an integer.', 1436717302);
        }
        $this->size = $size;

        if (!is_int($errorStatus) || 0 > $errorStatus || 8 < $errorStatus) {
            throw new \InvalidArgumentException('Invalid error status for an uploaded file. See UPLOAD_ERR_* constant in PHP.', 1436717303);
        }
        $this->error = $errorStatus;

        if ($clientFilename !== null && !is_string($clientFilename)) {
            throw new \InvalidArgumentException('Invalid client filename provided for an uploaded file.', 1436717304);
        }
        $this->clientFilename = $clientFilename;

        if ($clientMediaType !== null && !is_string($clientMediaType)) {
            throw new \InvalidArgumentException('Invalid client media type provided for an uploaded file.', 1436717305);
        }
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
    public function getStream()
    {
        if ($this->moved) {
            throw new \RuntimeException('Cannot retrieve stream as it was moved.', 1436717306);
        }

        if ($this->stream instanceof StreamInterface) {
            return $this->stream;
        }

        $this->stream = new Stream($this->file);
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
     * @see http://php.net/is_uploaded_file
     * @see http://php.net/move_uploaded_file
     * @param string $targetPath Path to which to move the uploaded file.
     * @throws \InvalidArgumentException if the $path specified is invalid.
     * @throws \RuntimeException on any error during the move operation, or on the second or subsequent call to the method.
     */
    public function moveTo($targetPath)
    {
        if (!is_string($targetPath) || empty($targetPath)) {
            throw new \InvalidArgumentException('Invalid path while moving an uploaded file.', 1436717307);
        }

        if ($this->moved) {
            throw new \RuntimeException('Cannot move uploaded file, as it was already moved.', 1436717308);
        }

        // Check if the target path is inside the allowed paths of TYPO3, and make it absolute.
        $targetPath = GeneralUtility::getFileAbsFileName($targetPath);
        if (empty($targetPath)) {
            throw new \RuntimeException('Cannot move uploaded file, as it was already moved.', 1436717309);
        }

        if (!empty($this->file) && is_uploaded_file($this->file)) {
            if (GeneralUtility::upload_copy_move($this->file, $targetPath . PathUtility::basename($this->file)) === false) {
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
    public function getSize()
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
     * @see http://php.net/manual/en/features.file-upload.errors.php
     * @return int One of PHP's UPLOAD_ERR_XXX constants.
     */
    public function getError()
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
    public function getClientFilename()
    {
        return $this->clientFilename;
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
    public function getClientMediaType()
    {
        return $this->clientMediaType;
    }
}

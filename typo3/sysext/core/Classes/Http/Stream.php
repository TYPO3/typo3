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

/**
 * Default implementation for the StreamInterface of the PSR-7 standard
 * Acts mainly as a decorator class for streams/resources.
 *
 * Highly inspired by https://github.com/phly/http/
 *
 * @internal Note that this is not public API yet.
 */
class Stream implements StreamInterface
{
    /**
     * The actual PHP resource
     * @var resource
     */
    protected $resource;

    /**
     * @var string|resource
     */
    protected $stream;

    /**
     * Constructor setting up the PHP resource
     *
     * @param string|resource $stream
     * @param string $mode Mode with which to open stream
     * @throws \InvalidArgumentException
     */
    public function __construct($stream, $mode = 'r')
    {
        $this->stream = $stream;
        if (is_resource($stream)) {
            $this->resource = $stream;
        } elseif (is_string($stream)) {
            $this->resource = fopen($stream, $mode);
        } else {
            throw new \InvalidArgumentException('Invalid stream provided; must be a string stream identifier or resource', 1436717284);
        }
    }

    /**
     * Reads all data from the stream into a string, from the beginning to end.
     *
     * This method MUST attempt to seek to the beginning of the stream before
     * reading data and read the stream until the end is reached.
     *
     * Warning: This could attempt to load a large amount of data into memory.
     *
     * This method MUST NOT raise an exception in order to conform with PHP's
     * string casting operations.
     *
     * @see http://php.net/manual/en/language.oop5.magic.php#object.tostring
     * @return string
     */
    public function __toString()
    {
        if (!$this->isReadable()) {
            return '';
        }
        try {
            $this->rewind();
            return $this->getContents();
        } catch (\RuntimeException $e) {
            return '';
        }
    }

    /**
     * Closes the stream and any underlying resources.
     *
     * @return void
     */
    public function close()
    {
        if (!$this->resource) {
            return;
        }
        $resource = $this->detach();
        fclose($resource);
    }

    /**
     * Separates any underlying resources from the stream.
     *
     * After the stream has been detached, the stream is in an unusable state.
     *
     * @return resource|null Underlying PHP stream, if any
     */
    public function detach()
    {
        $resource = $this->resource;
        $this->resource = null;
        return $resource;
    }

    /**
     * Get the size of the stream if known.
     *
     * @return int|null Returns the size in bytes if known, or null if unknown.
     */
    public function getSize()
    {
        if ($this->resource === null) {
            return null;
        }
        $stats = fstat($this->resource);
        return $stats['size'];
    }

    /**
     * Returns the current position of the file read/write pointer
     *
     * @return int Position of the file pointer
     * @throws \RuntimeException on error.
     */
    public function tell()
    {
        if (!$this->resource) {
            throw new \RuntimeException('No resource available; cannot tell position', 1436717285);
        }
        $result = ftell($this->resource);
        if (!is_int($result)) {
            throw new \RuntimeException('Error occurred during tell operation', 1436717286);
        }
        return $result;
    }

    /**
     * Returns true if the stream is at the end of the stream.
     *
     * @return bool
     */
    public function eof()
    {
        if (!$this->resource) {
            return true;
        }
        return feof($this->resource);
    }

    /**
     * Returns whether or not the stream is seekable.
     *
     * @return bool
     */
    public function isSeekable()
    {
        if (!$this->resource) {
            return false;
        }
        return (bool)$this->getMetadata('seekable');
    }

    /**
     * Seek to a position in the stream.
     *
     * @link http://www.php.net/manual/en/function.fseek.php
     *
     * @param int $offset Stream offset
     * @param int $whence Specifies how the cursor position will be calculated
     *     based on the seek offset. Valid values are identical to the built-in
     *     PHP $whence values for `fseek()`.  SEEK_SET: Set position equal to
     *     offset bytes SEEK_CUR: Set position to current location plus offset
     *     SEEK_END: Set position to end-of-stream plus offset.
     *
     * @throws \RuntimeException on failure.
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        if (!$this->resource) {
            throw new \RuntimeException('No resource available; cannot seek position', 1436717287);
        }

        if (!$this->isSeekable()) {
            throw new \RuntimeException('Stream is not seekable', 1436717288);
        }
        $result = fseek($this->resource, $offset, $whence);
        if ($result !== 0) {
            throw new \RuntimeException('Error seeking within stream', 1436717289);
        }
    }

    /**
     * Seek to the beginning of the stream.
     *
     * If the stream is not seekable, this method will raise an exception;
     * otherwise, it will perform a seek(0).
     *
     * @see seek()
     * @link http://www.php.net/manual/en/function.fseek.php
     * @throws \RuntimeException on failure.
     */
    public function rewind()
    {
        $this->seek(0);
    }

    /**
     * Returns whether or not the stream is writable.
     *
     * @return bool
     */
    public function isWritable()
    {
        if (!$this->resource) {
            return false;
        }
        $uri = $this->getMetadata('uri');
        return is_writable($uri);
    }

    /**
     * Write data to the stream.
     *
     * @param string $string The string that is to be written.
     * @return int Returns the number of bytes written to the stream.
     * @throws \RuntimeException on failure.
     */
    public function write($string)
    {
        if (!$this->resource) {
            throw new \RuntimeException('No resource available; cannot write', 1436717290);
        }
        $result = fwrite($this->resource, $string);
        if ($result === false) {
            throw new \RuntimeException('Error writing to stream', 1436717291);
        }
        return $result;
    }

    /**
     * Returns whether or not the stream is readable.
     *
     * @return bool
     */
    public function isReadable()
    {
        if (!$this->resource) {
            return false;
        }
        $mode = $this->getMetadata('mode');
        return strpos($mode, 'r') !== false || strpos($mode, '+') !== false;
    }

    /**
     * Read data from the stream.
     *
     * @param int $length Read up to $length bytes from the object and return
     *     them. Fewer than $length bytes may be returned if underlying stream
     *     call returns fewer bytes.
     * @return string Returns the data read from the stream, or an empty string
     *     if no bytes are available.
     * @throws \RuntimeException if an error occurs.
     */
    public function read($length)
    {
        if (!$this->resource) {
            throw new \RuntimeException('No resource available; cannot read', 1436717292);
        }
        if (!$this->isReadable()) {
            throw new \RuntimeException('Stream is not readable', 1436717293);
        }
        $result = fread($this->resource, $length);
        if ($result === false) {
            throw new \RuntimeException('Error reading stream', 1436717294);
        }
        return $result;
    }

    /**
     * Returns the remaining contents in a string
     *
     * @return string
     * @throws \RuntimeException if unable to read or an error occurs while
     *     reading.
     */
    public function getContents()
    {
        if (!$this->isReadable()) {
            return '';
        }
        $result = stream_get_contents($this->resource);
        if ($result === false) {
            throw new \RuntimeException('Error reading from stream', 1436717295);
        }
        return $result;
    }

    /**
     * Get stream metadata as an associative array or retrieve a specific key.
     *
     * The keys returned are identical to the keys returned from PHP's
     * stream_get_meta_data() function.
     *
     * @link http://php.net/manual/en/function.stream-get-meta-data.php
     *
     * @param string $key Specific metadata to retrieve.
     *
     * @return array|mixed|null Returns an associative array if no key is
     *     provided. Returns a specific key value if a key is provided and the
     *     value is found, or null if the key is not found.
     */
    public function getMetadata($key = null)
    {
        $metadata = stream_get_meta_data($this->resource);
        if ($key === null) {
            return $metadata;
        }
        if (!isset($metadata[$key])) {
            return null;
        }
        return $metadata[$key];
    }

    /**
     * Attach a new stream/resource to the instance.
     *
     * @param string|resource $resource
     * @param string $mode
     * @throws \InvalidArgumentException for stream identifier that cannot be cast to a resource
     * @throws \InvalidArgumentException for non-resource stream
     */
    public function attach($resource, $mode = 'r')
    {
        $error = null;
        if (!is_resource($resource) && is_string($resource)) {
            set_error_handler(function ($e) use (&$error) {
                $error = $e;
            }, E_WARNING);
            $resource = fopen($resource, $mode);
            restore_error_handler();
        }
        if ($error) {
            throw new \InvalidArgumentException('Invalid stream reference provided', 1436717296);
        }
        if (!is_resource($resource)) {
            throw new \InvalidArgumentException('Invalid stream provided; must be a string stream identifier or resource', 1436717297);
        }
        $this->resource = $resource;
    }
}

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

use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @internal Note that this is not public API, use PSR-17 interfaces instead.
 */
class StreamFactory implements StreamFactoryInterface
{
    /**
     * Create a new stream from a string.
     *
     * @param string $content String content with which to populate the stream.
     * @return StreamInterface
     */
    public function createStream(string $content = ''): StreamInterface
    {
        $stream = new Stream('php://temp', 'r+');
        if ($content !== '') {
            $stream->write($content);
        }
        return $stream;
    }

    /**
     * Create a stream from an existing file.
     *
     * The `$filename` MAY be any string supported by `fopen()`.
     *
     * @param string $filename Filename or stream URI to use as basis of stream.
     * @param string $mode Mode with which to open the underlying filename/stream.
     * @return StreamInterface
     * @throws \RuntimeException If the file cannot be opened.
     * @throws \InvalidArgumentException If the mode is invalid.
     */
    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        $resource = @fopen($filename, $mode);
        if ($resource === false) {
            if ($mode === '' || in_array($mode[0], ['r', 'w', 'a', 'x', 'c'], true) === false) {
                throw new \InvalidArgumentException('The mode ' . $mode . ' is invalid.', 1566823434);
            }

            throw new \RuntimeException('The file ' . $filename . ' cannot be opened.', 1566823435);
        }

        return new Stream($resource);
    }

    /**
     * Create a new stream from an existing resource.
     *
     * The stream MUST be readable and may be writable.
     *
     * @param resource $resource PHP resource to use as basis of stream.
     * @return StreamInterface
     * @throws \InvalidArgumentException
     */
    public function createStreamFromResource($resource): StreamInterface
    {
        if (!is_resource($resource) || get_resource_type($resource) !== 'stream') {
            throw new \InvalidArgumentException('Invalid stream provided; must be a stream resource', 1566853697);
        }
        return new Stream($resource);
    }
}

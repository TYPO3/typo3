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

use GuzzleHttp\Psr7\LazyOpenStream;
use GuzzleHttp\Psr7\StreamDecoratorTrait;
use Psr\Http\Message\StreamInterface;

/**
 * This class implements a stream that can be used like a usual PSR-7 stream
 * but is additionally able to provide a file-serving fastpath using readfile().
 * The file this stream refers to is opened on demand.
 *
 * @internal
 */
class SelfEmittableLazyOpenStream implements SelfEmittableStreamInterface
{
    use StreamDecoratorTrait;
    protected string $filename;
    protected LazyOpenStream $stream;

    /**
     * Constructor setting up the PHP resource
     *
     * @param string $filename
     */
    public function __construct($filename)
    {
        $this->stream = new LazyOpenStream($filename, 'r');
        $this->filename = $filename;
    }

    /**
     * Output the contents of the file to the output buffer
     */
    public function emit()
    {
        readfile($this->filename, false);
    }

    public function isWritable(): bool
    {
        return false;
    }

    /**
     * @param string $string
     * @throws \RuntimeException on failure.
     */
    public function write($string)
    {
        throw new \RuntimeException('Cannot write to a ' . self::class, 1538331833);
    }

    /**
     * Creates the underlying stream lazily when required.
     */
    protected function createStream(): StreamInterface
    {
        return $this->stream->stream;
    }
}

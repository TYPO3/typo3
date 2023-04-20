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

use GuzzleHttp\Psr7\StreamDecoratorTrait;
use Psr\Http\Message\StreamInterface;
use TYPO3\CMS\Core\Resource\Driver\DriverInterface;

/**
 * A lazy stream, that wraps the FAL dumpFileContents() method to send file contents
 * using emit(), as defined in SelfEmittableStreamInterface.
 * This call will fall back to the FAL getFileContents() method if the fastpath possibility
 * using SelfEmittableStreamInterface is not used.
 *
 * @internal
 */
class FalDumpFileContentsDecoratorStream implements StreamInterface, SelfEmittableStreamInterface
{
    use StreamDecoratorTrait;

    public function __construct(
        protected readonly string $identifier,
        protected readonly DriverInterface $driver,
        protected readonly int $size
    ) {
    }

    /**
     * Emit the response to stdout, as specified in SelfEmittableStreamInterface.
     * Offload to the driver method dumpFileContents.
     */
    public function emit()
    {
        $this->driver->dumpFileContents($this->identifier);
    }

    /**
     * Creates a stream (on demand). This method is consumed by the guzzle StreamDecoratorTrait
     * and is used when this stream is used without the emit() fastpath.
     */
    protected function createStream(): StreamInterface
    {
        $stream = new Stream('php://temp', 'rw');
        $stream->write($this->driver->getFileContents($this->identifier));
        return $stream;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function isWritable(): bool
    {
        return false;
    }

    /**
     * @throws \RuntimeException
     */
    public function write(string $string): int
    {
        throw new \RuntimeException('Cannot write to a ' . self::class, 1538331852);
    }
}

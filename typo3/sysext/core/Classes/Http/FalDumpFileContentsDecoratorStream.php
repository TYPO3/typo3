<?php
declare(strict_types = 1);

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

    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var DriverInterface
     */
    protected $driver;

    /**
     * @var int
     */
    protected $size;

    /**
     * @param string $identifier
     * @param DriverInterface $driver
     * @param int $size
     */
    public function __construct(string $identifier, DriverInterface $driver, int $size)
    {
        $this->identifier = $identifier;
        $this->driver = $driver;
        $this->size = $size;
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
     *
     * @return StreamInterface
     */
    protected function createStream(): StreamInterface
    {
        $stream = new Stream('php://temp', 'rw');
        $stream->write($this->driver->getFileContents($this->identifier));
        return $stream;
    }

    /**
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * @return bool
     */
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
        throw new \RuntimeException('Cannot write to a ' . self::class, 1538331852);
    }
}

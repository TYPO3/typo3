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

use GuzzleHttp\Psr7\LazyOpenStream;

/**
 * This class implements a stream that can be used like a usual PSR-7 stream
 * but is additionally able to provide a file-serving fastpath using readfile().
 * The file this stream refers to is opened on demand.
 *
 * @internal
 */
class SelfEmittableLazyOpenStream extends LazyOpenStream implements SelfEmittableStreamInterface
{
    /**
     * @var string
     */
    protected $filename;

    /**
     * Constructor setting up the PHP resource
     *
     * @param string $filename
     */
    public function __construct($filename)
    {
        parent::__construct($filename, 'r');
        $this->filename = $filename;
    }

    /**
     * Output the contents of the file to the output buffer
     */
    public function emit()
    {
        readfile($this->filename, false);
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
        throw new \RuntimeException('Cannot write to a ' . self::class, 1538331833);
    }
}

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

namespace TYPO3\CMS\Core\Tests\Functional\Resource\Driver\Fixtures;

use League\Flysystem\Config;
use League\Flysystem\FileAttributes;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\UnableToReadFile;

/**
 * Counts the calls to the adapter, like requests to a remote storage
 */
final class CountingFilesystemAdapter implements FilesystemAdapter
{
    /**
     * @var array<string, int>
     */
    public array $calls = [];

    /**
     * Lets reading fail, like a remote storage that cannot be reached
     */
    public bool $failReads = false;

    /**
     * Ends read streams after the given number of bytes, like an interrupted download
     */
    public ?int $truncateReadStreamsTo = null;

    /**
     * Reports this MIME type for every file, like a remote storage reporting a stored content type
     */
    public ?string $reportedMimeType = null;

    public function __construct(private readonly FilesystemAdapter $adapter) {}

    public function count(string $method): int
    {
        return $this->calls[$method] ?? 0;
    }

    private function track(string $method): void
    {
        $this->calls[$method] = $this->count($method) + 1;
    }

    public function fileExists(string $path): bool
    {
        $this->track(__FUNCTION__);
        return $this->adapter->fileExists($path);
    }

    public function directoryExists(string $path): bool
    {
        $this->track(__FUNCTION__);
        return $this->adapter->directoryExists($path);
    }

    public function write(string $path, string $contents, Config $config): void
    {
        $this->track(__FUNCTION__);
        $this->adapter->write($path, $contents, $config);
    }

    public function writeStream(string $path, $contents, Config $config): void
    {
        $this->track(__FUNCTION__);
        $this->adapter->writeStream($path, $contents, $config);
    }

    public function read(string $path): string
    {
        $this->track(__FUNCTION__);
        if ($this->failReads) {
            throw UnableToReadFile::fromLocation($path, 'storage is not reachable');
        }
        return $this->adapter->read($path);
    }

    public function readStream(string $path)
    {
        $this->track(__FUNCTION__);
        if ($this->failReads) {
            throw UnableToReadFile::fromLocation($path, 'storage is not reachable');
        }
        $stream = $this->adapter->readStream($path);
        if ($this->truncateReadStreamsTo === null) {
            return $stream;
        }
        $truncatedStream = fopen('php://temp', 'w+b');
        stream_copy_to_stream($stream, $truncatedStream, $this->truncateReadStreamsTo);
        fclose($stream);
        rewind($truncatedStream);
        return $truncatedStream;
    }

    public function delete(string $path): void
    {
        $this->track(__FUNCTION__);
        $this->adapter->delete($path);
    }

    public function deleteDirectory(string $path): void
    {
        $this->track(__FUNCTION__);
        $this->adapter->deleteDirectory($path);
    }

    public function createDirectory(string $path, Config $config): void
    {
        $this->track(__FUNCTION__);
        $this->adapter->createDirectory($path, $config);
    }

    public function setVisibility(string $path, string $visibility): void
    {
        $this->track(__FUNCTION__);
        $this->adapter->setVisibility($path, $visibility);
    }

    public function visibility(string $path): FileAttributes
    {
        $this->track(__FUNCTION__);
        return $this->adapter->visibility($path);
    }

    public function mimeType(string $path): FileAttributes
    {
        $this->track(__FUNCTION__);
        if ($this->reportedMimeType !== null) {
            return new FileAttributes($path, null, null, null, $this->reportedMimeType);
        }
        return $this->adapter->mimeType($path);
    }

    public function lastModified(string $path): FileAttributes
    {
        $this->track(__FUNCTION__);
        return $this->adapter->lastModified($path);
    }

    public function fileSize(string $path): FileAttributes
    {
        $this->track(__FUNCTION__);
        return $this->adapter->fileSize($path);
    }

    public function listContents(string $path, bool $deep): iterable
    {
        $this->track(__FUNCTION__);
        return $this->adapter->listContents($path, $deep);
    }

    public function move(string $source, string $destination, Config $config): void
    {
        $this->track(__FUNCTION__);
        $this->adapter->move($source, $destination, $config);
    }

    public function copy(string $source, string $destination, Config $config): void
    {
        $this->track(__FUNCTION__);
        $this->adapter->copy($source, $destination, $config);
    }
}

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

namespace TYPO3\CMS\Filelist\Dto;

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceInterface;

/**
 * @internal
 */
class ResourceCollection implements \Countable, \Iterator, \ArrayAccess
{
    private int $position = 0;

    /**
     * @var ResourceInterface[];
     */
    protected array $resources = [];

    /**
     * @param ResourceInterface[] $resources
     */
    public function __construct(array $resources = [])
    {
        $this->setResources($resources);
    }

    public function addResource(ResourceInterface $resource): self
    {
        $this->resources[] = $resource;
        return $this;
    }

    public function addResources(array $resources): self
    {
        foreach ($resources as $resource) {
            $this->addResource($resource);
        }
        return $this;
    }

    public function setResources(array $resources): self
    {
        $this->resources = [];
        $this->addResources($resources);
        return $this;
    }

    /**
     * @return ResourceInterface[]
     */
    public function getResources(): array
    {
        return $this->resources;
    }

    /**
     * @return Folder[];
     */
    public function getFolders(): array
    {
        return array_filter($this->resources, static function (ResourceInterface $resource): bool {
            return $resource instanceof Folder;
        });
    }

    /**
     * @return File[];
     */
    public function getFiles(): array
    {
        return array_filter($this->resources, static function (ResourceInterface $resource): bool {
            return $resource instanceof File;
        });
    }

    public function getTotalBytes(): int
    {
        $totalBytes = 0;
        foreach ($this->getFiles() as $file) {
            $totalBytes += $file->getSize();
        }

        return $totalBytes;
    }

    public function getTotalFolderCount(): int
    {
        return count($this->getFolders());
    }

    public function getTotalFileCount(): int
    {
        return count($this->getFiles());
    }

    public function getTotalCount(): int
    {
        return count($this->resources);
    }

    /**
     * Array Access
     */
    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->resources[] = $value;
        } else {
            $this->resources[$offset] = $value;
        }
    }

    public function offsetExists($offset): bool
    {
        return isset($this->resources[$offset]);
    }

    public function offsetUnset($offset): void
    {
        unset($this->resources[$offset]);
    }

    public function offsetGet($offset): ?ResourceInterface
    {
        return $this->resources[$offset] ?? null;
    }

    /**
     * Iterator
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        return $this->resources[$this->position];
    }

    #[\ReturnTypeWillChange]
    public function key(): int
    {
        return $this->position;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function valid(): bool
    {
        return isset($this->resources[$this->position]);
    }

    /**
     * Countable
     */
    public function count(): int
    {
        return $this->getTotalCount();
    }
}

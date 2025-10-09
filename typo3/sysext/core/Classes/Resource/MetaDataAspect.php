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

namespace TYPO3\CMS\Core\Resource;

use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Aspect that takes care of a file's metadata
 */
class MetaDataAspect implements \ArrayAccess, \Countable, \Iterator
{
    private array $metaData = [];

    /**
     * This flag is used to treat a possible recursion between $this->get() and $this->file->getUid()
     */
    private bool $loaded = false;

    private int $indexPosition = 0;

    /**
     * Constructor
     */
    public function __construct(
        private readonly File $file
    ) {}

    /**
     * Adds already known metadata to the aspect
     *
     * @internal
     *
     * @return $this
     */
    public function add(array $metaData): self
    {
        $this->loaded = true;
        $this->metaData = array_merge($this->metaData, $metaData);

        return $this;
    }

    /**
     * Gets the metadata of a file. If not metadata is loaded yet, the database gets queried
     */
    public function get(): array
    {
        if (!$this->loaded) {
            $this->loaded = true;
            $this->metaData = $this->loadFromRepository();
        }
        return $this->metaData;
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->get());
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->get()[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->loaded = true;
        $this->metaData[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->metaData[$offset] = null;
    }

    public function count(): int
    {
        return count($this->get());
    }

    /**
     * Resets the internal iterator counter
     */
    public function rewind(): void
    {
        $this->indexPosition = 0;
    }

    /**
     * Gets the current value of iteration
     */
    public function current(): mixed
    {
        $key = array_keys($this->metaData)[$this->indexPosition];
        return $this->metaData[$key];
    }

    /**
     * Returns the key of the current iteration
     */
    public function key(): string
    {
        return array_keys($this->metaData)[$this->indexPosition];
    }

    /**
     * Increases the index for iteration
     */
    public function next(): void
    {
        ++$this->indexPosition;
    }

    public function valid(): bool
    {
        $key = array_keys($this->metaData)[$this->indexPosition] ?? '';
        return array_key_exists($key, $this->metaData);
    }

    /**
     * Creates new or updates existing meta data
     *
     * @internal
     */
    public function save(): void
    {
        $metaDataInDatabase = $this->loadFromRepository();
        if ($metaDataInDatabase === []) {
            $this->metaData = $this->getMetaDataRepository()->createMetaDataRecord($this->file->getUid(), $this->metaData);
        } else {
            $this->metaData = $this->getMetaDataRepository()->update($this->file->getUid(), $this->metaData, $metaDataInDatabase);
        }
    }

    /**
     * Removes a meta data record
     *
     * @internal
     */
    public function remove(): void
    {
        $this->getMetaDataRepository()->removeByFileUid($this->file->getUid());
        $this->metaData = [];
    }

    protected function getMetaDataRepository(): MetaDataRepository
    {
        return GeneralUtility::makeInstance(MetaDataRepository::class);
    }

    protected function loadFromRepository(): array
    {
        return $this->getMetaDataRepository()->findByFileUid($this->file->getUid());
    }
}

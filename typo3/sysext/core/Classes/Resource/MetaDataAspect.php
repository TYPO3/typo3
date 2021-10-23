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
    /**
     * @var File
     */
    private $file;

    /**
     * @var array
     */
    private $metaData = [];

    /**
     * This flag is used to treat a possible recursion between $this->get() and $this->file->getUid()
     *
     * @var bool
     */
    private $loaded = false;

    /**
     * @var int
     */
    private $indexPosition = 0;

    /**
     * Constructor
     *
     * @param File $file
     */
    public function __construct(File $file)
    {
        $this->file = $file;
    }

    /**
     * Adds already known metadata to the aspect
     *
     * @param array $metaData
     * @return self
     * @internal
     */
    public function add(array $metaData): self
    {
        $this->loaded = true;
        $this->metaData = array_merge($this->metaData, $metaData);

        return $this;
    }

    /**
     * Gets the metadata of a file. If not metadata is loaded yet, the database gets queried
     *
     * @return array
     */
    public function get(): array
    {
        if (!$this->loaded) {
            $this->loaded = true;
            $this->metaData = $this->loadFromRepository();
        }
        return $this->metaData;
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->get());
    }

    /**
     * @param mixed $offset
     * @return mixed
     * @todo Set return type to mixed as breaking change in v12 and remove #[\ReturnTypeWillChange].
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->get()[$offset] ?? null;
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        $this->loaded = true;
        $this->metaData[$offset] = $value;
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset): void
    {
        $this->metaData[$offset] = null;
    }

    /**
     * @return int
     */
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
     *
     * @return mixed
     * @todo Set return type to mixed as breaking change in v12 and remove #[\ReturnTypeWillChange].
     */
    #[\ReturnTypeWillChange]
    public function current()
    {
        $key = array_keys($this->metaData)[$this->indexPosition];
        return $this->metaData[$key];
    }

    /**
     * Returns the key of the current iteration
     *
     * @return string
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

    /**
     * @return bool
     */
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
            $this->getMetaDataRepository()->update($this->file->getUid(), $this->metaData);
            $this->metaData = array_merge($metaDataInDatabase, $this->metaData);
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

    /**
     * @return MetaDataRepository
     */
    protected function getMetaDataRepository(): MetaDataRepository
    {
        return GeneralUtility::makeInstance(MetaDataRepository::class);
    }

    /**
     * @return array
     */
    protected function loadFromRepository(): array
    {
        return $this->getMetaDataRepository()->findByFileUid((int)$this->file->getUid());
    }
}

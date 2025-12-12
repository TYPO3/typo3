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

namespace TYPO3\CMS\Core\Schema\Struct;

use TYPO3\CMS\Core\Collection\CollectionInterface;
use TYPO3\CMS\Core\Collection\EditableCollectionInterface;

final class SelectItemCollection implements CollectionInterface, EditableCollectionInterface
{
    private \SplDoublyLinkedList $storage;

    public function __construct()
    {
        $this->storage = new \SplDoublyLinkedList();
    }

    /**
     * Utility method to transform an arbitrary array to a proper SelectItem collection
     *
     * @param array $itemList List of SelectItem elements or legacy item arrays
     * @param string $type The field type, e.g. "select"
     */
    public static function createFromArray(array $itemList, string $type): self
    {
        $collection = new self();
        foreach ($itemList as $item) {
            if ($item instanceof SelectItem) {
                $collection->add($item);
                continue;
            }
            if (is_array($item)) {
                $collection->add(
                    SelectItem::fromTcaItemArray($item, $type)
                );
                continue;
            }
            throw new \InvalidArgumentException(
                'Values of $itemList must be of type ' . SelectItem::class . ' or array.',
                1762417317
            );
        }
        return $collection;
    }

    public function current(): SelectItem
    {
        return $this->storage->current();
    }

    public function next(): void
    {
        $this->storage->next();
    }

    public function key(): int
    {
        return $this->storage->key();
    }

    public function valid(): bool
    {
        return $this->storage->valid();
    }

    public function rewind(): void
    {
        $this->storage->rewind();
    }

    public function count(): int
    {
        return $this->storage->count();
    }

    /**
     * @param SelectItem $data
     */
    public function add($data): void
    {
        if ($data instanceof SelectItem) {
            $this->storage->push($data);
        }
    }

    /**
     * @param SelectItemCollection $other
     */
    public function addAll(CollectionInterface $other): void
    {
        foreach ($other as $item) {
            if ($item instanceof SelectItem) {
                $this->storage->push($item);
            }
        }
    }

    /**
     * @param SelectItem $data
     */
    public function remove($data): void
    {
        if (!($data instanceof SelectItem)) {
            return;
        }

        foreach ($this->storage as $key => $value) {
            if ($value === $data) {
                $this->storage->offsetUnset($key);
                break;
            }
        }
    }

    public function removeAll(): void
    {
        $this->storage = new \SplDoublyLinkedList();
    }

    /**
     * @return SelectItem[]
     */
    public function toArray(): array
    {
        $items = [];
        foreach ($this->storage as $item) {
            $items[] = $item;
        }
        return $items;
    }
}

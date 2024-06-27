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

namespace TYPO3\CMS\Core\Collection;

use TYPO3\CMS\Core\Domain\RecordInterface;

/**
 * When first accessed, this class will initialize itself and find the relations
 * for this record field.
 *
 * This class acts as a "Value holder", as it only fetches the related records
 * when needed.
 *
 * @todo: Evaluate if we should use a Ghost object instead.
 *
 * @internal not part of public API, as this needs to be streamlined and proven
 */
class LazyRecordCollection implements \IteratorAggregate, \ArrayAccess, \Countable
{
    /**
     * @var RecordInterface[]|\Closure
     */
    private array|\Closure $items;

    public function __construct(
        private readonly mixed $fieldValue,
        \Closure $initialization
    ) {
        $this->items = $initialization;
    }

    public function count(): int
    {
        $this->initialize();
        return count($this->items);
    }

    private function initialize(): void
    {
        if ($this->items instanceof \Closure) {
            $this->items = ($this->items)();
        }
    }

    public function getIterator(): \Iterator
    {
        $this->initialize();
        return new \ArrayIterator($this->items);
    }

    public function __toString(): string
    {
        return (string)$this->fieldValue;
    }

    public function offsetExists(mixed $offset): bool
    {
        $this->initialize();
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        $this->initialize();
        return $this->items[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($value instanceof RecordInterface === false) {
            throw new \InvalidArgumentException(
                'Modifying the record collection is only allowed by setting a value of type RecordInterface.',
                1723188315
            );
        }
        $this->items[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \RuntimeException('Removing items from the record collection is not implemented.', 1723188316);
    }
}

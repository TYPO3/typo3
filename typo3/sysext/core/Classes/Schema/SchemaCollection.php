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

namespace TYPO3\CMS\Core\Schema;

final readonly class SchemaCollection implements \ArrayAccess, \IteratorAggregate, \Countable
{
    public function __construct(
        /**
         * @var array<string, SchemaInterface>
         */
        protected array $items
    ) {}

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \InvalidArgumentException('A schema cannot be set.', 1712539286);
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \InvalidArgumentException('A schema cannot be unset.', 1712539285);
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return string[]
     */
    public function getNames(): array
    {
        return array_values(array_map(fn($item): string => $item->getName(), $this->items));
    }

    public static function __set_state(array $state): self
    {
        return new self(...$state);
    }
}

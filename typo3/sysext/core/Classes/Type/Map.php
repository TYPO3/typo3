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

namespace TYPO3\CMS\Core\Type;

/**
 * Map implementation that supports objects as keys.
 *
 * PHP's \WeakMap is not an option in case object keys are created and assigned
 * in an encapsulated scope (like passing a map to a function to enrich it). In
 * case the original object is not referenced anymore, it also will vanish from
 * a \WeakMap, when used as key (see https://www.php.net/manual/class.weakmap.php).
 *
 * PHP's \SplObjectStorage has a strange behavior when using an iteration like
 * `foreach ($map as $key => $value)` - the `$value` is actually the `$key` for
 * BC reasons (see https://bugs.php.net/bug.php?id=49967).
 *
 * This individual implementation works around the "weak" behavior of \WeakMap
 * and the iteration issue with `foreach` of `\SplObjectStorage` by acting as
 * a wrapper for `\SplObjectStorage` with reduced features.
 *
 * Example:
 * ```
 * $map = new \TYPO3\CMS\Core\Type\Map();
 * $key = new \stdClass();
 * $value = new \stdClass();
 * $map[$key] = $value;
 *
 * foreach ($map as $key => $value) { ... }
 * ```
 */
final class Map implements \ArrayAccess, \Countable, \Iterator
{
    private \SplObjectStorage $storage;

    /**
     * @template E array{0:mixed, 1:mixed}
     * @param list<E> $entries
     */
    public static function fromEntries(array ...$entries): self
    {
        $map = new self();
        foreach ($entries as $entry) {
            $map[$entry[0]] = $entry[1];
        }
        return $map;
    }

    public function __construct()
    {
        $this->storage = new \SplObjectStorage();
    }

    public function key(): mixed
    {
        return $this->storage->current();
    }

    public function current(): mixed
    {
        return $this->storage->getInfo();
    }

    public function next(): void
    {
        $this->storage->next();
    }

    public function rewind(): void
    {
        $this->storage->rewind();
    }

    public function valid(): bool
    {
        return $this->storage->valid();
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->storage->offsetExists($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        if (!$this->offsetExists($offset)) {
            return null;
        }
        return $this->storage->offsetGet($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->storage->offsetSet($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        $this->storage->offsetUnset($offset);
    }

    public function count(): int
    {
        return count($this->storage);
    }
}

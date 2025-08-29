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
 * Map implementation that supports objects as keys, as well as scalar values.
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
 * and the iteration issue with `foreach` of `\SplObjectStorage` by maintaining
 * its own internal state.
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
    private array $keys = [];
    private array $values = [];
    private int $index = 0;
    private int $length = 0;
    /**
     * Whether the internal index exceeded the end (either the map is empty,
     * or previous call to `next()` exceeded the amount of available entries)
     */
    private bool $end = true;

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

    public function key(): mixed
    {
        return $this->valid() ? $this->keys[$this->index] : null;
    }

    public function current(): mixed
    {
        return $this->valid() ? $this->values[$this->index] : null;
    }

    public function next(): void
    {
        if (!$this->valid()) {
            return;
        }
        if ($this->index + 1 < $this->length) {
            $this->index++;
        } else {
            $this->end = true;
        }
    }

    public function rewind(): void
    {
        $this->index = 0;
        $this->updateState();
    }

    public function valid(): bool
    {
        return !$this->end;
    }

    public function offsetExists(mixed $offset): bool
    {
        return in_array($offset, $this->keys(), true);
    }

    public function offsetGet(mixed $offset): mixed
    {
        $index = array_search($offset, $this->keys(), true);
        return $index === false ? null : $this->values[$index];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $index = array_search($offset, $this->keys, true);
        if ($index !== false) {
            $this->values[$index] = $value;
            return;
        }
        $this->keys[] = $offset;
        $this->values[] = $value;
        $this->length++;
        if ($this->end) {
            $this->index = $this->length - 1;
        }
        $this->updateState();
    }

    public function offsetUnset(mixed $offset): void
    {
        $index = array_search($offset, $this->keys, true);
        if ($index === false) {
            return;
        }
        unset($this->keys[$index], $this->values[$index]);
        $this->keys = array_values($this->keys);
        $this->values = array_values($this->values);
        // key indexes `[0 => A, 1 => B, 2 => C]`
        // | unset  | $this->   | $this->   |
        // | $index | index cur | index new |
        // +--------+-----------+-----------+
        // | 1 (B)  |   2 (C)   |   1 (C)   |
        // | 1 (B)  |   1 (B)   |   1 (C)   |
        // | 2 (C)  |   1 (B)   |   1 (B)   |
        // | 2 (C)  |   2 (C)   |   2 (ø)   |
        if ($index < $this->index) {
            $this->index--;
        }
        $this->length--;
        $this->updateState();
    }

    public function assign(self $source): void
    {
        if (count($source) === 0) {
            return;
        }
        foreach ($source as $key => $value) {
            $this[$key] = $value;
        }
    }

    public function keys(): array
    {
        return $this->keys;
    }

    public function values(): array
    {
        return $this->values;
    }

    /**
     * @return list<array{0:mixed, 1:mixed}>
     */
    public function entries(): array
    {
        return array_map(
            static fn(mixed $key, mixed $value): array => [$key, $value],
            $this->keys(),
            $this->values()
        );
    }

    public function count(): int
    {
        return $this->length;
    }

    private function updateState(): void
    {
        $this->end = !($this->index < $this->length);
    }
}

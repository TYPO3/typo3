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

namespace TYPO3\CMS\Core\Schema\Field;

final readonly class FieldCollection implements \ArrayAccess, \IteratorAggregate, \Countable
{
    public function __construct(
        /**
         * @var array<string, FieldTypeInterface> $fieldDefinitions
         */
        protected array $fieldDefinitions = []
    ) {}

    public static function __set_state(array $state): self
    {
        return new self(...$state);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->fieldDefinitions[$offset]);
    }

    public function offsetGet(mixed $offset): ?FieldTypeInterface
    {
        return $this->fieldDefinitions[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \InvalidArgumentException('Fields cannot be set.', 1712539281);
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \InvalidArgumentException('Fields cannot be unset.', 1712539280);
    }

    public function getNames(): array
    {
        return array_keys($this->fieldDefinitions);
    }

    /**
     * @return \Traversable|FieldTypeInterface[]
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->fieldDefinitions);
    }

    public function count(): int
    {
        return count($this->fieldDefinitions);
    }
}

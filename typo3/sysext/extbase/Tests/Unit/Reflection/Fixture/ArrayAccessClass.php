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

namespace TYPO3\CMS\Extbase\Tests\Unit\Reflection\Fixture;

/**
 * ArrayAccess class for the Reflection tests
 */
class ArrayAccessClass implements \ArrayAccess
{
    public function __construct(protected array $array)
    {
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->array);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->array[$offset];
    }

    /**
     * @param int|string $offset
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->array[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->array[$offset]);
    }

    public function getVirtual(): mixed
    {
        return $this->array['virtual'] ?? 'default-value';
    }
}

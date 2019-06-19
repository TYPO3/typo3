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
 * The BitSet class is a helper class to manage bit sets. It eases the work with bits and bitwise
 * operations by providing a reliable and tested API.
 *
 * The class can be used standalone or as a parent for more verbose classes that handle bit sets.
 *
 *
 * The functionality is best described by an example:
 *
 * define('PERMISSIONS_NONE', 0b0); // 0
 * define('PERMISSIONS_PAGE_SHOW', 0b1); // 1
 * define('PERMISSIONS_PAGE_EDIT', 0b10); // 2
 * define('PERMISSIONS_PAGE_DELETE', 0b100); // 4
 *
 * $bitSet = new \TYPO3\CMS\Core\Type\BitSet(PERMISSIONS_PAGE_SHOW | PERMISSIONS_PAGE_EDIT);
 * $bitSet->get(PERMISSIONS_PAGE_SHOW); // true
 * $bitSet->get(PERMISSIONS_PAGE_DELETE); // false
 *
 * Another example shows how to possibly extend the class:
 *
 * class Permissions extends \TYPO3\CMS\Core\Type\BitSet
 * {
 *     public const NONE = 0b0; // 0
 *     public const PAGE_SHOW = 0b1; // 1
 *
 *     public function isGranted(int $permission): bool
 *     {
 *         return $this->get($permission);
 *     }
 *
 *     public function grant(int $permission): void
 *     {
 *         $this->set($permission);
 *     }
 * }
 *
 * $permissions = new Permissions();
 * $permissions->isGranted(Permissions::PAGE_SHOW); // false
 * $permissions->grant(Permissions::PAGE_SHOW);
 * $permissions->isGranted(Permissions::PAGE_SHOW); // true
 */
class BitSet
{
    /**
     * @var int
     */
    protected $set;

    /**
     * @param int $set
     */
    public function __construct(int $set = 0)
    {
        $this->set = $set;
    }

    /**
     * Performs the same operation as {@see or()} without the need to create a BitSet instance from
     * an integer value.
     *
     * @param int $bitIndex
     */
    public function set(int $bitIndex): void
    {
        $this->set |= $bitIndex;
    }

    /**
     * @param int $bitIndex
     * @param bool $value
     */
    public function setValue(int $bitIndex, bool $value): void
    {
        if ($value) {
            $this->set($bitIndex);
        } else {
            $this->unset($bitIndex);
        }
    }

    /**
     * Performs the same operation as {@see andNot()} without the need to create a BitSet instance from
     * an integer value.
     *
     * @param int $bitIndex
     */
    public function unset(int $bitIndex): void
    {
        $this->set &= ~$bitIndex;
    }

    /**
     * @param int $bitIndex
     * @return bool
     */
    public function get(int $bitIndex): bool
    {
        return ($bitIndex & $this->set) === $bitIndex;
    }

    /**
     * Sets all of the bits in this BitSet to false.
     */
    public function clear(): void
    {
        $this->set = 0;
    }

    /**
     * Performs a logical AND of this target bit set with the argument bit set. This bit set is
     * modified so that each bit in it has the value true if and only if it both initially had the
     * value true and the corresponding bit in the bit set argument also had the value true.
     *
     * @param BitSet $set
     */
    public function and(BitSet $set): void
    {
        $this->set &= $set->__toInt();
    }

    /**
     * Performs a logical OR of this bit set with the bit set argument. This bit set is modified so
     * that a bit in it has the value true if and only if it either already had the value true or
     * the corresponding bit in the bit set argument has the value true.
     *
     * @param BitSet $set
     */
    public function or(BitSet $set): void
    {
        $this->set |= $set->__toInt();
    }

    /**
     * Performs a logical XOR of this bit set with the bit set argument. This bit set is modified so
     * that a bit in it has the value true if and only if one of the following statements holds:
     *
     * - The bit initially has the value true, and the corresponding bit
     *   in the argument has the value false.
     * - The bit initially has the value false, and the corresponding bit
     *   in the argument has the value true.
     *
     * @param BitSet $set
     */
    public function xor(BitSet $set): void
    {
        $this->set ^= $set->__toInt();
    }

    /**
     * Clears all of the bits in this BitSet whose corresponding bit is set in the specified BitSet.
     *
     * @param BitSet $set
     */
    public function andNot(BitSet $set): void
    {
        $this->set &= ~$set->__toInt();
    }

    /**
     * Returns the integer representation of the internal set.
     * (As PHP does not know a byte type, the internal set is already handled as an integer and can
     * therefore directly be returned)
     *
     * @return int
     */
    public function __toInt(): int
    {
        return $this->set;
    }

    /**
     * Returns the (binary) string representation of the internal (integer) set.
     *
     * @return string
     */
    public function __toString(): string
    {
        return '0b' . decbin($this->set);
    }
}

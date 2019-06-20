<?php
declare(strict_types = 1);

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
    private $set;

    /**
     * @param int $set
     */
    public function __construct(int $set = 0)
    {
        $this->set = $set;
    }

    /**
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
}

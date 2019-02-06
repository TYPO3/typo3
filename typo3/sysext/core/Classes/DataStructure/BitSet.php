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

namespace TYPO3\CMS\Core\DataStructure;

/**
 * Class TYPO3\CMS\Core\DataStructure\BitSet
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

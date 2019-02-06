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

namespace TYPO3\CMS\Core\Tests\Unit\DataStructure;

use TYPO3\CMS\Core\DataStructure\BitSet;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Class TYPO3\CMS\Core\Tests\Unit\DataStructure\BitSetTest
 */
class BitSetTest extends UnitTestCase
{
    /**
     * @test
     */
    public function defaultBitSetOnlyHasZeroByteSet(): void
    {
        $bitSet = new BitSet();
        static::assertTrue($bitSet->get(0b0));
        static::assertFalse($bitSet->get(0b1));
    }

    /**
     * @test
     */
    public function constructorSetsInternalSet(): void
    {
        $bitSet = new BitSet(0b1 | 0b100);
        static::assertTrue($bitSet->get(0b1));
        static::assertTrue($bitSet->get(0b100));
        static::assertFalse($bitSet->get(0b10));
    }

    /**
     * @test
     */
    public function setSetsBit(): void
    {
        $bitSet = new BitSet(0b101);
        static::assertTrue($bitSet->get(0b1));
        static::assertTrue($bitSet->get(0b100));
        static::assertFalse($bitSet->get(0b10));

        $bitSet->set(0b10);
        static::assertTrue($bitSet->get(0b10));
    }

    /**
     * @test
     */
    public function setValueSetsBit(): void
    {
        $bitSet = new BitSet();
        static::assertFalse($bitSet->get(0b1));

        $bitSet->setValue(0b1, true);
        static::assertTrue($bitSet->get(0b1));

        $bitSet->setValue(0b1, false);
        static::assertFalse($bitSet->get(0b1));
    }

    /**
     * @test
     */
    public function clearUnsetsBit(): void
    {
        $bitSet = new BitSet(0b111);
        $bitSet->unset(0b10);
        static::assertTrue($bitSet->get(0b1));
        static::assertTrue($bitSet->get(0b100));
        static::assertFalse($bitSet->get(0b10));
    }
}

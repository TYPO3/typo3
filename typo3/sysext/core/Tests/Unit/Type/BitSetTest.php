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

namespace TYPO3\CMS\Core\Tests\Unit\Type;

use TYPO3\CMS\Core\Type\BitSet;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case.
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

    /**
     * @test
     */
    public function andPerformsABinaryAnd(): void
    {
        $bitSet = new BitSet(0b101);
        $bitSet->and(new BitSet(0b111));

        // 0b101 & 0b111 === 0b101 ≙ 5
        static::assertSame(5, $bitSet->__toInt());
        static::assertSame('0b101', $bitSet->__toString());
    }

    /**
     * @test
     */
    public function orPerformsABinaryOr(): void
    {
        $bitSet = new BitSet(0b101);
        $bitSet->or(new BitSet(0b011));

        // 0b101 | 0b011 === 0b111 ≙ 7
        static::assertSame(7, $bitSet->__toInt());
        static::assertSame('0b111', $bitSet->__toString());
    }

    /**
     * @test
     */
    public function xorPerformsABinaryXor(): void
    {
        $bitSet = new BitSet(0b1001);
        $bitSet->xor(new BitSet(0b1010));

        // 0b1001 ^ 0b1010 === 0b11 ≙ 3
        static::assertSame(3, $bitSet->__toInt());
        static::assertSame('0b11', $bitSet->__toString());
    }

    /**
     * @test
     */
    public function andNotPerformsABinaryAndNot(): void
    {
        $bitSet = new BitSet(0b111);
        $bitSet->andNot(new BitSet(0b101));

        // 0b111 & ~0b101 === 0b10 ≙ 2
        static::assertSame(2, $bitSet->__toInt());
        static::assertSame('0b10', $bitSet->__toString());
    }

    /**
     * @test
     */
    public function __toIntReturnsIntegerRepresentationOfBitSet()
    {
        $bitSet = new BitSet(0b010);
        static::assertSame(2, $bitSet->__toInt());
    }

    /**
     * @test
     */
    public function __toStringReturnsBinaryStringRepresentationOfBitSet()
    {
        $bitSet = new BitSet(13);
        static::assertSame('0b1101', $bitSet->__toString());
    }
}

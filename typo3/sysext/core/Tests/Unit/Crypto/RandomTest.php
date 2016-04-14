<?php
declare (strict_types=1);
namespace TYPO3\CMS\Core\Tests\Unit\Crypto;

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

use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case
 */
class RandomTest extends UnitTestCase
{
    /**
     * @test
     */
    public function generateRandomBytesReturnsExpectedAmountOfBytes()
    {
        $subject = new Random();
        $this->assertEquals(4, strlen($subject->generateRandomBytes(4)));
    }

    /**
     * Data provider for generateRandomHexStringReturnsExpectedAmountOfChars
     *
     * @return array
     */
    public function generateRandomHexStringReturnsExpectedAmountOfCharsDataProvider()
    {
        return [
            [1],
            [2],
            [3],
            [4],
            [7],
            [8],
            [31],
            [32],
            [100],
            [102],
            [4000],
            [4095],
            [4096],
            [4097],
            [8000]
        ];
    }

    /**
     * @test
     * @dataProvider generateRandomHexStringReturnsExpectedAmountOfCharsDataProvider
     * @param int $numberOfChars Number of Chars to generate
     */
    public function generateRandomHexStringReturnsExpectedAmountOfChars($numberOfChars)
    {
        $subject = new Random();
        $this->assertEquals($numberOfChars, strlen($subject->generateRandomHexString($numberOfChars)));
    }
}

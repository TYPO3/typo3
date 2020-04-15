<?php

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

namespace TYPO3\CMS\Core\Tests\Unit\Log;

use Psr\Log\InvalidArgumentException;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class LogLevelTest extends UnitTestCase
{
    /**
     * @test
     */
    public function isValidLevelValidatesValidLevels()
    {
        $validLevels = [0, 1, 2, 3, 4, 5, 6, 7];
        foreach ($validLevels as $validLevel) {
            self::assertTrue(LogLevel::isValidLevel($validLevel));
        }
    }

    /**
     * @test
     */
    public function isValidLevelDoesNotValidateInvalidLevels()
    {
        $invalidLevels = [-1, 8];
        foreach ($invalidLevels as $invalidLevel) {
            self::assertFalse(LogLevel::isValidLevel($invalidLevel));
        }
    }

    /**
     * Data provider or isValidLevelThrowsExceptionOnInvalidLevelIfAskedToDoSo
     */
    public function isValidLevelThrowsExceptionOnInvalidLevelIfAskedToDoSoDataProvider()
    {
        return [
            'negative integer' => [-1],
            'higher level than expected' => [8],
        ];
    }

    /**
     * @test
     * @dataProvider isValidLevelThrowsExceptionOnInvalidLevelIfAskedToDoSoDataProvider
     */
    public function isValidLevelThrowsExceptionOnInvalidLevelIfAskedToDoSo($inputValue)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1321637121);

        LogLevel::validateLevel($inputValue);
    }

    /**
     * @test
     */
    public function normalizeLevelConvertsValidLevelFromStringToInteger()
    {
        self::assertEquals(7, LogLevel::normalizeLevel('debug'));
    }
}

<?php
namespace TYPO3\CMS\Core\Tests\Unit\Log;

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

/**
 * Test case
 */
class LogLevelTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function isValidLevelValidatesValidLevels()
    {
        $validLevels = [0, 1, 2, 3, 4, 5, 6, 7];
        foreach ($validLevels as $validLevel) {
            $this->assertTrue(\TYPO3\CMS\Core\Log\LogLevel::isValidLevel($validLevel));
        }
    }

    /**
     * @test
     */
    public function isValidLevelDoesNotValidateInvalidLevels()
    {
        $invalidLevels = [-1, 8, 1.5, 'string', [], new \stdClass(), false, null];
        foreach ($invalidLevels as $invalidLevel) {
            $this->assertFalse(\TYPO3\CMS\Core\Log\LogLevel::isValidLevel($invalidLevel));
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
            'float' => [1.5],
            'string' => ['string'],
            'array' => [[]],
            'object' => [new \stdClass()],
            'boolean FALSE' => [false],
            'NULL' => [null]
        ];
    }

    /**
     * @test
     * @dataProvider isValidLevelThrowsExceptionOnInvalidLevelIfAskedToDoSoDataProvider
     * @expectedException \Psr\Log\InvalidArgumentException
     */
    public function isValidLevelThrowsExceptionOnInvalidLevelIfAskedToDoSo($inputValue)
    {
        \TYPO3\CMS\Core\Log\LogLevel::validateLevel($inputValue);
    }

    /**
     * @test
     */
    public function normalizeLevelConvertsValidLevelFromStringToInteger()
    {
        $this->assertEquals(7, \TYPO3\CMS\Core\Log\LogLevel::normalizeLevel('debug'));
    }

    /**
     * @test
     */
    public function normalizeLevelDoesNotConvertInvalidLevel()
    {
        $levelString = 'invalid';
        $this->assertEquals($levelString, \TYPO3\CMS\Core\Log\LogLevel::normalizeLevel($levelString));
    }
}

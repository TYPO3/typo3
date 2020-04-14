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

namespace TYPO3\CMS\Core\Tests\UnitDeprecated\Utility;

use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for class \TYPO3\CMS\Core\Utility\VersionNumberUtility
 */
class VersionNumberUtilityTest extends UnitTestCase
{

    /**
     * Data Provider for convertVersionNumberToIntegerConvertsVersionNumbersToIntegers
     *
     * @return array
     */
    public function validVersionNumberDataProvider()
    {
        return [
            ['4003003', '4.3.3'],
            ['4012003', '4.12.3'],
            ['5000000', '5.0.0'],
            ['5000001', '5.0.1'],
            ['3008001', '3.8.1'],
            ['1012', '0.1.12']
        ];
    }

    /**
     * Data Provider for convertIntegerToVersionNumberConvertsOtherTypesAsIntegerToVersionNumber
     *
     * @see http://php.net/manual/en/language.types.php
     * @return array
     */
    public function invalidVersionNumberDataProvider()
    {
        return [
            'boolean' => [true],
            'float' => [5.4],
            'array' => [[]],
            'string' => ['300ABCD'],
            'object' => [new \stdClass()],
            'NULL' => [null],
            'function' => [function () {
            }]
        ];
    }

    /**
     * @test
     * @dataProvider validVersionNumberDataProvider
     */
    public function convertVersionNumberToIntegerConvertsVersionNumbersToIntegers($expected, $version)
    {
        self::assertEquals($expected, VersionNumberUtility::convertVersionNumberToInteger($version));
    }

    /**
     * @test
     * @dataProvider validVersionNumberDataProvider
     */
    public function convertIntegerToVersionNumberConvertsIntegerToVersionNumber($versionNumber, $expected)
    {
        // Make sure incoming value is an integer
        $versionNumber = (int)$versionNumber;
        self::assertEquals($expected, VersionNumberUtility::convertIntegerToVersionNumber($versionNumber));
    }

    /**
     * @test
     * @dataProvider invalidVersionNumberDataProvider
     */
    public function convertIntegerToVersionNumberConvertsOtherTypesAsIntegerToVersionNumber($version)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1334072223);
        VersionNumberUtility::convertIntegerToVersionNumber($version);
    }
}

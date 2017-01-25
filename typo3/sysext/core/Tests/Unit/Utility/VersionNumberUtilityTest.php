<?php
namespace TYPO3\CMS\Core\Tests\Unit\Utility;

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

use TYPO3\CMS\Core\Tests\Unit\Utility\Fixtures\VersionNumberUtilityFixture;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

/**
 * Testcase for class \TYPO3\CMS\Core\Utility\VersionNumberUtility
 */
class VersionNumberUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
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
        $this->assertEquals($expected, VersionNumberUtility::convertVersionNumberToInteger($version));
    }

    /**
     * @test
     * @dataProvider validVersionNumberDataProvider
     */
    public function convertIntegerToVersionNumberConvertsIntegerToVersionNumber($versionNumber, $expected)
    {
        // Make sure incoming value is an integer
        $versionNumber = (int)$versionNumber;
        $this->assertEquals($expected, VersionNumberUtility::convertIntegerToVersionNumber($versionNumber));
    }

    /**
     * @test
     * @dataProvider invalidVersionNumberDataProvider
     */
    public function convertIntegerToVersionNumberConvertsOtherTypesAsIntegerToVersionNumber($version)
    {
        $this->setExpectedException('\\InvalidArgumentException', '', 1334072223);
        VersionNumberUtility::convertIntegerToVersionNumber($version);
    }

    /**
     * @return array
     */
    public function getNumericTypo3VersionNumberDataProvider()
    {
        return [
            [
                '6.0-dev',
                '6.0.0'
            ],
            [
                '4.5-alpha',
                '4.5.0'
            ],
            [
                '4.5-beta',
                '4.5.0'
            ],
            [
                '4.5-RC',
                '4.5.0'
            ],
            [
                '6.0.1',
                '6.0.1'
            ],
            [
                '6.2.0beta5',
                '6.2.0'
            ],
        ];
    }

    /**
     * Check whether getNumericTypo3Version handles all kinds of valid
     * version strings
     *
     * @dataProvider getNumericTypo3VersionNumberDataProvider
     * @test
     * @param string $currentVersion
     * @param string $expectedVersion
     */
    public function getNumericTypo3VersionNumber($currentVersion, $expectedVersion)
    {
        VersionNumberUtilityFixture::$versionNumber = $currentVersion;
        $this->assertEquals($expectedVersion, VersionNumberUtilityFixture::getNumericTypo3Version());
    }

    /**
     * Data provider for convertVersionsStringToVersionNumbersForcesVersionNumberInRange
     *
     * @return array
     */
    public function convertVersionsStringToVersionNumbersForcesVersionNumberInRangeDataProvider()
    {
        return [
            'everything ok' => [
                '4.2.0-4.4.99',
                [
                    '4.2.0',
                    '4.4.99'
                ]
            ],
            'too high value' => [
                '4.2.0-4.4.2990',
                [
                    '4.2.0',
                    '4.4.999'
                ]
            ],
            'empty high value' => [
                '4.2.0-0.0.0',
                [
                    '4.2.0',
                    ''
                ]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider convertVersionsStringToVersionNumbersForcesVersionNumberInRangeDataProvider
     */
    public function convertVersionsStringToVersionNumbersForcesVersionNumberInRange($versionString, $expectedResult)
    {
        $versions = VersionNumberUtility::convertVersionsStringToVersionNumbers($versionString);
        $this->assertEquals($expectedResult, $versions);
    }
}

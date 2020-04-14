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

namespace TYPO3\CMS\Core\Tests\Unit\Utility;

use TYPO3\CMS\Core\Tests\Unit\Utility\Fixtures\VersionNumberUtilityFixture;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for class \TYPO3\CMS\Core\Utility\VersionNumberUtility
 */
class VersionNumberUtilityTest extends UnitTestCase
{

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
        self::assertEquals($expectedVersion, VersionNumberUtilityFixture::getNumericTypo3Version());
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
        self::assertEquals($expectedResult, $versions);
    }
}

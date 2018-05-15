<?php
namespace TYPO3\CMS\Backend\Tests\UnitDeprecated\Utility;

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

use TYPO3\CMS\Backend\Tests\UnitDeprecated\Utility\Fixtures\BackendUtilityFixture;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class BackendUtilityTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getModTSconfigIgnoresValuesFromUserTsConfigIfNotSet()
    {
        $completeConfiguration = [
            'value' => 'bar',
            'properties' => [
                'permissions.' => [
                    'file.' => [
                        'default.' => ['readAction' => '1'],
                        '1.' => ['writeAction' => '1'],
                        '0.' => ['readAction' => '0'],
                    ],
                ]
            ]
        ];

        $GLOBALS['BE_USER'] = $this->createMock(BackendUserAuthentication::class);
        $GLOBALS['BE_USER']->expects($this->at(0))->method('getTSConfig')->will($this->returnValue($completeConfiguration));
        $GLOBALS['BE_USER']->expects($this->at(1))->method('getTSConfig')->will($this->returnValue(['value' => null, 'properties' => null]));

        $this->assertSame($completeConfiguration, BackendUtilityFixture::getModTSconfig(42, 'notrelevant'));
    }
}

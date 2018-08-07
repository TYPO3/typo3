<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Adminpanel\Tests\Unit\Utility;

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

use TYPO3\CMS\Adminpanel\Utility\StateUtility;
use TYPO3\CMS\Backend\FrontendBackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class StateUtilityTest extends UnitTestCase
{

    /**
     * @test
     */
    public function isEnabledReturnsFalseIfNoBackendUserExists(): void
    {
        $GLOBALS['BE_USER'] = false;
        $isEnabled = StateUtility::isActivated();
        self::assertFalse($isEnabled);
    }

    /**
     * @test
     */
    public function isEnabledReturnsFalseIfNoBackendUserInFrontendContextIsLoggedIn(): void
    {
        $GLOBALS['BE_USER'] = $this->prophesize(BackendUserAuthentication::class)->reveal();
        $isEnabled = StateUtility::isActivated();
        self::assertFalse($isEnabled);
    }

    public function tsConfigEnabledDataProvider(): array
    {
        return [
            '1 module enabled' => [
                [
                    'admPanel.' => [
                        'enable.' => [
                            'preview' => 1
                        ]
                    ]
                ]
            ],
            'all modules enabled' => [
                [
                    'admPanel.' => [
                        'enable.' => [
                            'all' => 1
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider tsConfigEnabledDataProvider
     * @param array $tsConfig
     */
    public function isEnabledReturnsTrueIfAtLeastOneModuleIsEnabled(array $tsConfig): void
    {
        $beUserProphecy = $this->prophesize(FrontendBackendUserAuthentication::class);
        $beUserProphecy->getTSConfig()->willReturn($tsConfig);
        $GLOBALS['BE_USER'] = $beUserProphecy->reveal();
        $isEnabled = StateUtility::isActivated();
        self::assertTrue($isEnabled);
    }

    public function tsConfigDisabledDataProvider(): array
    {
        return [
            'no config set' => [
                []
            ],
            'all modules disabled' => [
                'admPanel.' => [
                    'enable.' => [
                        'all' => 0
                    ]
                ]
            ],
            'single module configured, disabled' => [
                'admPanel.' => [
                    'enable.' => [
                        'preview' => 0
                    ]
                ]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider tsConfigDisabledDataProvider
     * @param array $tsConfig
     */
    public function isEnabledReturnsFalseIfNoModulesEnabled(array $tsConfig): void
    {
        $beUserProphecy = $this->prophesize(FrontendBackendUserAuthentication::class);
        $beUserProphecy->getTSConfig()->willReturn($tsConfig);
        $GLOBALS['BE_USER'] = $beUserProphecy->reveal();
        $isEnabled = StateUtility::isActivated();
        self::assertFalse($isEnabled);
    }
}

<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Adminpanel\Tests\Unit\Utility;

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
        $isEnabled = StateUtility::isActivatedForUser();
        self::assertFalse($isEnabled);
    }

    /**
     * @test
     */
    public function isEnabledReturnsFalseIfNoBackendUserInFrontendContextIsLoggedIn(): void
    {
        $GLOBALS['BE_USER'] = $this->prophesize(BackendUserAuthentication::class)->reveal();
        $isEnabled = StateUtility::isActivatedForUser();
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
        $isEnabled = StateUtility::isActivatedForUser();
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
        $isEnabled = StateUtility::isActivatedForUser();
        self::assertFalse($isEnabled);
    }

    public function tsConfigHideDataProvider(): array
    {
        return [
            'no config set' => [
                [],
                false
            ],
            'defined as not hidden' => [
                [
                    'admPanel.' => [
                        'hide' => '0'
                    ]
                ],
                false
            ],
            'defined as hidden' => [
                [
                    'admPanel.' => [
                        'hide' => '1'
                    ]
                ],
                true
            ]
        ];
    }

    /**
     * @test
     * @dataProvider tsConfigHideDataProvider
     * @param array $tsConfig
     * @param bool $expected
     */
    public function isHiddenForUserReturnsCorrectValue(array $tsConfig, bool $expected): void
    {
        $beUserProphecy = $this->prophesize(FrontendBackendUserAuthentication::class);
        $beUserProphecy->getTSConfig()->willReturn($tsConfig);
        $GLOBALS['BE_USER'] = $beUserProphecy->reveal();
        $isEnabled = StateUtility::isHiddenForUser();
        self::assertSame($expected, $isEnabled);
    }

    /**
     * @test
     */
    public function isHiddenForUserReturnsFalseIfUserIsNotAvailable(): void
    {
        $GLOBALS['BE_USER'] = null;
        $isEnabled = StateUtility::isHiddenForUser();
        self::assertFalse($isEnabled);
    }

    public function ucDisplayOpenDataProvider(): array
    {
        return [
            'no config set' => [
                [],
                false
            ],
            'defined as display_top=false' => [
                [
                    'AdminPanel' => [
                        'display_top' => false
                    ]
                ],
                false
            ],
            'defined as display_top=true' => [
                [
                    'AdminPanel' => [
                        'display_top' => true
                    ]
                ],
                true
            ],
        ];
    }

    /**
     * @test
     * @dataProvider ucDisplayOpenDataProvider
     * @param array $uc
     * @param bool $expected
     */
    public function isOpenForUserReturnsCorrectValue(array $uc, bool $expected): void
    {
        $beUser = new FrontendBackendUserAuthentication();
        $beUser->uc = $uc;
        $GLOBALS['BE_USER'] = $beUser;
        $isOpen = StateUtility::isOpen();
        self::assertSame($expected, $isOpen);
    }

    /**
     * @test
     */
    public function isOpenForUserReturnsFalseIfUserIsNotAvailable(): void
    {
        $GLOBALS['BE_USER'] = null;
        $isOpen = StateUtility::isOpen();
        self::assertFalse($isOpen);
    }

    public function typoScriptDataProvider(): array
    {
        return [
            'no config set' => [
                [],
                false
            ],
            'Admin Panel is disabled' => [
                [
                    'config' => [
                        'admPanel' => '0'
                    ]
                ],
                false
            ],
            'Admin Panel is enabled' => [
                [
                    'config' => [
                        'admPanel' => '1'
                    ]
                ],
                true
            ],
        ];
    }

    /**
     * @test
     * @dataProvider typoScriptDataProvider
     * @param array $typoScript
     * @param bool $expected
     */
    public function isActivatedInTypoScriptReturnsCorrectValue(array $typoScript, bool $expected): void
    {
        $tsfe = new \stdClass();
        $tsfe->config = $typoScript;
        $GLOBALS['TSFE'] = $tsfe;
        $isEnabled = StateUtility::isActivatedInTypoScript();
        self::assertSame($expected, $isEnabled);
    }
}

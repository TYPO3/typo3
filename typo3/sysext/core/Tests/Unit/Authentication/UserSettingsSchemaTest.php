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

namespace TYPO3\CMS\Core\Tests\Unit\Authentication;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\UserSettingsSchema;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class UserSettingsSchemaTest extends UnitTestCase
{
    protected function tearDown(): void
    {
        unset($GLOBALS['TYPO3_USER_SETTINGS']);
        unset($GLOBALS['TCA']['be_users']['columns']['user_settings']);
        parent::tearDown();
    }

    #[Test]
    public function getColumnsReturnsColumnsFromLegacyGlobal(): void
    {
        $GLOBALS['TYPO3_USER_SETTINGS'] = [
            'columns' => [
                'colorScheme' => ['type' => 'select', 'label' => 'Color'],
                'titleLen' => ['type' => 'number', 'label' => 'Title Length'],
            ],
        ];

        $schema = new UserSettingsSchema();
        $columns = $schema->getColumns();

        self::assertArrayHasKey('colorScheme', $columns);
        self::assertArrayHasKey('titleLen', $columns);
        self::assertSame('select', $columns['colorScheme']['type']);
    }

    #[Test]
    public function getColumnsReturnsColumnsFromTca(): void
    {
        $GLOBALS['TCA']['be_users']['columns']['user_settings'] = [
            'columns' => [
                'colorScheme' => [
                    'label' => 'Color Scheme',
                    'config' => ['type' => 'select', 'renderType' => 'selectSingle'],
                ],
            ],
        ];

        $schema = new UserSettingsSchema();
        $columns = $schema->getColumns();

        self::assertArrayHasKey('colorScheme', $columns);
        self::assertSame('select', $columns['colorScheme']['type']);
        self::assertSame('Color Scheme', $columns['colorScheme']['label']);
    }

    #[Test]
    public function getColumnsMergesTcaAndLegacyPreferringTca(): void
    {
        $GLOBALS['TCA']['be_users']['columns']['user_settings'] = [
            'columns' => [
                'colorScheme' => [
                    'label' => 'TCA Label',
                    'config' => ['type' => 'select', 'renderType' => 'selectSingle'],
                ],
            ],
        ];
        $GLOBALS['TYPO3_USER_SETTINGS'] = [
            'columns' => [
                'colorScheme' => ['type' => 'select', 'label' => 'Legacy Label'],
                'thirdPartyField' => ['type' => 'check', 'label' => 'Third Party'],
            ],
        ];

        $schema = new UserSettingsSchema();
        $columns = $schema->getColumns();

        // TCA takes precedence
        self::assertSame('TCA Label', $columns['colorScheme']['label']);
        // Legacy third-party fields are included
        self::assertArrayHasKey('thirdPartyField', $columns);
        self::assertSame('Third Party', $columns['thirdPartyField']['label']);
    }

    #[Test]
    public function getColumnReturnsConfigFromTca(): void
    {
        $GLOBALS['TCA']['be_users']['columns']['user_settings'] = [
            'columns' => [
                'emailMeAtLogin' => [
                    'label' => 'Email Me',
                    'config' => ['type' => 'check', 'renderType' => 'checkboxToggle'],
                ],
            ],
        ];

        $schema = new UserSettingsSchema();
        $config = $schema->getColumn('emailMeAtLogin');

        self::assertSame('check', $config['type']);
        self::assertSame('Email Me', $config['label']);
    }

    #[Test]
    public function getColumnReturnsNullForUnknownField(): void
    {
        $GLOBALS['TCA']['be_users']['columns']['user_settings'] = ['columns' => []];
        $GLOBALS['TYPO3_USER_SETTINGS'] = ['columns' => []];

        $schema = new UserSettingsSchema();
        self::assertNull($schema->getColumn('unknownField'));
    }

    #[Test]
    public function getShowitemReturnsTcaShowitem(): void
    {
        $GLOBALS['TCA']['be_users']['columns']['user_settings'] = [
            'showitem' => '--div--;Tab1,field1,field2',
        ];

        $schema = new UserSettingsSchema();
        self::assertSame('--div--;Tab1,field1,field2', $schema->getShowitem());
    }

    #[Test]
    public function getShowitemMergesTcaAndLegacy(): void
    {
        $GLOBALS['TCA']['be_users']['columns']['user_settings'] = [
            'showitem' => '--div--;Tab1,field1',
        ];
        $GLOBALS['TYPO3_USER_SETTINGS'] = [
            'showitem' => 'thirdPartyField',
        ];

        $schema = new UserSettingsSchema();
        self::assertSame('--div--;Tab1,field1,thirdPartyField', $schema->getShowitem());
    }

    #[Test]
    public function inheritFromParentMergesWithBeUsersColumn(): void
    {
        // Set up parent be_users TCA column
        $GLOBALS['TCA']['be_users']['columns']['realName'] = [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.realName',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'max' => 80,
                'eval' => 'trim',
            ],
        ];

        $GLOBALS['TCA']['be_users']['columns']['user_settings'] = [
            'columns' => [
                'realName' => [
                    'inheritFromParent' => true,
                ],
            ],
        ];

        $schema = new UserSettingsSchema();
        $config = $schema->getColumn('realName');

        self::assertSame('text', $config['type']);
        self::assertSame('be_users', $config['table']);
        self::assertSame(80, $config['max']);
    }

    #[Test]
    public function inheritFromParentAllowsLabelOverride(): void
    {
        $GLOBALS['TCA']['be_users']['columns']['password'] = [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.password',
            'config' => [
                'type' => 'password',
            ],
        ];

        $GLOBALS['TCA']['be_users']['columns']['user_settings'] = [
            'columns' => [
                'password' => [
                    'inheritFromParent' => true,
                    'label' => 'setup.messages:newPassword',
                ],
            ],
        ];

        $schema = new UserSettingsSchema();
        $config = $schema->getColumn('password');

        self::assertSame('password', $config['type']);
        self::assertSame('setup.messages:newPassword', $config['label']);
        self::assertSame('be_users', $config['table']);
    }

    #[Test]
    public function getJsonFieldSettingKeysExcludesBeUsersTableFields(): void
    {
        $GLOBALS['TCA']['be_users']['columns']['realName'] = [
            'config' => ['type' => 'input'],
        ];
        $GLOBALS['TCA']['be_users']['columns']['user_settings'] = [
            'columns' => [
                'colorScheme' => [
                    'label' => 'Color',
                    'config' => ['type' => 'select', 'renderType' => 'selectSingle'],
                ],
                'realName' => [
                    'inheritFromParent' => true,
                ],
            ],
        ];

        $schema = new UserSettingsSchema();
        $keys = $schema->getJsonFieldSettingKeys();

        self::assertContains('colorScheme', $keys);
        self::assertNotContains('realName', $keys);
    }

    #[Test]
    public function getJsonFieldSettingKeysExcludesButtonAndMfaTypes(): void
    {
        $GLOBALS['TCA']['be_users']['columns']['user_settings'] = [
            'columns' => [
                'colorScheme' => [
                    'label' => 'Color',
                    'config' => ['type' => 'select', 'renderType' => 'selectSingle'],
                ],
                'resetConfiguration' => [
                    'label' => 'Reset',
                    'config' => ['type' => 'button'],
                ],
                'mfaProviders' => [
                    'label' => 'MFA',
                    'config' => ['type' => 'mfa'],
                ],
            ],
        ];

        $schema = new UserSettingsSchema();
        $keys = $schema->getJsonFieldSettingKeys();

        self::assertContains('colorScheme', $keys);
        self::assertNotContains('resetConfiguration', $keys);
        self::assertNotContains('mfaProviders', $keys);
    }

    #[Test]
    public function getDbColumnSettingKeysReturnsBeUsersTableFields(): void
    {
        $GLOBALS['TCA']['be_users']['columns']['email'] = [
            'config' => ['type' => 'email'],
        ];
        $GLOBALS['TCA']['be_users']['columns']['lang'] = [
            'config' => ['type' => 'language'],
        ];
        $GLOBALS['TCA']['be_users']['columns']['password'] = [
            'config' => ['type' => 'password'],
        ];
        $GLOBALS['TCA']['be_users']['columns']['user_settings'] = [
            'columns' => [
                'colorScheme' => [
                    'label' => 'Color',
                    'config' => ['type' => 'select', 'renderType' => 'selectSingle'],
                ],
                'email' => [
                    'inheritFromParent' => true,
                ],
                'lang' => [
                    'inheritFromParent' => true,
                ],
                'password' => [
                    'inheritFromParent' => true,
                    'label' => 'New Password',
                ],
            ],
        ];

        $schema = new UserSettingsSchema();
        $keys = $schema->getDbColumnSettingKeys();

        self::assertNotContains('colorScheme', $keys);
        self::assertContains('email', $keys);
        self::assertContains('lang', $keys);
        // password type is excluded
        self::assertNotContains('password', $keys);
    }

    #[Test]
    public function getDefaultReturnsValueFromTcaConfigDefault(): void
    {
        $GLOBALS['TCA']['be_users']['columns']['user_settings'] = [
            'columns' => [
                'displayRecentlyUsed' => [
                    'label' => 'Display Recently Used',
                    'config' => ['type' => 'check', 'renderType' => 'checkboxToggle', 'default' => 1],
                ],
            ],
        ];

        $schema = new UserSettingsSchema();
        self::assertSame(1, $schema->getDefault('displayRecentlyUsed'));
    }

    #[Test]
    public function getDefaultReturnsNullForMissingDefault(): void
    {
        $GLOBALS['TCA']['be_users']['columns']['user_settings'] = [
            'columns' => [
                'colorScheme' => [
                    'label' => 'Color',
                    'config' => ['type' => 'select', 'renderType' => 'selectSingle'],
                ],
            ],
        ];

        $schema = new UserSettingsSchema();
        self::assertNull($schema->getDefault('colorScheme'));
    }

    #[Test]
    public function convertsTcaSelectItemsToLegacyFormat(): void
    {
        $GLOBALS['TCA']['be_users']['columns']['user_settings'] = [
            'columns' => [
                'backendTitleFormat' => [
                    'label' => 'Title Format',
                    'config' => [
                        'type' => 'select',
                        'renderType' => 'selectSingle',
                        'items' => [
                            ['label' => 'Title First', 'value' => 'titleFirst'],
                            ['label' => 'Sitename First', 'value' => 'sitenameFirst'],
                        ],
                    ],
                ],
            ],
        ];

        $schema = new UserSettingsSchema();
        $config = $schema->getColumn('backendTitleFormat');

        self::assertSame('select', $config['type']);
        self::assertArrayHasKey('items', $config);
        self::assertSame('Title First', $config['items']['titleFirst']);
        self::assertSame('Sitename First', $config['items']['sitenameFirst']);
    }

    #[Test]
    public function convertsLegacySelectItemsFormat(): void
    {
        $GLOBALS['TCA']['be_users']['columns']['user_settings'] = [
            'columns' => [
                'colorScheme' => [
                    'label' => 'Color Scheme',
                    'config' => [
                        'type' => 'select',
                        'renderType' => 'selectSingle',
                        // Legacy format: value => label
                        'items' => [
                            'auto' => 'Automatic',
                            'light' => 'Light',
                            'dark' => 'Dark',
                        ],
                    ],
                ],
            ],
        ];

        $schema = new UserSettingsSchema();
        $config = $schema->getColumn('colorScheme');

        self::assertSame('select', $config['type']);
        self::assertArrayHasKey('items', $config);
        self::assertSame('Automatic', $config['items']['auto']);
        self::assertSame('Light', $config['items']['light']);
        self::assertSame('Dark', $config['items']['dark']);
    }

    #[Test]
    public function buttonTypeIsConvertedCorrectly(): void
    {
        $GLOBALS['TCA']['be_users']['columns']['user_settings'] = [
            'columns' => [
                'resetConfiguration' => [
                    'label' => 'Reset',
                    'config' => [
                        'type' => 'button',
                        'buttonLabel' => 'Reset Button',
                        'confirm' => true,
                        'confirmData' => ['message' => 'Are you sure?'],
                    ],
                ],
            ],
        ];

        $schema = new UserSettingsSchema();
        $config = $schema->getColumn('resetConfiguration');

        self::assertSame('button', $config['type']);
        self::assertSame('Reset Button', $config['buttonlabel']);
        self::assertTrue($config['confirm']);
        self::assertSame('Are you sure?', $config['confirmData']['message']);
    }
}

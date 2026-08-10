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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\FormDataProvider\UserSettingsDisabledFields;
use TYPO3\CMS\Core\Authentication\UserSettingsSchema;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class UserSettingsDisabledFieldsTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TCA']['be_users']['columns']['password'] = [
            'config' => ['type' => 'password'],
        ];
        $GLOBALS['TCA']['be_users']['columns']['user_settings'] = [
            'columns' => [
                'emailMeAtLogin' => [
                    'config' => ['type' => 'check'],
                ],
                'titleLen' => [
                    'config' => ['type' => 'number'],
                ],
                'password' => [
                    'inheritFromParent' => true,
                ],
                'password2' => [
                    'config' => ['type' => 'password'],
                ],
            ],
        ];
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TCA']['be_users']);
        parent::tearDown();
    }

    #[Test]
    public function addDataKeepsResultUntouchedForOtherTables(): void
    {
        $input = [
            'tableName' => 'tt_content',
            'userTsConfig' => [
                'setup.' => [
                    'fields.' => [
                        'emailMeAtLogin.' => ['disabled' => '1'],
                    ],
                ],
            ],
            'processedTca' => [
                'types' => [
                    '0' => [
                        'showitem' => 'user_settings__emailMeAtLogin,user_settings__titleLen',
                    ],
                ],
            ],
        ];

        self::assertSame($input, new UserSettingsDisabledFields(new UserSettingsSchema())->addData($input));
    }

    #[Test]
    public function addDataRemovesDisabledFieldFromShowitem(): void
    {
        $input = [
            'tableName' => 'be_users_settings',
            'userTsConfig' => [
                'setup.' => [
                    'fields.' => [
                        'emailMeAtLogin.' => ['disabled' => '1'],
                    ],
                ],
            ],
            'processedTca' => [
                'types' => [
                    '0' => [
                        'showitem' => '--div--;aTabLabel,user_settings__emailMeAtLogin,user_settings__titleLen',
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['processedTca']['types']['0']['showitem'] = '--div--;aTabLabel,user_settings__titleLen';

        self::assertSame($expected, new UserSettingsDisabledFields(new UserSettingsSchema())->addData($input));
    }

    #[Test]
    public function addDataRemovesPasswordConfirmationIfPasswordIsDisabled(): void
    {
        $input = [
            'tableName' => 'be_users_settings',
            'userTsConfig' => [
                'setup.' => [
                    'fields.' => [
                        'password.' => ['disabled' => '1'],
                    ],
                ],
            ],
            'processedTca' => [
                'types' => [
                    '0' => [
                        'showitem' => 'be_users__password;aLabel,user_settings__password2,user_settings__titleLen',
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['processedTca']['types']['0']['showitem'] = 'user_settings__titleLen';

        self::assertSame($expected, new UserSettingsDisabledFields(new UserSettingsSchema())->addData($input));
    }

    #[Test]
    public function addDataIgnoresUnknownFieldNames(): void
    {
        $input = [
            'tableName' => 'be_users_settings',
            'userTsConfig' => [
                'setup.' => [
                    'fields.' => [
                        'unknownField.' => ['disabled' => '1'],
                        'anotherField.' => ['nonRelatedOption' => 'foo'],
                    ],
                ],
            ],
            'processedTca' => [
                'types' => [
                    '0' => [
                        'showitem' => 'user_settings__emailMeAtLogin,user_settings__titleLen',
                    ],
                ],
            ],
        ];

        self::assertSame($input, new UserSettingsDisabledFields(new UserSettingsSchema())->addData($input));
    }
}

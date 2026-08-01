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
use TYPO3\CMS\Backend\Form\FormDataProvider\UserSettingsOverrideFields;
use TYPO3\CMS\Core\Authentication\UserSettingsSchema;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class UserSettingsOverrideFieldsTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TCA']['be_users']['columns']['user_settings'] = [
            'columns' => [
                'emailMeAtLogin' => [
                    'config' => ['type' => 'check'],
                ],
                'titleLen' => [
                    'config' => ['type' => 'number'],
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
                    'override.' => [
                        'emailMeAtLogin' => '1',
                    ],
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'user_settings__emailMeAtLogin' => [
                        'config' => ['type' => 'check'],
                    ],
                ],
            ],
        ];

        self::assertSame($input, (new UserSettingsOverrideFields(new UserSettingsSchema()))->addData($input));
    }

    #[Test]
    public function addDataSetsOverriddenFieldReadOnly(): void
    {
        $input = [
            'tableName' => 'be_users_settings',
            'userTsConfig' => [
                'setup.' => [
                    'override.' => [
                        'emailMeAtLogin' => '1',
                    ],
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'user_settings__emailMeAtLogin' => [
                        'config' => ['type' => 'check'],
                    ],
                    'user_settings__titleLen' => [
                        'config' => ['type' => 'number'],
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['processedTca']['columns']['user_settings__emailMeAtLogin']['config']['readOnly'] = true;

        self::assertSame($expected, (new UserSettingsOverrideFields(new UserSettingsSchema()))->addData($input));
    }

    #[Test]
    public function addDataIgnoresUnknownFieldNames(): void
    {
        $input = [
            'tableName' => 'be_users_settings',
            'userTsConfig' => [
                'setup.' => [
                    'override.' => [
                        'unknownField' => '1',
                    ],
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'user_settings__emailMeAtLogin' => [
                        'config' => ['type' => 'check'],
                    ],
                ],
            ],
        ];

        self::assertSame($input, (new UserSettingsOverrideFields(new UserSettingsSchema()))->addData($input));
    }

    #[Test]
    public function addDataIgnoresFieldsMissingInProcessedTca(): void
    {
        $input = [
            'tableName' => 'be_users_settings',
            'userTsConfig' => [
                'setup.' => [
                    'override.' => [
                        'titleLen' => '30',
                    ],
                ],
            ],
            'processedTca' => [
                'columns' => [
                    'user_settings__emailMeAtLogin' => [
                        'config' => ['type' => 'check'],
                    ],
                ],
            ],
        ];

        self::assertSame($input, (new UserSettingsOverrideFields(new UserSettingsSchema()))->addData($input));
    }
}

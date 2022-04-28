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

use TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseRowDefaultAsReadonly;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class DatabaseRowDefaultAsReadonlyTest extends UnitTestCase
{
    /**
     * @test
     */
    public function addDataReplacesCurrentDatabaseValue(): void
    {
        $input = [
            'databaseRow' => [
                'uid' => 10,
                'l10n_parent' => 5,
                'sys_language_uid' => 2,
                'aField' => '',
            ],
            'defaultLanguageRow' => [
                'uid' => 5,
                'l10n_parent' => 0,
                'sys_language_uid' => 0,
                'aField' => 'some-default-value',
            ],
            'processedTca' => [
                'ctrl' => [
                    'transOrigPointerField' => 'l10n_parent',
                    'languageField' => 'sys_language_uid',
                ],
                'columns' => [
                    'aField' => [
                        'label' => 'aField',
                        'l10n_display' => 'defaultAsReadonly',
                    ],
                ],
            ],
        ];

        $expected = $input;
        $expected['databaseRow']['aField'] = $expected['defaultLanguageRow']['aField'];

        self::assertEquals($expected, (new DatabaseRowDefaultAsReadonly())->addData($input));
    }

    /**
     * @test
     * @dataProvider addDataDoesNotReplaceCurrentDatabaseValueDataProvider
     */
    public function addDataDoesNotReplaceCurrentDatabaseValue(array $input): void
    {
        self::assertEquals(
            $input['databaseRow']['aField'],
            (new DatabaseRowDefaultAsReadonly())->addData($input)['databaseRow']['aField']
        );
    }

    public function addDataDoesNotReplaceCurrentDatabaseValueDataProvider(): \Generator
    {
        yield 'No default language row available' => [
            [
                'databaseRow' => [
                    'uid' => 10,
                    'l10n_parent' => 5,
                    'sys_language_uid' => 2,
                    'aField' => 'wont-be-overridden',
                ],
                'processedTca' => [
                    'ctrl' => [
                        'transOrigPointerField' => 'l10n_parent',
                        'languageField' => 'sys_language_uid',
                    ],
                    'columns' => [
                        'aField' => [
                            'label' => 'aField',
                            'l10n_display' => 'defaultAsReadonly',
                        ],
                    ],
                ],
            ],
        ];
        yield 'defaultAsReadonly is not set' => [
            [
                'databaseRow' => [
                    'uid' => 10,
                    'l10n_parent' => 5,
                    'sys_language_uid' => 2,
                    'aField' => 'wont-be-overridden',
                ],
                'defaultLanguageRow' => [
                    'uid' => 5,
                    'l10n_parent' => 0,
                    'sys_language_uid' => 0,
                    'aField' => 'some-default-value',
                ],
                'processedTca' => [
                    'ctrl' => [
                        'transOrigPointerField' => 'l10n_parent',
                        'languageField' => 'sys_language_uid',
                    ],
                    'columns' => [
                        'aField' => [
                            'label' => 'aField',
                        ],
                    ],
                ],
            ],
        ];
        yield 'current record is no overlay' => [
            [
                'databaseRow' => [
                    'uid' => 10,
                    'l10n_parent' => 0,
                    'sys_language_uid' => 2,
                    'aField' => 'wont-be-overridden',
                ],
                'defaultLanguageRow' => [
                    // This case usually can not occur, however since it's possible
                    // for 3rd party to hook in we have to check this case as well.
                    'aField' => 'some-default-value',
                ],
                'processedTca' => [
                    'ctrl' => [
                        'transOrigPointerField' => 'l10n_parent',
                        'languageField' => 'sys_language_uid',
                    ],
                    'columns' => [
                        'aField' => [
                            'label' => 'aField',
                            'l10n_display' => 'defaultAsReadonly',
                        ],
                    ],
                ],
            ],
        ];
        yield 'default row is not the localization parent of the current record' => [
            [
                'databaseRow' => [
                    'uid' => 10,
                    'l10n_parent' => 7,
                    'sys_language_uid' => 2,
                    'aField' => 'wont-be-overridden',
                ],
                'defaultLanguageRow' => [
                    'uid' => 5,
                    'l10n_parent' => 0,
                    'sys_language_uid' => 0,
                    'aField' => 'some-default-value',
                ],
                'processedTca' => [
                    'ctrl' => [
                        'transOrigPointerField' => 'l10n_parent',
                        'languageField' => 'sys_language_uid',
                    ],
                    'columns' => [
                        'aField' => [
                            'label' => 'aField',
                            'l10n_display' => 'defaultAsReadonly',
                        ],
                    ],
                ],
            ],
        ];
    }
}

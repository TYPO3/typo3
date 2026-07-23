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
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsRemoveEmptyRelations;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TcaColumnsRemoveEmptyRelationsTest extends UnitTestCase
{
    private TcaColumnsRemoveEmptyRelations $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new TcaColumnsRemoveEmptyRelations();
        $this->setUpBackendUser(false);
    }

    private function setUpBackendUser(bool $showDebugInfo): void
    {
        $backendUser = self::createStub(BackendUserAuthentication::class);
        $backendUser->method('shallDisplayDebugInformation')->willReturn($showDebugInfo);
        $GLOBALS['BE_USER'] = $backendUser;
    }

    #[Test]
    public function selectFieldWithForeignTableAndEmptyItemsIsRemoved(): void
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => [],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'foreign_table' => 'foreign_table',
                            'items' => [],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->subject->addData($input);
        self::assertArrayNotHasKey('aField', $result['processedTca']['columns']);
    }

    #[Test]
    public function selectFieldWithForeignTableAndEmptyItemsIsReadOnlyInDebugMode(): void
    {
        $this->setUpBackendUser(true);
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => [],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'foreign_table' => 'foreign_table',
                            'items' => [],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->subject->addData($input);
        self::assertArrayHasKey('aField', $result['processedTca']['columns']);
        self::assertTrue($result['processedTca']['columns']['aField']['config']['readOnly']);
        self::assertArrayHasKey('noSelectableItemsAvailable', $result['processedTca']['columns']['aField']['config']['fieldInformation']);
    }

    #[Test]
    public function selectFieldWithForeignTableAndNonEmptyItemsIsKept(): void
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => [],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'foreign_table' => 'foreign_table',
                            'items' => [
                                ['label' => 'Item 1', 'value' => '1'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->subject->addData($input);
        self::assertArrayHasKey('aField', $result['processedTca']['columns']);
    }

    #[Test]
    public function selectFieldWithShowIfEmptyTrueAndEmptyItemsIsKept(): void
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => [],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'foreign_table' => 'foreign_table',
                            'showIfEmpty' => true,
                            'items' => [],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->subject->addData($input);
        self::assertArrayHasKey('aField', $result['processedTca']['columns']);
    }

    #[Test]
    public function selectFieldWithExistingValueAndEmptyItemsIsStillRemoved(): void
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => ['1'],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'foreign_table' => 'foreign_table',
                            'items' => [],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->subject->addData($input);
        self::assertArrayNotHasKey('aField', $result['processedTca']['columns']);
    }

    #[Test]
    public function selectFieldWithoutForeignTableAndEmptyItemsIsRemoved(): void
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => [],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'items' => [],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->subject->addData($input);
        self::assertArrayNotHasKey('aField', $result['processedTca']['columns']);
    }

    #[Test]
    public function selectFieldWithoutForeignTableAndWithItemsIsKept(): void
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => [],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'items' => [
                                ['label' => 'Option A', 'value' => '1'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->subject->addData($input);
        self::assertArrayHasKey('aField', $result['processedTca']['columns']);
    }

    #[Test]
    public function categoryFieldWithEmptyItemsIsRemoved(): void
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => [],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'category',
                            'items' => [],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->subject->addData($input);
        self::assertArrayNotHasKey('aField', $result['processedTca']['columns']);
    }

    #[Test]
    public function groupFieldIsNotTouched(): void
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => [],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'group',
                            'items' => [],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->subject->addData($input);
        self::assertArrayHasKey('aField', $result['processedTca']['columns']);
    }

    #[Test]
    public function inlineFieldIsNotTouched(): void
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => [],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'items' => [],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->subject->addData($input);
        self::assertArrayHasKey('aField', $result['processedTca']['columns']);
    }

    #[Test]
    public function placeholderOnlyItemsAreTreatedAsEmpty(): void
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => [],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'foreign_table' => 'foreign_table',
                            'items' => [
                                ['label' => '', 'value' => ''],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->subject->addData($input);
        self::assertArrayNotHasKey('aField', $result['processedTca']['columns']);
    }

    #[Test]
    public function otherFieldsAreNotAffectedWhenRelationalFieldIsRemoved(): void
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'emptyRelation' => [],
                'inputField' => 'some value',
            ],
            'processedTca' => [
                'columns' => [
                    'emptyRelation' => [
                        'config' => [
                            'type' => 'select',
                            'foreign_table' => 'foreign_table',
                            'items' => [],
                        ],
                    ],
                    'inputField' => [
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->subject->addData($input);
        self::assertArrayNotHasKey('emptyRelation', $result['processedTca']['columns']);
        self::assertArrayHasKey('inputField', $result['processedTca']['columns']);
    }

    #[Test]
    public function selectFieldWithForeignTableAndOnlyStaticItemsIsRemoved(): void
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'fe_group' => [],
            ],
            'processedTca' => [
                'columns' => [
                    'fe_group' => [
                        'config' => [
                            'type' => 'select',
                            'foreign_table' => 'fe_groups',
                            'items' => [
                                ['label' => 'Hide at login', 'value' => -1],
                                ['label' => 'Show at any login', 'value' => -2],
                                ['label' => 'User groups', 'value' => '--div--'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->subject->addData($input);
        self::assertArrayNotHasKey('fe_group', $result['processedTca']['columns']);
    }

    #[Test]
    public function selectFieldWithForeignTableAndOnlyDividersIsRemoved(): void
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => [],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'foreign_table' => 'foreign_table',
                            'items' => [
                                ['label' => 'Group', 'value' => '--div--'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->subject->addData($input);
        self::assertArrayNotHasKey('aField', $result['processedTca']['columns']);
    }

    #[Test]
    public function selectFieldWithForeignTableAndStaticPlusForeignItemsIsKept(): void
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'fe_group' => [],
            ],
            'processedTca' => [
                'columns' => [
                    'fe_group' => [
                        'config' => [
                            'type' => 'select',
                            'foreign_table' => 'fe_groups',
                            'items' => [
                                ['label' => 'Hide at login', 'value' => -1],
                                ['label' => 'Show at any login', 'value' => -2],
                                ['label' => 'User groups', 'value' => '--div--'],
                                ['label' => 'Editors', 'value' => 1],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->subject->addData($input);
        self::assertArrayHasKey('fe_group', $result['processedTca']['columns']);
    }

    #[Test]
    public function selectFieldWithItemsKeyNotSetIsRemoved(): void
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => [],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'foreign_table' => 'foreign_table',
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->subject->addData($input);
        self::assertArrayNotHasKey('aField', $result['processedTca']['columns']);
    }

    #[Test]
    public function selectFieldWhereItemsProcFuncYieldedNoResultsIsRemoved(): void
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'aField' => [],
            ],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'select',
                            'foreign_table' => 'foreign_table',
                            'items' => [],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->subject->addData($input);
        self::assertArrayNotHasKey('aField', $result['processedTca']['columns']);
    }

    #[Test]
    public function languageFieldWithSingleLanguageIsRemoved(): void
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'sys_language_uid' => 0,
            ],
            'processedTca' => [
                'columns' => [
                    'sys_language_uid' => [
                        'config' => [
                            'type' => 'language',
                            'items' => [
                                ['label' => 'Site languages', 'value' => '--div--'],
                                ['label' => 'Default', 'value' => 0, 'icon' => 'flags-en'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->subject->addData($input);
        self::assertArrayNotHasKey('sys_language_uid', $result['processedTca']['columns']);
    }

    #[Test]
    public function languageFieldWithSingleLanguageAndAllLanguagesItemIsRemoved(): void
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'sys_language_uid' => 0,
            ],
            'processedTca' => [
                'columns' => [
                    'sys_language_uid' => [
                        'config' => [
                            'type' => 'language',
                            'items' => [
                                ['label' => 'Site languages', 'value' => '--div--'],
                                ['label' => 'Default', 'value' => 0, 'icon' => 'flags-en'],
                                ['label' => 'Special', 'value' => '--div--'],
                                ['label' => 'All languages', 'value' => -1, 'icon' => 'flags-multiple'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->subject->addData($input);
        self::assertArrayNotHasKey('sys_language_uid', $result['processedTca']['columns']);
    }

    #[Test]
    public function languageFieldWithMultipleLanguagesIsKept(): void
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'sys_language_uid' => 0,
            ],
            'processedTca' => [
                'columns' => [
                    'sys_language_uid' => [
                        'config' => [
                            'type' => 'language',
                            'items' => [
                                ['label' => 'Site languages', 'value' => '--div--'],
                                ['label' => 'Default', 'value' => 0, 'icon' => 'flags-en'],
                                ['label' => 'German', 'value' => 1, 'icon' => 'flags-de'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->subject->addData($input);
        self::assertArrayHasKey('sys_language_uid', $result['processedTca']['columns']);
    }

    #[Test]
    public function languageFieldWithShowIfEmptyTrueIsKept(): void
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [
                'sys_language_uid' => 0,
            ],
            'processedTca' => [
                'columns' => [
                    'sys_language_uid' => [
                        'config' => [
                            'type' => 'language',
                            'showIfEmpty' => true,
                            'items' => [
                                ['label' => 'Site languages', 'value' => '--div--'],
                                ['label' => 'Default', 'value' => 0, 'icon' => 'flags-en'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->subject->addData($input);
        self::assertArrayHasKey('sys_language_uid', $result['processedTca']['columns']);
    }
}

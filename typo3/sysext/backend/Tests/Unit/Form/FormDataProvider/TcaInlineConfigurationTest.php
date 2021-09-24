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

use TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineConfiguration;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class TcaInlineConfigurationTest extends UnitTestCase
{
    /**
     * @var array Set of default controls
     */
    protected array $defaultConfig = [
        'type' => 'inline',
        'foreign_table' => 'aForeignTableName',
        'minitems' => 0,
        'maxitems' => 99999,
        'appearance' => [
            'levelLinksPosition' => 'top',
            'showPossibleLocalizationRecords' => false,
            'enabledControls' => [
                'info' => true,
                'new' => true,
                'dragdrop' => true,
                'sort' => true,
                'hide' => true,
                'delete' => true,
                'localize' => true,
            ],
        ],
    ];

    /**
     * @test
     */
    public function addDataThrowsExceptionForInlineFieldWithoutForeignTableConfig(): void
    {
        $input = [
            'tableName' => 'aTable',
            'databaseRow' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                        ],
                    ],
                ],
            ],
        ];
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1443793404);
        (new TcaInlineConfiguration())->addData($input);
    }

    /**
     * @test
     */
    public function addDataSetsDefaults(): void
    {
        $input = [
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_table' => 'aForeignTableName',
                        ],
                    ],
                ],
            ],
        ];
        $expected = [];
        $expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
        self::assertEquals($expected, (new TcaInlineConfiguration())->addData($input));
    }

    /**
     * @test
     */
    public function addDataKeepsGivenMinitems(): void
    {
        $input = [
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_table' => 'aForeignTableName',
                            'minitems' => 23,
                        ],
                    ],
                ],
            ],
        ];
        $expected = [];
        $expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
        $expected['processedTca']['columns']['aField']['config']['minitems'] = 23;
        self::assertEquals($expected, (new TcaInlineConfiguration())->addData($input));
    }

    /**
     * @test
     */
    public function addDataForcesMinitemsPositive(): void
    {
        $input = [
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_table' => 'aForeignTableName',
                            'minitems' => -23,
                        ],
                    ],
                ],
            ],
        ];
        $expected = [];
        $expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
        $expected['processedTca']['columns']['aField']['config']['minitems'] = 0;
        self::assertEquals($expected, (new TcaInlineConfiguration())->addData($input));
    }

    /**
     * @test
     */
    public function addDataKeepsGivenMaxitems(): void
    {
        $input = [
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_table' => 'aForeignTableName',
                            'maxitems' => 23,
                        ],
                    ],
                ],
            ],
        ];
        $expected = [];
        $expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
        $expected['processedTca']['columns']['aField']['config']['maxitems'] = 23;
        self::assertEquals($expected, (new TcaInlineConfiguration())->addData($input));
    }

    /**
     * @test
     */
    public function addDataForcesMaxitemsPositive(): void
    {
        $input = [
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_table' => 'aForeignTableName',
                            'maxitems' => '-23',
                        ],
                    ],
                ],
            ],
        ];
        $expected = [];
        $expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
        $expected['processedTca']['columns']['aField']['config']['maxitems'] = 1;
        self::assertEquals($expected, (new TcaInlineConfiguration())->addData($input));
    }

    /**
     * @test
     */
    public function addDataMergesWithGivenAppearanceSettings(): void
    {
        $input = [
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_table' => 'aForeignTableName',
                            'appearance' => [
                                'levelLinksPosition' => 'both',
                                'enabledControls' => [
                                    'dragdrop' => false,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $expected = [];
        $expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
        $expected['processedTca']['columns']['aField']['config']['appearance']['levelLinksPosition'] = 'both';
        $expected['processedTca']['columns']['aField']['config']['appearance']['enabledControls']['dragdrop'] = false;
        self::assertEquals($expected, (new TcaInlineConfiguration())->addData($input));
    }

    /**
     * @test
     */
    public function addDataForcesLevelLinksWithForeignSelector(): void
    {
        $input = [
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_table' => 'aForeignTableName',
                            'foreign_selector' => 'aField',
                            'appearance' => [
                                'levelLinksPosition' => 'both',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $GLOBALS['TCA']['aForeignTableName']['columns']['aField']['config'] = [
            'type' => 'select',
            'foreign_table' => 'anotherForeignTableName',
        ];
        $expected = [];
        $expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
        $expected['processedTca']['columns']['aField']['config']['foreign_selector'] = 'aField';
        $expected['processedTca']['columns']['aField']['config']['selectorOrUniqueConfiguration'] = [
            'fieldName' => 'aField',
            'isSelector' => true,
            'isUnique' => false,
            'config' => [
                'type' => 'select',
                'foreign_table' => 'anotherForeignTableName',
            ],
            'foreignTable' => 'anotherForeignTableName',
        ];
        $expected['processedTca']['columns']['aField']['config']['appearance']['levelLinksPosition'] = 'both';
        $expected['processedTca']['columns']['aField']['config']['appearance']['showAllLocalizationLink'] = false;
        $expected['processedTca']['columns']['aField']['config']['appearance']['showSynchronizationLink'] = false;
        $expected['processedTca']['columns']['aField']['config']['appearance']['showNewRecordLink'] = false;
        self::assertEquals($expected, (new TcaInlineConfiguration())->addData($input));
    }

    /**
     * @test
     */
    public function addDataKeepsLevelLinksPositionWithForeignSelectorAndUseCombination(): void
    {
        $input = [
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_table' => 'aForeignTableName',
                            'foreign_selector' => 'aField',
                            'appearance' => [
                                'useCombination' => true,
                                'levelLinksPosition' => 'both',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $GLOBALS['TCA']['aForeignTableName']['columns']['aField']['config'] = [
            'type' => 'select',
            'foreign_table' => 'anotherForeignTableName',
        ];
        $expected = [];
        $expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
        $expected['processedTca']['columns']['aField']['config']['foreign_selector'] = 'aField';
        $expected['processedTca']['columns']['aField']['config']['selectorOrUniqueConfiguration'] = [
            'fieldName' => 'aField',
            'isSelector' => true,
            'isUnique' => false,
            'config' => [
                'type' => 'select',
                'foreign_table' => 'anotherForeignTableName',
            ],
            'foreignTable' => 'anotherForeignTableName',
        ];
        $expected['processedTca']['columns']['aField']['config']['appearance']['useCombination'] = true;
        $expected['processedTca']['columns']['aField']['config']['appearance']['levelLinksPosition'] = 'both';
        self::assertEquals($expected, (new TcaInlineConfiguration())->addData($input));
    }

    /**
     * @test
     */
    public function addDataKeepsShowPossibleLocalizationRecordsButForcesBooleanTrue(): void
    {
        $input = [
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_table' => 'aForeignTableName',
                            'appearance' => [
                                'showPossibleLocalizationRecords' => '1',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $expected = [];
        $expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
        $expected['processedTca']['columns']['aField']['config']['appearance']['showPossibleLocalizationRecords'] = true;
        self::assertEquals($expected, (new TcaInlineConfiguration())->addData($input));
    }

    /**
     * @test
     */
    public function addDataKeepsShowPossibleLocalizationRecordsButForcesBooleanFalse(): void
    {
        $input = [
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_table' => 'aForeignTableName',
                            'appearance' => [
                                'showPossibleLocalizationRecords' => 0,
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $expected = [];
        $expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
        $expected['processedTca']['columns']['aField']['config']['appearance']['showPossibleLocalizationRecords'] = false;
        self::assertEquals($expected, (new TcaInlineConfiguration())->addData($input));
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfForeignSelectorAndForeignUniquePointToDifferentFields(): void
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_table' => 'aForeignTableName',
                            'foreign_selector' => 'aField',
                            'foreign_unique' => 'aDifferentField',
                        ],
                    ],
                ],
            ],
        ];
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1444995464);
        (new TcaInlineConfiguration())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfForeignSelectorPointsToANotExistingField(): void
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_table' => 'aForeignTableName',
                            'foreign_selector' => 'aField',
                        ],
                    ],
                ],
            ],
        ];
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1444996537);
        (new TcaInlineConfiguration())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfForeignUniquePointsToANotExistingField(): void
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_table' => 'aForeignTableName',
                            'foreign_unique' => 'aField',
                        ],
                    ],
                ],
            ],
        ];
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1444996537);
        (new TcaInlineConfiguration())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfForeignUniqueTargetIsNotTypeSelectOrGroup(): void
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_table' => 'aForeignTableName',
                            'foreign_unique' => 'aField',
                        ],
                    ],
                ],
            ],
        ];
        $GLOBALS['TCA']['aForeignTableName']['columns']['aField']['config'] = [
            'type' => 'notSelectOrGroup',
        ];
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1444996537);
        (new TcaInlineConfiguration())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionForForeignSelectorGroupWithoutInternalTypeDb(): void
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_table' => 'aForeignTableName',
                            'foreign_unique' => 'aField',
                        ],
                    ],
                ],
            ],
        ];
        $GLOBALS['TCA']['aForeignTableName']['columns']['aField']['config'] = [
            'type' => 'group',
            'internal_type' => 'notDb',
        ];
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1444999130);
        (new TcaInlineConfiguration())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfForeignUniqueSelectDoesNotDefineForeignTable(): void
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_table' => 'aForeignTableName',
                            'foreign_unique' => 'aField',
                        ],
                    ],
                ],
            ],
        ];
        $GLOBALS['TCA']['aForeignTableName']['columns']['aField']['config'] = [
            'type' => 'select',
        ];
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1445078627);
        (new TcaInlineConfiguration())->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfForeignUniqueGroupDoesNotDefineForeignTable(): void
    {
        $input = [
            'tableName' => 'aTable',
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_table' => 'aForeignTableName',
                            'foreign_unique' => 'aField',
                        ],
                    ],
                ],
            ],
        ];
        $GLOBALS['TCA']['aForeignTableName']['columns']['aField']['config'] = [
            'type' => 'group',
            'internal_type' => 'db',
        ];
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1445078628);
        (new TcaInlineConfiguration())->addData($input);
    }

    /**
     * @test
     */
    public function addDataAddsSelectorOrUniqueConfigurationForForeignUnique(): void
    {
        $input = [
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_table' => 'aForeignTableName',
                            'foreign_unique' => 'aField',
                        ],
                    ],
                ],
            ],
        ];
        $GLOBALS['TCA']['aForeignTableName']['columns']['aField']['config'] = [
            'type' => 'select',
            'foreign_table' => 'anotherForeignTableName',
        ];
        $expected = [];
        $expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
        $expected['processedTca']['columns']['aField']['config']['foreign_unique'] = 'aField';
        $expected['processedTca']['columns']['aField']['config']['selectorOrUniqueConfiguration'] = [
            'fieldName' => 'aField',
            'isSelector' => false,
            'isUnique' => true,
            'config' => [
                'type' => 'select',
                'foreign_table' => 'anotherForeignTableName',
            ],
            'foreignTable' => 'anotherForeignTableName',
        ];
        self::assertEquals($expected, (new TcaInlineConfiguration())->addData($input));
    }

    /**
     * @test
     */
    public function addDataMergesForeignSelectorFieldTcaOverride(): void
    {
        $input = [
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_table' => 'aForeignTableName',
                            'foreign_selector' => 'aField',
                            'overrideChildTca' => [
                                'columns' => [
                                    'aField' => [
                                        'config' => [
                                            'aGivenSetting' => 'aOverrideValue',
                                            'aNewSetting' => 'aNewSetting',
                                            'appearance' => [
                                                'elementBrowserType' => 'file',
                                                'elementBrowserAllowed' => 'jpg,png',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $GLOBALS['TCA']['aForeignTableName']['columns']['aField']['config'] = [
            'type' => 'group',
            'internal_type' => 'db',
            'allowed' => 'anotherForeignTableName',
            'doNotChangeMe' => 'doNotChangeMe',
            'aGivenSetting' => 'aGivenValue',
        ];

        $expected = [];
        $expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
        $expected['processedTca']['columns']['aField']['config']['appearance']['showAllLocalizationLink'] = false;
        $expected['processedTca']['columns']['aField']['config']['appearance']['showSynchronizationLink'] = false;
        $expected['processedTca']['columns']['aField']['config']['appearance']['showNewRecordLink'] = false;
        $expected['processedTca']['columns']['aField']['config']['foreign_selector'] = 'aField';
        $expected['processedTca']['columns']['aField']['config']['overrideChildTca']['columns']['aField'] = [
            'config' => [
                'aGivenSetting' => 'aOverrideValue',
                'aNewSetting' => 'aNewSetting',
                'appearance' => [
                    'elementBrowserType' => 'file',
                    'elementBrowserAllowed' => 'jpg,png',
                ],
            ],
        ];

        $expected['processedTca']['columns']['aField']['config']['selectorOrUniqueConfiguration'] = [
            'fieldName' => 'aField',
            'isSelector' => true,
            'isUnique' => false,
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'anotherForeignTableName',
                'doNotChangeMe' => 'doNotChangeMe',
                'aGivenSetting' => 'aOverrideValue',
                'aNewSetting' => 'aNewSetting',
                'appearance' => [
                    'elementBrowserType' => 'file',
                    'elementBrowserAllowed' => 'jpg,png',
                ],
            ],
            'foreignTable' => 'anotherForeignTableName',
        ];
        self::assertEquals($expected, (new TcaInlineConfiguration())->addData($input));
    }
}

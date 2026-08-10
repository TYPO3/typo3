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
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineConfiguration;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Schema\FieldTypeFactory;
use TYPO3\CMS\Core\Schema\RelationMapBuilder;
use TYPO3\CMS\Core\Schema\SchemaCollection;
use TYPO3\CMS\Core\Schema\TcaSchemaBuilder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TcaInlineConfigurationTest extends UnitTestCase
{
    /**
     * @var array Set of default controls
     */
    private array $defaultConfig = [
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

    #[Test]
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
        new TcaInlineConfiguration()->addData($input);
    }

    #[Test]
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
        $input['tcaSchemata'] = $this->getSchemaCollection($input['processedTca']);
        $expected = [];
        $expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
        $expected['tcaSchemata'] = $input['tcaSchemata'];
        self::assertEquals($expected, new TcaInlineConfiguration()->addData($input));
    }

    #[Test]
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
        $input['tcaSchemata'] = $this->getSchemaCollection($input['processedTca']);
        $expected = [];
        $expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
        $expected['processedTca']['columns']['aField']['config']['minitems'] = 23;
        $expected['tcaSchemata'] = $input['tcaSchemata'];
        self::assertEquals($expected, new TcaInlineConfiguration()->addData($input));
    }

    #[Test]
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
        $input['tcaSchemata'] = $this->getSchemaCollection($input['processedTca']);
        $expected = [];
        $expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
        $expected['processedTca']['columns']['aField']['config']['minitems'] = 0;
        $expected['tcaSchemata'] = $input['tcaSchemata'];
        self::assertEquals($expected, new TcaInlineConfiguration()->addData($input));
    }

    #[Test]
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
        $input['tcaSchemata'] = $this->getSchemaCollection($input['processedTca']);
        $expected = [];
        $expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
        $expected['processedTca']['columns']['aField']['config']['maxitems'] = 23;
        $expected['tcaSchemata'] = $input['tcaSchemata'];
        self::assertEquals($expected, new TcaInlineConfiguration()->addData($input));
    }

    #[Test]
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
        $input['tcaSchemata'] = $this->getSchemaCollection($input['processedTca']);
        $expected = [];
        $expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
        $expected['processedTca']['columns']['aField']['config']['maxitems'] = 1;
        $expected['tcaSchemata'] = $input['tcaSchemata'];
        self::assertEquals($expected, new TcaInlineConfiguration()->addData($input));
    }

    #[Test]
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
        $input['tcaSchemata'] = $this->getSchemaCollection($input['processedTca']);
        $expected = [];
        $expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
        $expected['processedTca']['columns']['aField']['config']['appearance']['levelLinksPosition'] = 'both';
        $expected['processedTca']['columns']['aField']['config']['appearance']['enabledControls']['dragdrop'] = false;
        $expected['tcaSchemata'] = $input['tcaSchemata'];
        self::assertEquals($expected, new TcaInlineConfiguration()->addData($input));
    }

    #[Test]
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
        $input['processedTca']['aForeignTableName']['columns']['aField']['config'] = [
            'type' => 'select',
            'foreign_table' => 'anotherForeignTableName',
        ];
        $input['tcaSchemata'] = $this->getSchemaCollection($input['processedTca']);
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
        $expected['processedTca']['aForeignTableName'] = $input['processedTca']['aForeignTableName'];
        $expected['tcaSchemata'] = $input['tcaSchemata'];
        self::assertEquals($expected, new TcaInlineConfiguration()->addData($input));
    }

    #[Test]
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
        $input['processedTca']['aForeignTableName']['columns']['aField']['config'] = [
            'type' => 'select',
            'foreign_table' => 'anotherForeignTableName',
        ];
        $input['tcaSchemata'] = $this->getSchemaCollection($input['processedTca']);
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
        $expected['processedTca']['aForeignTableName'] = $input['processedTca']['aForeignTableName'];
        $expected['tcaSchemata'] = $input['tcaSchemata'];
        self::assertEquals($expected, new TcaInlineConfiguration()->addData($input));
    }

    #[Test]
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
        $input['tcaSchemata'] = $this->getSchemaCollection($input['processedTca']);
        $expected = [];
        $expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
        $expected['processedTca']['columns']['aField']['config']['appearance']['showPossibleLocalizationRecords'] = true;
        $expected['tcaSchemata'] = $input['tcaSchemata'];
        self::assertEquals($expected, new TcaInlineConfiguration()->addData($input));
    }

    #[Test]
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
        $input['tcaSchemata'] = $this->getSchemaCollection($input['processedTca']);
        $expected = [];
        $expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
        $expected['processedTca']['columns']['aField']['config']['appearance']['showPossibleLocalizationRecords'] = false;
        $expected['tcaSchemata'] = $input['tcaSchemata'];
        self::assertEquals($expected, new TcaInlineConfiguration()->addData($input));
    }

    #[Test]
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
        $input['processedTca']['aForeignTableName'] = [];
        $input['tcaSchemata'] = $this->getSchemaCollection($input['processedTca']);
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1444995464);
        new TcaInlineConfiguration()->addData($input);
    }

    #[Test]
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
        $input['processedTca']['aForeignTableName'] = [];
        $input['tcaSchemata'] = $this->getSchemaCollection($input['processedTca']);
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1444996537);
        new TcaInlineConfiguration()->addData($input);
    }

    #[Test]
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
        $input['processedTca']['aForeignTableName'] = [];
        $input['tcaSchemata'] = $this->getSchemaCollection($input['processedTca']);
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1444996537);
        new TcaInlineConfiguration()->addData($input);
    }

    #[Test]
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
        $input['processedTca']['aForeignTableName']['columns']['aField']['config'] = [
            'type' => 'notSelectOrGroup',
        ];
        $input['tcaSchemata'] = $this->getSchemaCollection($input['processedTca']);
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1444996537);
        new TcaInlineConfiguration()->addData($input);
    }

    #[Test]
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
        $input['processedTca']['aForeignTableName']['columns']['aField']['config'] = [
            'type' => 'select',
        ];
        $input['tcaSchemata'] = $this->getSchemaCollection($input['processedTca']);
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1445078627);
        new TcaInlineConfiguration()->addData($input);
    }

    #[Test]
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
        $input['processedTca']['aForeignTableName']['columns']['aField']['config'] = ['type' => 'group'];
        $input['tcaSchemata'] = $this->getSchemaCollection($input['processedTca']);
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1445078628);
        new TcaInlineConfiguration()->addData($input);
    }

    #[Test]
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
        $input['processedTca']['aForeignTableName']['columns']['aField']['config'] = [
            'type' => 'select',
            'foreign_table' => 'anotherForeignTableName',
        ];
        $input['tcaSchemata'] = $this->getSchemaCollection($input['processedTca']);
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
        $expected['processedTca']['aForeignTableName'] = $input['processedTca']['aForeignTableName'];
        $expected['tcaSchemata'] = $input['tcaSchemata'];
        self::assertEquals($expected, new TcaInlineConfiguration()->addData($input));
    }

    #[Test]
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
                                                'useSortable' => true,
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
        $input['processedTca']['aForeignTableName']['columns']['aField']['config'] = [
            'type' => 'group',
            'allowed' => 'anotherForeignTableName',
            'doNotChangeMe' => 'doNotChangeMe',
            'aGivenSetting' => 'aGivenValue',
        ];
        $input['tcaSchemata'] = $this->getSchemaCollection($input['processedTca']);

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
                    'useSortable' => true,
                ],
            ],
        ];

        $expected['processedTca']['columns']['aField']['config']['selectorOrUniqueConfiguration'] = [
            'fieldName' => 'aField',
            'isSelector' => true,
            'isUnique' => false,
            'config' => [
                'type' => 'group',
                'allowed' => 'anotherForeignTableName',
                'doNotChangeMe' => 'doNotChangeMe',
                'aGivenSetting' => 'aOverrideValue',
                'aNewSetting' => 'aNewSetting',
                'appearance' => [
                    'useSortable' => true,
                ],
            ],
            'foreignTable' => 'anotherForeignTableName',
        ];
        $expected['processedTca']['aForeignTableName'] = $input['processedTca']['aForeignTableName'];
        $expected['tcaSchemata'] = $input['tcaSchemata'];
        self::assertEquals($expected, new TcaInlineConfiguration()->addData($input));
    }

    private function getSchemaCollection(array $tca): SchemaCollection
    {
        $tcaSchemaFactory = new TcaSchemaBuilder(
            new RelationMapBuilder(self::createStub(FlexFormTools::class)),
            new FieldTypeFactory()
        );
        return $tcaSchemaFactory->buildFromStructure($tca);
    }
}

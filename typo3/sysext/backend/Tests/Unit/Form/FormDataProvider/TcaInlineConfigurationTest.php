<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Form\FormDataProvider;

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

use TYPO3\CMS\Backend\Form\FormDataProvider\TcaInlineConfiguration;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case
 */
class TcaInlineConfigurationTest extends UnitTestCase
{
    /**
     * @var TcaInlineConfiguration
     */
    protected $subject;

    protected function setUp()
    {
        $this->subject = new TcaInlineConfiguration();
    }

    /**
     * @var array Set of default controls
     */
    protected $defaultConfig = [
        'type' => 'inline',
        'foreign_table' => 'aForeignTableName',
        'minitems' => 0,
        'maxitems' => 100000,
        'behaviour' => [
            'localizationMode' => 'none',
        ],
        'appearance' => [
            'levelLinksPosition' => 'top',
            'showPossibleLocalizationRecords' => false,
            'showRemovedLocalizationRecords' => false,
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
    public function addDataThrowsExceptionForInlineFieldWithoutForeignTableConfig()
    {
        $input = [
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
        $this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1443793404);
        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataSetsDefaults()
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
        $expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataKeepsGivenMinitems()
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
        $expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
        $expected['processedTca']['columns']['aField']['config']['minitems'] = 23;
        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataForcesMinitemsPositive()
    {
        $input = [
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_table' => 'aForeignTableName',
                            'minitems' => '-23',
                        ],
                    ],
                ],
            ],
        ];
        $expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
        $expected['processedTca']['columns']['aField']['config']['minitems'] = 0;
        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataKeepsGivenMaxitems()
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
        $expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
        $expected['processedTca']['columns']['aField']['config']['maxitems'] = 23;
        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataForcesMaxitemsPositive()
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
        $expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
        $expected['processedTca']['columns']['aField']['config']['maxitems'] = 1;
        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfLocalizationModeIsSetButNotToKeepOrSelect()
    {
        $input = [
            'defaultLanguageRow' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_table' => 'aForeignTableName',
                            'behaviour' => [
                                'localizationMode' => 'foo',
                            ]
                        ],
                    ],
                ],
            ],
        ];
        $this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1443829370);
        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfLocalizationModeIsSetToSelectAndChildIsNotLocalizable()
    {
        $input = [
            'defaultLanguageRow' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_table' => 'aForeignTableName',
                            'behaviour' => [
                                'localizationMode' => 'select',
                            ]
                        ],
                    ],
                ],
            ],
        ];
        // not $globals definition for child here -> not localizable
        $this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1443944274);
        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataKeepsLocalizationModeSelectIfChildIsLocalizable()
    {
        $input = [
            'defaultLanguageRow' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_table' => 'aForeignTableName',
                            'behaviour' => [
                                'localizationMode' => 'select',
                            ]
                        ],
                    ],
                ],
            ],
        ];
        $GLOBALS['TCA']['aForeignTableName']['ctrl'] = [
            'languageField' => 'theLanguageField',
            'transOrigPointerField' => 'theTransOrigPointerField',
        ];
        $expected = $input;
        $expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
        $expected['processedTca']['columns']['aField']['config']['behaviour']['localizationMode'] = 'select';
        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataKeepsLocalizationModeKeep()
    {
        $input = [
            'defaultLanguageRow' => [],
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_table' => 'aForeignTableName',
                            'behaviour' => [
                                'localizationMode' => 'keep',
                            ]
                        ],
                    ],
                ],
            ],
        ];
        $expected = $input;
        $expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
        $expected['processedTca']['columns']['aField']['config']['behaviour']['localizationMode'] = 'keep';
        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsLocalizationModeToNoneIfNotSetAndChildIsNotLocalizable()
    {
        $input = [
            'defaultLanguageRow' => [],
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
        $expected = $input;
        $expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
        $expected['processedTca']['columns']['aField']['config']['behaviour']['localizationMode'] = 'none';
        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataSetsLocalizationModeToSelectIfNotSetAndChildIsLocalizable()
    {
        $input = [
            'defaultLanguageRow' => [],
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
        $GLOBALS['TCA']['aForeignTableName']['ctrl'] = [
            'languageField' => 'theLanguageField',
            'transOrigPointerField' => 'theTransOrigPointerField',
        ];
        $expected = $input;
        $expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
        $expected['processedTca']['columns']['aField']['config']['behaviour']['localizationMode'] = 'select';
        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataMergesWithGivenAppearanceSettings()
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
        $expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
        $expected['processedTca']['columns']['aField']['config']['appearance']['levelLinksPosition'] = 'both';
        $expected['processedTca']['columns']['aField']['config']['appearance']['enabledControls']['dragdrop'] = false;
        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataForcesLevelLinksPositionWithForeignSelector()
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
        $expected['processedTca']['columns']['aField']['config']['appearance']['levelLinksPosition'] = 'none';
        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataKeepsLevelLinksPositionWithForeignSelectorAndUseCombination()
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
        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataKeepsShowPossibleLocalizationRecordsButForcesBooleanTrue()
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
        $expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
        $expected['processedTca']['columns']['aField']['config']['appearance']['showPossibleLocalizationRecords'] = true;
        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataKeepsShowPossibleLocalizationRecordsButForcesBooleanFalse()
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
        $expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
        $expected['processedTca']['columns']['aField']['config']['appearance']['showPossibleLocalizationRecords'] = false;
        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataKeepshowRemovedLocalizationRecordsButForcesBooleanTrue()
    {
        $input = [
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_table' => 'aForeignTableName',
                            'appearance' => [
                                'showRemovedLocalizationRecords' => 1,
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
        $expected['processedTca']['columns']['aField']['config']['appearance']['showRemovedLocalizationRecords'] = true;
        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataKeepsShowRemovedLocalizationRecordsButForcesBooleanFalse()
    {
        $input = [
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_table' => 'aForeignTableName',
                            'appearance' => [
                                'showRemovedLocalizationRecords' => '',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
        $expected['processedTca']['columns']['aField']['config']['appearance']['showRemovedLocalizationRecords'] = false;
        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfForeignSelectorAndForeignUniquePointToDifferentFields()
    {
        $input = [
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
        $this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1444995464);
        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfForeignSelectorPointsToANotExistingField()
    {
        $input = [
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
        $this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1444996537);
        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfForeignUniquePointsToANotExistingField()
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
        $this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1444996537);
        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfForeignUniqueTargetIsNotTypeSelectOrGroup()
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
            'type' => 'notSelectOrGroup',
        ];
        $this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1444996537);
        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionForForeignSelectorGroupWithoutInternalTypeDb()
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
            'type' => 'group',
            'internal_type' => 'notDb'
        ];
        $this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1444999130);
        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfForeignUniqueSelectDoesNotDefineForeignTable()
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
        ];
        $this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1445078627);
        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataThrowsExceptionIfForeignUniqueGroupDoesNotDefineForeignTable()
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
            'type' => 'group',
            'internal_type' => 'db',
        ];
        $this->setExpectedException(\UnexpectedValueException::class, $this->anything(), 1445078628);
        $this->subject->addData($input);
    }

    /**
     * @test
     */
    public function addDataAddsSelectorOrUniqueConfigurationForForeignUnique()
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
        $this->assertEquals($expected, $this->subject->addData($input));
    }

    /**
     * @test
     */
    public function addDataMergesForeignSelectorFieldTcaOverride()
    {
        $input = [
            'processedTca' => [
                'columns' => [
                    'aField' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_table' => 'aForeignTableName',
                            'foreign_selector' => 'aField',
                            'foreign_selector_fieldTcaOverride' => [
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
        ];
        $GLOBALS['TCA']['aForeignTableName']['columns']['aField']['config'] = [
            'type' => 'group',
            'internal_type' => 'db',
            'allowed' => 'anotherForeignTableName',
            'doNotChangeMe' => 'doNotChangeMe',
            'aGivenSetting' => 'aGivenValue',
        ];

        $expected['processedTca']['columns']['aField']['config'] = $this->defaultConfig;
        $expected['processedTca']['columns']['aField']['config']['appearance']['levelLinksPosition'] = 'none';
        $expected['processedTca']['columns']['aField']['config']['foreign_selector'] = 'aField';
        $expected['processedTca']['columns']['aField']['config']['foreign_selector_fieldTcaOverride'] = [
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
        $this->assertEquals($expected, $this->subject->addData($input));
    }
}

<?php
namespace TYPO3\CMS\Core\Tests\Unit\Migrations;

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

use TYPO3\CMS\Core\Migrations\TcaMigration;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Test case
 */
class TcaMigrationTest extends UnitTestCase
{
    /**
     * @test
     */
    public function migrateReturnsGivenArrayUnchangedIfNoMigrationNeeded()
    {
        $input = $expected = [
            'aTable' => [
                'ctrl' => [
                    'aKey' => 'aValue',
                ],
                'columns' => [
                    'aField' => [
                        'label' => 'foo',
                        'config' => [
                            'type' => 'aType',
                            'lolli' => 'did this',
                        ]
                    ],
                ],
                'types' => [
                    0 => [
                        'showitem' => 'this,should;stay,this,too',
                    ],
                ],
            ],
        ];
        $subject = new TcaMigration();
        $this->assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function migrateChangesT3editorWizardToT3editorRenderTypeIfNotEnabledByTypeConfig()
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'bodytext' => [
                        'exclude' => 1,
                        'label' => 'aLabel',
                        'config' => [
                            'type' => 'text',
                            'rows' => 42,
                            'wizards' => [
                                't3editor' => [
                                    'type' => 'userFunc',
                                    'userFunc' => 'TYPO3\CMS\T3editor\FormWizard->main',
                                    'title' => 't3editor',
                                    'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_table.gif',
                                    'module' => [
                                        'name' => 'wizard_table'
                                    ],
                                    'params' => [
                                        'format' => 'html',
                                        'style' => 'width:98%; height: 60%;'
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $expected = [
            'aTable' => [
                'columns' => [
                    'bodytext' => [
                        'exclude' => 1,
                        'label' => 'aLabel',
                        'config' => [
                            'type' => 'text',
                            'renderType' => 't3editor',
                            'format' => 'html',
                            'rows' => 42,
                        ],
                    ],
                ],
            ],
        ];
        $subject = new TcaMigration();
        $this->assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function migrateDropsStylePointerFromShowItem()
    {
        $input = [
            'aTable' => [
                'types' => [
                    0 => [
                        'showitem' => 'aField,anotherField;with;;;style-pointer,thirdField',
                    ],
                    1 => [
                        'showitem' => 'aField,;;;;only-a-style-pointer,anotherField',
                    ],
                ],
            ],
        ];
        $expected = [
            'aTable' => [
                'types' => [
                    0 => [
                        'showitem' => 'aField,anotherField;with,thirdField',
                    ],
                    1 => [
                        'showitem' => 'aField,anotherField',
                    ],
                ],
            ],
        ];
        $subject = new TcaMigration();
        $this->assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function migrateMovesSpecialConfigurationToColumnsOverridesDefaultExtras()
    {
        $input = [
            'aTable' => [
                'types' => [
                    0 => [
                        'showitem' => 'aField,anotherField;with;;special:configuration,thirdField',
                    ],
                ],
            ],
        ];
        $expected = [
            'aTable' => [
                'types' => [
                    0 => [
                        'showitem' => 'aField,anotherField;with,thirdField',
                        'columnsOverrides' => [
                            'anotherField' => [
                                'defaultExtras' => 'special:configuration',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $subject = new TcaMigration();
        $this->assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function migrateMovesSpecialConfigurationToColumnsOverridesDefaultExtrasAndMergesExistingDefaultExtras()
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'anotherField' => [
                        'defaultExtras' => 'some:values',
                    ],
                ],
                'types' => [
                    0 => [
                        'showitem' => 'aField,anotherField;with;;special:configuration,thirdField',
                    ],
                ],
            ],
        ];
        $expected = [
            'aTable' => [
                'columns' => [
                    'anotherField' => [
                        'defaultExtras' => 'some:values',
                    ],
                ],
                'types' => [
                    0 => [
                        'showitem' => 'aField,anotherField;with,thirdField',
                        'columnsOverrides' => [
                            'anotherField' => [
                                'defaultExtras' => 'some:values:special:configuration',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $subject = new TcaMigration();
        $this->assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function migrateChangesT3editorWizardThatIsEnabledByTypeConfigToRenderTypeInColmnnsOverrides()
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'bodytext' => [
                        'exclude' => 1,
                        'label' => 'aLabel',
                        'config' => [
                            'type' => 'text',
                            'rows' => 42,
                            'wizards' => [
                                't3editorHtml' => [
                                    'type' => 'userFunc',
                                    'userFunc' => 'TYPO3\CMS\T3editor\FormWizard->main',
                                    'enableByTypeConfig' => 1,
                                    'title' => 't3editor',
                                    'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_table.gif',
                                    'module' => [
                                        'name' => 'wizard_table'
                                    ],
                                    'params' => [
                                        'format' => 'html',
                                        'style' => 'width:98%; height: 60%;'
                                    ],
                                ],
                                't3editorTypoScript' => [
                                    'type' => 'userFunc',
                                    'userFunc' => 'TYPO3\CMS\T3editor\FormWizard->main',
                                    'enableByTypeConfig' => 1,
                                    'title' => 't3editor',
                                    'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_table.gif',
                                    'module' => [
                                        'name' => 'wizard_table'
                                    ],
                                    'params' => [
                                        'format' => 'typoscript',
                                        'style' => 'width:98%; height: 60%;'
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'types' => [
                    'firstType' => [
                        'showitem' => 'foo,bodytext;;;wizards[t3editorTypoScript|someOtherWizard],bar',
                    ],
                    'secondType' => [
                        'showitem' => 'foo,bodytext;;;nowrap:wizards[t3editorHtml], bar',
                    ],
                ],
            ],
        ];
        $expected = [
            'aTable' => [
                'columns' => [
                    'bodytext' => [
                        'exclude' => 1,
                        'label' => 'aLabel',
                        'config' => [
                            'type' => 'text',
                            'rows' => 42,
                        ],
                    ],
                ],
                'types' => [
                    'firstType' => [
                        'showitem' => 'foo,bodytext,bar',
                        'columnsOverrides' => [
                            'bodytext' => [
                                'config' => [
                                    'format' => 'typoscript',
                                    'renderType' => 't3editor',
                                ],
                                'defaultExtras' => 'wizards[someOtherWizard]',
                            ],
                        ],
                    ],
                    'secondType' => [
                        'showitem' => 'foo,bodytext,bar',
                        'columnsOverrides' => [
                            'bodytext' => [
                                'config' => [
                                    'format' => 'html',
                                    'renderType' => 't3editor',
                                ],
                                'defaultExtras' => 'nowrap',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $subject = new TcaMigration();
        $this->assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function migrateRemovesAnUnusedT3edtiorDefinitionIfEnabledByTypeConfig()
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'bodytext' => [
                        'exclude' => 1,
                        'label' => 'aLabel',
                        'config' => [
                            'type' => 'text',
                            'rows' => 42,
                            'wizards' => [
                                't3editorHtml' => [
                                    'type' => 'userFunc',
                                    'userFunc' => 'TYPO3\CMS\T3editor\FormWizard->main',
                                    'enableByTypeConfig' => 1,
                                    'title' => 't3editor',
                                    'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_table.gif',
                                    'module' => [
                                        'name' => 'wizard_table'
                                    ],
                                    'params' => [
                                        'format' => 'html',
                                        'style' => 'width:98%; height: 60%;'
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $expected = [
            'aTable' => [
                'columns' => [
                    'bodytext' => [
                        'exclude' => 1,
                        'label' => 'aLabel',
                        'config' => [
                            'type' => 'text',
                            'rows' => 42,
                        ],
                    ],
                ],
            ],
        ];
        $subject = new TcaMigration();
        $this->assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function migrateSpecialConfigurationAndRemoveShowItemStylePointerConfigDoesNotAddMessageIfOnlySyntaxChanged()
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'anotherField' => [
                    ],
                ],
                'types' => [
                    0 => [
                        'showitem' => 'aField;;;',
                    ],
                    1 => []
                ],
            ],
        ];
        $subject = new TcaMigration();
        $subject->migrate($input);
        $this->assertEmpty($subject->getMessages());
    }

    /**
     * @test
     */
    public function migrateShowItemMovesAdditionalPaletteToOwnPaletteDefinition()
    {
        $input = [
            'aTable' => [
                'types' => [
                    'firstType' => [
                        'showitem' => 'field1;field1Label,field2;fieldLabel2;palette1,field2;;palette2',
                    ],
                ],
            ],
        ];
        $expected = [
            'aTable' => [
                'types' => [
                    'firstType' => [
                        'showitem' => 'field1;field1Label,field2;fieldLabel2,--palette--;;palette1,field2,--palette--;;palette2',
                    ],
                ],
            ],
        ];
        $subject = new TcaMigration();
        $this->assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function migrateIconsForFormFieldWizardsToNewLocation()
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'bodytext' => [
                        'config' => [
                            'wizards' => [
                                't3editorHtml' => [
                                    'icon' => 'wizard_table.gif',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = [
            'aTable' => [
                'columns' => [
                    'bodytext' => [
                        'config' => [
                            'wizards' => [
                                't3editorHtml' => [
                                    'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_table.gif',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $subject = new TcaMigration();
        $this->assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function migrateExtAndSysextPathToEXTPath()
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'foo' => [
                        'config' => [
                            'type' => 'select',
                            'items' => [
                                ['foo', 0, 'ext/myext/foo/bar.gif'],
                                ['bar', 1, 'sysext/myext/foo/bar.gif'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = [
            'aTable' => [
                'columns' => [
                    'foo' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => [
                                ['foo', 0, 'EXT:myext/foo/bar.gif'],
                                ['bar', 1, 'EXT:myext/foo/bar.gif'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $subject = new TcaMigration();
        $this->assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function migratePathWhichStartsWithIToEXTPath()
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'foo' => [
                        'config' => [
                            'type' => 'select',
                            'items' => [
                                ['foo', 0, 'i/tt_content.gif'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $expected = [
            'aTable' => [
                'columns' => [
                    'foo' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => [
                                ['foo', 0, 'EXT:t3skin/icons/gfx/i/tt_content.gif'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $subject = new TcaMigration();
        $this->assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function migrateRemovesIconsInOptionTags()
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'foo' => [
                        'config' => [
                            'type' => 'select',
                            'iconsInOptionTags' => 1,
                        ],
                    ],
                ],
            ],
        ];
        $expected = [
            'aTable' => [
                'columns' => [
                    'foo' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                        ],
                    ],
                ],
            ],
        ];

        $subject = new TcaMigration();
        $this->assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function migrateRewritesRelativeIconPathToExtensionReference()
    {
        $input = [
                'aTable' => [
                        'ctrl' => [
                                'iconfile' => '../typo3conf/ext/myExt/iconfile.gif',
                        ],
                ],
        ];
        $expected = [
                'aTable' => [
                        'ctrl' => [
                                'iconfile' => 'EXT:myExt/iconfile.gif',
                        ],
                ],
        ];
        $subject = new TcaMigration();
        $this->assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function migrateRewritesIconFilenameOnlyToDefaultT3skinExtensionReference()
    {
        $input = [
                'aTable' => [
                        'ctrl' => [
                                'iconfile' => 'iconfile.gif',
                        ],
                ],
        ];
        $expected = [
                'aTable' => [
                        'ctrl' => [
                                'iconfile' => 'EXT:t3skin/icons/gfx/i/iconfile.gif',
                        ],
                ],
        ];
        $subject = new TcaMigration();
        $this->assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function migrateKeepsGivenExtensionReference()
    {
        $input = [
                'aTable' => [
                        'ctrl' => [
                                'iconfile' => 'EXT:myExt/iconfile.gif',
                        ],
                ],
        ];
        $expected = [
                'aTable' => [
                        'ctrl' => [
                                'iconfile' => 'EXT:myExt/iconfile.gif',
                        ],
                ],
        ];
        $subject = new TcaMigration();
        $this->assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function migrateSelectFieldRenderType()
    {
        $input = [
            'aTable-do-not-migrate-because-renderType-is-set' => [
                'columns' => [
                    'a-column' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'fooBar'
                        ]
                    ]
                ],
            ],
            'aTable-do-migrate-because-renderType-is-not-set' => [
                'columns' => [
                    'a-tree-column' => [
                        'config' => [
                            'type' => 'select',
                            'renderMode' => 'tree'
                        ]
                    ],
                    'a-singlebox-column' => [
                        'config' => [
                            'type' => 'select',
                            'renderMode' => 'singlebox'
                        ]
                    ],
                    'a-checkbox-column' => [
                        'config' => [
                            'type' => 'select',
                            'renderMode' => 'checkbox'
                        ]
                    ],
                    'an-unknown-column' => [
                        'config' => [
                            'type' => 'select',
                            'renderMode' => 'unknown'
                        ]
                    ],
                    'a-maxitems-column-not-set' => [
                        'config' => [
                            'type' => 'select',
                        ]
                    ],
                    'a-maxitems-column-0' => [
                        'config' => [
                            'type' => 'select',
                            'maxitems' => '0'
                        ]
                    ],
                    'a-maxitems-column-1' => [
                        'config' => [
                            'type' => 'select',
                            'maxitems' => '1'
                        ]
                    ],
                    'a-maxitems-column-2' => [
                        'config' => [
                            'type' => 'select',
                            'maxitems' => '2'
                        ]
                    ],
                    'a-tree-column-with-maxitems' => [
                        'config' => [
                            'type' => 'select',
                            'renderMode' => 'tree',
                            'maxitems' => '1'
                        ]
                    ],
                    'a-singlebox-column-with-maxitems' => [
                        'config' => [
                            'type' => 'select',
                            'renderMode' => 'singlebox',
                            'maxitems' => '1'
                        ]
                    ],
                    'a-checkbox-column-with-maxitems' => [
                        'config' => [
                            'type' => 'select',
                            'renderMode' => 'checkbox',
                            'maxitems' => '1'
                        ]
                    ],
                ],
            ],
        ];
        $expected = [
            'aTable-do-not-migrate-because-renderType-is-set' => [
                'columns' => [
                    'a-column' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'fooBar'
                        ]
                    ]
                ],
            ],
            'aTable-do-migrate-because-renderType-is-not-set' => [
                'columns' => [
                    'a-tree-column' => [
                        'config' => [
                            'type' => 'select',
                            'renderMode' => 'tree',
                            'renderType' => 'selectTree'
                        ]
                    ],
                    'a-singlebox-column' => [
                        'config' => [
                            'type' => 'select',
                            'renderMode' => 'singlebox',
                            'renderType' => 'selectSingleBox'
                        ]
                    ],
                    'a-checkbox-column' => [
                        'config' => [
                            'type' => 'select',
                            'renderMode' => 'checkbox',
                            'renderType' => 'selectCheckBox'
                        ]
                    ],
                    'an-unknown-column' => [
                        'config' => [
                            'type' => 'select',
                            'renderMode' => 'unknown'
                        ]
                    ],
                    'a-maxitems-column-not-set' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle'
                        ]
                    ],
                    'a-maxitems-column-0' => [
                        'config' => [
                            'type' => 'select',
                            'maxitems' => '0',
                            'renderType' => 'selectSingle'
                        ]
                    ],
                    'a-maxitems-column-1' => [
                        'config' => [
                            'type' => 'select',
                            'maxitems' => '1',
                            'renderType' => 'selectSingle'
                        ]
                    ],
                    'a-maxitems-column-2' => [
                        'config' => [
                            'type' => 'select',
                            'maxitems' => '2',
                            'renderType' => 'selectMultipleSideBySide'
                        ]
                    ],
                    'a-tree-column-with-maxitems' => [
                        'config' => [
                            'type' => 'select',
                            'renderMode' => 'tree',
                            'renderType' => 'selectTree',
                            'maxitems' => '1'
                        ]
                    ],
                    'a-singlebox-column-with-maxitems' => [
                        'config' => [
                            'type' => 'select',
                            'renderMode' => 'singlebox',
                            'renderType' => 'selectSingleBox',
                            'maxitems' => '1'
                        ]
                    ],
                    'a-checkbox-column-with-maxitems' => [
                        'config' => [
                            'type' => 'select',
                            'renderMode' => 'checkbox',
                            'renderType' => 'selectCheckBox',
                            'maxitems' => '1'
                        ]
                    ],
                ],
            ],
        ];
        $subject = new TcaMigration();
        $this->assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @return array
     */
    public function migrateSetsShowIconTableIfMissingDataProvider()
    {
        return [
            'not-a-select-is-kept' => [
                [
                    // Given config section
                    'type' => 'input',
                ],
                [
                    // Expected config section
                    'type' => 'input',
                ],
            ],
            'not-a-selectSingle-is-kept' => [
                [
                    'type' => 'select',
                    'renderType' => 'selectCheckBox',
                ],
                [
                    'type' => 'select',
                    'renderType' => 'selectCheckBox',
                ],
            ],
            'noIconsBelowSelect-true-is-removed' => [
                [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'noIconsBelowSelect' => true,
                ],
                [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                ],
            ],
            'noIconsBelowSelect-false-is-removed-sets-showIconTable' => [
                [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'noIconsBelowSelect' => false,
                ],
                [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'showIconTable' => true,
                ],
            ],
            'noIconsBelowSelect-false-is-removed-keeps-given-showIconTable' => [
                [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'noIconsBelowSelect' => false,
                    'showIconTable' => false,
                ],
                [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'showIconTable' => false,
                ],
            ],
            'suppress-icons-1-is-removed' => [
                [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'suppress_icons' => '1',
                ],
                [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                ],
            ],
            'suppress-icons-value-is-removed' => [
                [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'suppress_icons' => 'IF_VALUE_FALSE',
                ],
                [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                ],
            ],
            'selicon-cols-sets-showIconTable' => [
                [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'selicon_cols' => 16,
                ],
                [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'selicon_cols' => 16,
                    'showIconTable' => true,
                ],
            ],
            'selicon-cols-does-not-override-given-showIconTable' => [
                [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'selicon_cols' => 16,
                    'showIconTable' => false,
                ],
                [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'selicon_cols' => 16,
                    'showIconTable' => false,
                ],
            ],
            'foreign_table_loadIcons-is-removed' => [
                [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'foreign_table_loadIcons' => true,
                ],
                [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider migrateSetsShowIconTableIfMissingDataProvider
     * @param array $givenConfig
     * @param array $expectedConfig
     */
    public function migrateSetsShowIconTableIfMissing(array $givenConfig, array $expectedConfig)
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'aField' => [
                        'config' => $givenConfig,
                    ]
                ],
            ],
        ];
        $expected = $input;
        $expected['aTable']['columns']['aField']['config'] = $expectedConfig;

        $subject = new TcaMigration();
        $this->assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function migrateFixesReferenceToLinkHandler()
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'aCol' => [
                        'config' => [
                            'wizards' => [
                                'link' => [
                                    'module' => [
                                        'name' => 'wizard_element_browser',
                                        'urlParameters' => [
                                            'mode' => 'wizard'
                                        ]
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $expected = [
            'aTable' => [
                'columns' => [
                    'aCol' => [
                        'config' => [
                            'wizards' => [
                                'link' => [
                                    'module' => [
                                        'name' => 'wizard_link',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $subject = new TcaMigration();
        $this->assertEquals($expected, $subject->migrate($input));
    }
}

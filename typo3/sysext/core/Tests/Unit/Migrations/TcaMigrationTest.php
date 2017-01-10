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

/**
 * Test case
 */
class TcaMigrationTest extends \TYPO3\CMS\Components\TestingFramework\Core\UnitTestCase
{
    /**
     * @test
     */
    public function missingTypeThrowsException()
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'field_a' => [
                        'label' => 'aLabel',
                        'config' => [
                            'type' => 'text',
                        ],
                    ],
                    'field_b' => [
                        'label' => 'bLabel',
                        'config' => [
                            'rows' => 42,
                            'wizards' => []
                        ],
                    ],
                ],
            ],
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1482394401);
        $subject = new TcaMigration();
        $subject->migrate($input);
    }

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
                        'exclude' => true,
                        'label' => 'aLabel',
                        'config' => [
                            'type' => 'text',
                            'rows' => 42,
                            'wizards' => [
                                't3editor' => [
                                    'type' => 'userFunc',
                                    'userFunc' => 'TYPO3\CMS\T3editor\FormWizard->main',
                                    'title' => 't3editor',
                                    'icon' => 'content-table',
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
                        'exclude' => true,
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
                        'exclude' => true,
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
                                    'icon' => 'content-table',
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
                                    'icon' => 'content-table',
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
                        'exclude' => true,
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
                        'exclude' => true,
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
                                    'icon' => 'content-table',
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
                        'exclude' => true,
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
                            'type' => 'text',
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
                            'type' => 'text',
                            'wizards' => [
                                't3editorHtml' => [
                                    'icon' => 'content-table',
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
                            'maxitems' => 1,
                            'renderType' => 'selectSingle'
                        ]
                    ],
                    'a-maxitems-column-2' => [
                        'config' => [
                            'type' => 'select',
                            'maxitems' => 2,
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
                            'type' => 'input',
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
                            'type' => 'input',
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

    /**
     * @return array
     */
    public function migrateRemovesRteTransformOptionsDataProvider()
    {
        return [
            'remove empty options in columns' => [
                [
                    // Given config section
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'defaultExtras' => 'richtext:rte_transform[]'
                            ]
                        ]
                    ]
                ],
                [
                    // Expected config section
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'defaultExtras' => 'richtext:rte_transform'
                            ]
                        ]
                    ]
                ],
            ],
            'remove nothing in columns' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'defaultExtras' => 'richtext:rte_transform'
                            ]
                        ]
                    ]
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'defaultExtras' => 'richtext:rte_transform'
                            ]
                        ]
                    ]
                ],
            ],
            'remove mode in columns' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'defaultExtras' => 'richtext:rte_transform[mode=ts_css]'
                            ]
                        ]
                    ]
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'defaultExtras' => 'richtext:rte_transform'
                            ]
                        ]
                    ]
                ],
            ],
            'remove flag and mode in columns' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'defaultExtras' => 'richtext:rte_transform[flag=rte_enabled|mode=ts_css]'
                            ]
                        ]
                    ]
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'defaultExtras' => 'richtext:rte_transform'
                            ]
                        ]
                    ]
                ],
            ],
            'remove flag and mode in columns with array notation' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'defaultExtras' => 'richtext[]:rte_transform[flag=rte_enabled|mode=ts_css]'
                            ]
                        ]
                    ]
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'defaultExtras' => 'richtext[]:rte_transform'
                            ]
                        ]
                    ]
                ],
            ],
            'remove flag and mode in columns with array notation and index' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'defaultExtras' => 'richtext[*]:rte_transform[flag=rte_enabled|mode=ts_css]'
                            ]
                        ]
                    ]
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'defaultExtras' => 'richtext[*]:rte_transform'
                            ]
                        ]
                    ]
                ],
            ],
            'remove flag and mode in columns with array notation and index and option list' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'defaultExtras' => 'richtext[cut|copy|paste]:rte_transform[flag=rte_enabled|mode=ts_css]'
                            ]
                        ]
                    ]
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'defaultExtras' => 'richtext[cut|copy|paste]:rte_transform'
                            ]
                        ]
                    ]
                ],
            ],
            'remove empty options in columnsOverrides' => [
                [
                    'aTable' => [
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'defaultExtras' => 'richtext:rte_transform[]'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'aTable' => [
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'defaultExtras' => 'richtext:rte_transform'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            'remove nothing in columnsOverrides' => [
                [
                    'aTable' => [
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'defaultExtras' => 'richtext:rte_transform'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'aTable' => [
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'defaultExtras' => 'richtext:rte_transform'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            'remove mode in columnsOverrides' => [
                [
                    'aTable' => [
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'defaultExtras' => 'richtext:rte_transform[mode=ts_css]'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'aTable' => [
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'defaultExtras' => 'richtext:rte_transform'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            'remove flag and mode in columnsOverrides' => [
                [
                    'aTable' => [
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'defaultExtras' => 'richtext:rte_transform[flag=rte_enabled|mode=ts_css]'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'aTable' => [
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'defaultExtras' => 'richtext:rte_transform'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            'remove flag and mode in columnsOverrides with array notation' => [
                [
                    'aTable' => [
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'defaultExtras' => 'richtext[]:rte_transform[flag=rte_enabled|mode=ts_css]'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'aTable' => [
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'defaultExtras' => 'richtext[]:rte_transform'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            'remove flag and mode in columnsOverrides with array notation and index' => [
                [
                    'aTable' => [
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'defaultExtras' => 'richtext[*]:rte_transform[flag=rte_enabled|mode=ts_css]'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'aTable' => [
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'defaultExtras' => 'richtext[*]:rte_transform'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            'remove flag and mode in columnsOverrides with array notation and index and option list' => [
                [
                    'aTable' => [
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'defaultExtras' => 'richtext[copy|cut|paste]:rte_transform[flag=rte_enabled|mode=ts_css]'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'aTable' => [
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'defaultExtras' => 'richtext[copy|cut|paste]:rte_transform'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider migrateRemovesRteTransformOptionsDataProvider
     * @param array $givenConfig
     * @param array $expectedConfig
     */
    public function migrateRemovesRteTransformOptions(array $givenConfig, array $expectedConfig)
    {
        $subject = new TcaMigration();
        $this->assertEquals($expectedConfig, $subject->migrate($givenConfig));
    }

    /**
     * @test
     */
    public function migrateRewritesColorpickerWizard()
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'aCol' => [
                        'config' => [
                            'type' => 'input',
                            'wizards' => [
                                'colorpicker' => [
                                    'type' => 'colorbox',
                                    'title' => 'Color picker',
                                    'module' => [
                                        'name' => 'wizard_colorpicker',
                                    ],
                                    'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1',
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
                            'type' => 'input',
                            'renderType' => 'colorpicker',
                        ],
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
    public function migrateSelectTreeOptionsDataProvider()
    {
        return [
            'remove width' => [
                [
                    // Given config section
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'select',
                                    'renderType' => 'selectTree',
                                    'treeConfig' => [
                                        'appearance' => [
                                            'width' => 200
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    // Expected config section
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'select',
                                    'renderType' => 'selectTree',
                                    'treeConfig' => [
                                        'appearance' => [
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'remove allowRecursiveMode' => [
                [
                    // Given config section
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'select',
                                    'renderType' => 'selectTree',
                                    'treeConfig' => [
                                        'appearance' => [
                                            'someKey' => 'value',
                                            'allowRecursiveMode' => true
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    // Expected config section
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'select',
                                    'renderType' => 'selectTree',
                                    'treeConfig' => [
                                        'appearance' => [
                                            'someKey' => 'value'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'move autoSizeMax to size' => [
                [
                    // Given config section
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'select',
                                    'renderType' => 'selectTree',
                                    'autoSizeMax' => 20,
                                    'size' => 10
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    // Expected config section
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'select',
                                    'renderType' => 'selectTree',
                                    'size' => 20
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'keep settings for non selectTree' => [
                [
                    // Given config section
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'select',
                                    'renderType' => 'not a select tree',
                                    'autoSizeMax' => 20,
                                    'size' => 10,
                                    'treeConfig' => [
                                        'appearance' => [
                                            'someKey' => 'value',
                                            'allowRecursiveMode' => true,
                                            'width' => 200
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    // Expected config section
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'select',
                                    'renderType' => 'not a select tree',
                                    'autoSizeMax' => 20,
                                    'size' => 10,
                                    'treeConfig' => [
                                        'appearance' => [
                                            'someKey' => 'value',
                                            'allowRecursiveMode' => true,
                                            'width' => 200
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider migrateSelectTreeOptionsDataProvider
     * @param array $input
     * @param array $expected
     */
    public function migrateSelectTreeOptions(array $input, array $expected)
    {
        $subject = new TcaMigration();
        $this->assertEquals($expected, $subject->migrate($input));
    }

    public function migrateTsTemplateSoftReferencesDataProvider()
    {
        return [
            'nothing removed' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aCol' => [
                                'config' => [
                                    'type' => 'input',
                                    'softref' => 'email,somethingelse'
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aCol' => [
                                'config' => [
                                    'type' => 'input',
                                    'softref' => 'email,somethingelse',
                                ],
                            ],
                        ],
                    ],
                ]
            ],
            'TStemplate only' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aCol' => [
                                'config' => [
                                    'type' => 'input',
                                    'softref' => 'TStemplate,somethingelse'
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aCol' => [
                                'config' => [
                                    'type' => 'input',
                                    'softref' => 'somethingelse',
                                ],
                            ],
                        ],
                    ],
                ]
            ],
            'TStemplate and TSconfig' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aCol' => [
                                'config' => [
                                    'type' => 'input',
                                    'softref' => 'TStemplate,somethingelse,TSconfig'
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aCol' => [
                                'config' => [
                                    'type' => 'input',
                                    'softref' => 'somethingelse',
                                ],
                            ],
                        ],
                    ],
                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider migrateTsTemplateSoftReferencesDataProvider
     * @param array $givenConfig
     * @param array $expectedConfig
     */
    public function migrateTsTemplateSoftReferences(array $givenConfig, array $expectedConfig)
    {
        $subject = new TcaMigration();
        $this->assertEquals($expectedConfig, $subject->migrate($givenConfig));
    }

    public function migrateShowIfRTESettingDataProvider()
    {
        return [
            'nothing removed' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aCol' => [
                                'config' => [
                                    'type' => 'check'
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aCol' => [
                                'config' => [
                                    'type' => 'check'
                                ],
                            ],
                        ],
                    ],
                ]
            ],
            'Option removed' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aCol' => [
                                'config' => [
                                    'type' => 'check',
                                    'showIfRTE' => false
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aCol' => [
                                'config' => [
                                    'type' => 'check'
                                ],
                            ],
                        ],
                    ],
                ]

            ],
        ];
    }

    /**
     * @test
     * @dataProvider migrateShowIfRTESettingDataProvider
     * @param array $givenConfig
     * @param array $expectedConfig
     */
    public function migrateShowIfRTESetting(array $givenConfig, array $expectedConfig)
    {
        $subject = new TcaMigration();
        $this->assertEquals($expectedConfig, $subject->migrate($givenConfig));
    }

    public function migrateWorkspaceSettingsDataProvider()
    {
        return [
            'no workspaces enabled' => [
                [
                    'aTable' => [
                        'ctrl' => [
                            'versioningWS' => false
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'ctrl' => [
                            'versioningWS' => false
                        ],
                    ],
                ]
            ],
            'nothing activated' => [
                [
                    'aTable' => [
                        'ctrl' => [
                            'label' => 'blabla'
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'ctrl' => [
                            'label' => 'blabla'
                        ],
                    ],
                ]
            ],
            'nothing changed, workspaces enabled' => [
                [
                    'aTable' => [
                        'ctrl' => [
                            'versioningWS' => true
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'ctrl' => [
                            'versioningWS' => true
                        ],
                    ],
                ]
            ],
            'cast workspaces to bool' => [
                [
                    'aTable' => [
                        'ctrl' => [
                            'versioningWS' => 1
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'ctrl' => [
                            'versioningWS' => true
                        ],
                    ],
                ]
            ],
            'cast workspaces v2 to bool' => [
                [
                    'aTable' => [
                        'ctrl' => [
                            'versioningWS' => 2
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'ctrl' => [
                            'versioningWS' => true
                        ],
                    ],
                ]
            ],
            'cast workspaces v2 to bool and remove followpages' => [
                [
                    'aTable' => [
                        'ctrl' => [
                            'versioningWS' => 2,
                            'versioning_followPages' => true
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'ctrl' => [
                            'versioningWS' => true
                        ],
                    ],
                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider migrateWorkspaceSettingsDataProvider
     * @param array $givenConfig
     * @param array $expectedConfig
     */
    public function migrateWorkspaceSettings(array $givenConfig, array $expectedConfig)
    {
        $subject = new TcaMigration();
        $this->assertEquals($expectedConfig, $subject->migrate($givenConfig));
    }

    /**
     * @return array
     */
    public function migrateTranslationTableDataProvider()
    {
        return [
            'remove transForeignTable' => [
                [
                    'aTable' => [
                        'ctrl' => [
                            'transForeignTable' => 'pages_language_overlay',
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'ctrl' => [],
                    ],
                ]
            ],
            'remove transOrigPointerTable' => [
                [
                    'aTable' => [
                        'ctrl' => [
                            'transOrigPointerTable' => 'pages',
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'ctrl' => [],
                    ],
                ]
            ]
        ];
    }

    /**
     * @param array $givenConfig
     * @param array $expectedConfig
     * @test
     * @dataProvider migrateTranslationTableDataProvider
     */
    public function migrateTranslationTable(array $givenConfig, array $expectedConfig)
    {
        $subject = new TcaMigration();
        $this->assertEquals($expectedConfig, $subject->migrate($givenConfig));
    }

    /**
     * @return array
     */
    public function migrateL10nModeDefinitionsDataProvider()
    {
        return [
            'remove l10n_mode noCopy' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aColumn' => [
                                'l10n_mode' => 'noCopy',
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aColumn' => [
                            ],
                        ],
                    ],
                ]
            ],
        ];
    }

    /**
     * @param array $givenConfig
     * @param array $expectedConfig
     * @test
     * @dataProvider migrateTranslationTableDataProvider
     */
    public function migrateL10nModeDefinitions(array $givenConfig, array $expectedConfig)
    {
        $subject = new TcaMigration();
        $this->assertEquals($expectedConfig, $subject->migrate($givenConfig));
    }

    /**
     * @return array
     */
    public function migrateMovesRequestUpdateCtrlFieldToColumnsDataProvider()
    {
        return [
            'move single field name' => [
                [
                    'aTable' => [
                        'ctrl' => [
                            'requestUpdate' => 'aField',
                        ],
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'input',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'ctrl' => [],
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'input',
                                ],
                                'onChange' => 'reload',
                            ],
                        ],
                    ],
                ],
            ],
            'ignore missing field but migrate others' => [
                [
                    'aTable' => [
                        'ctrl' => [
                            'requestUpdate' => 'aField, bField, cField, ',
                        ],
                        'columns' => [
                            'aField' => [],
                            'cField' => [],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'ctrl' => [],
                        'columns' => [
                            'aField' => [
                                'onChange' => 'reload',
                            ],
                            'cField' => [
                                'onChange' => 'reload',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array $input
     * @param array $expected
     * @test
     * @dataProvider migrateMovesRequestUpdateCtrlFieldToColumnsDataProvider
     */
    public function migrateMovesRequestUpdateCtrlFieldToColumns(array $input, array $expected)
    {
        $this->assertEquals($expected, (new TcaMigration())->migrate($input));
    }
}

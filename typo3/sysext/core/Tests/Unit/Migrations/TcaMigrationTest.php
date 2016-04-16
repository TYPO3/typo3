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
        $input = $expected = array(
            'aTable' => array(
                'ctrl' => array(
                    'aKey' => 'aValue',
                ),
                'columns' => array(
                    'aField' => array(
                        'label' => 'foo',
                        'config' => array(
                            'type' => 'aType',
                            'lolli' => 'did this',
                        )
                    ),
                ),
                'types' => array(
                    0 => array(
                        'showitem' => 'this,should;stay,this,too',
                    ),
                ),
            ),
        );
        $subject = new TcaMigration();
        $this->assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function migrateChangesT3editorWizardToT3editorRenderTypeIfNotEnabledByTypeConfig()
    {
        $input = array(
            'aTable' => array(
                'columns' => array(
                    'bodytext' => array(
                        'exclude' => 1,
                        'label' => 'aLabel',
                        'config' => array(
                            'type' => 'text',
                            'rows' => 42,
                            'wizards' => array(
                                't3editor' => array(
                                    'type' => 'userFunc',
                                    'userFunc' => 'TYPO3\CMS\T3editor\FormWizard->main',
                                    'title' => 't3editor',
                                    'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_table.gif',
                                    'module' => array(
                                        'name' => 'wizard_table'
                                    ),
                                    'params' => array(
                                        'format' => 'html',
                                        'style' => 'width:98%; height: 60%;'
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        );
        $expected = array(
            'aTable' => array(
                'columns' => array(
                    'bodytext' => array(
                        'exclude' => 1,
                        'label' => 'aLabel',
                        'config' => array(
                            'type' => 'text',
                            'renderType' => 't3editor',
                            'format' => 'html',
                            'rows' => 42,
                        ),
                    ),
                ),
            ),
        );
        $subject = new TcaMigration();
        $this->assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function migrateDropsStylePointerFromShowItem()
    {
        $input = array(
            'aTable' => array(
                'types' => array(
                    0 => array(
                        'showitem' => 'aField,anotherField;with;;;style-pointer,thirdField',
                    ),
                    1 => array(
                        'showitem' => 'aField,;;;;only-a-style-pointer,anotherField',
                    ),
                ),
            ),
        );
        $expected = array(
            'aTable' => array(
                'types' => array(
                    0 => array(
                        'showitem' => 'aField,anotherField;with,thirdField',
                    ),
                    1 => array(
                        'showitem' => 'aField,anotherField',
                    ),
                ),
            ),
        );
        $subject = new TcaMigration();
        $this->assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function migrateMovesSpecialConfigurationToColumnsOverridesDefaultExtras()
    {
        $input = array(
            'aTable' => array(
                'types' => array(
                    0 => array(
                        'showitem' => 'aField,anotherField;with;;special:configuration,thirdField',
                    ),
                ),
            ),
        );
        $expected = array(
            'aTable' => array(
                'types' => array(
                    0 => array(
                        'showitem' => 'aField,anotherField;with,thirdField',
                        'columnsOverrides' => array(
                            'anotherField' => array(
                                'defaultExtras' => 'special:configuration',
                            ),
                        ),
                    ),
                ),
            ),
        );
        $subject = new TcaMigration();
        $this->assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function migrateMovesSpecialConfigurationToColumnsOverridesDefaultExtrasAndMergesExistingDefaultExtras()
    {
        $input = array(
            'aTable' => array(
                'columns' => array(
                    'anotherField' => array(
                        'defaultExtras' => 'some:values',
                    ),
                ),
                'types' => array(
                    0 => array(
                        'showitem' => 'aField,anotherField;with;;special:configuration,thirdField',
                    ),
                ),
            ),
        );
        $expected = array(
            'aTable' => array(
                'columns' => array(
                    'anotherField' => array(
                        'defaultExtras' => 'some:values',
                    ),
                ),
                'types' => array(
                    0 => array(
                        'showitem' => 'aField,anotherField;with,thirdField',
                        'columnsOverrides' => array(
                            'anotherField' => array(
                                'defaultExtras' => 'some:values:special:configuration',
                            ),
                        ),
                    ),
                ),
            ),
        );
        $subject = new TcaMigration();
        $this->assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function migrateChangesT3editorWizardThatIsEnabledByTypeConfigToRenderTypeInColmnnsOverrides()
    {
        $input = array(
            'aTable' => array(
                'columns' => array(
                    'bodytext' => array(
                        'exclude' => 1,
                        'label' => 'aLabel',
                        'config' => array(
                            'type' => 'text',
                            'rows' => 42,
                            'wizards' => array(
                                't3editorHtml' => array(
                                    'type' => 'userFunc',
                                    'userFunc' => 'TYPO3\CMS\T3editor\FormWizard->main',
                                    'enableByTypeConfig' => 1,
                                    'title' => 't3editor',
                                    'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_table.gif',
                                    'module' => array(
                                        'name' => 'wizard_table'
                                    ),
                                    'params' => array(
                                        'format' => 'html',
                                        'style' => 'width:98%; height: 60%;'
                                    ),
                                ),
                                't3editorTypoScript' => array(
                                    'type' => 'userFunc',
                                    'userFunc' => 'TYPO3\CMS\T3editor\FormWizard->main',
                                    'enableByTypeConfig' => 1,
                                    'title' => 't3editor',
                                    'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_table.gif',
                                    'module' => array(
                                        'name' => 'wizard_table'
                                    ),
                                    'params' => array(
                                        'format' => 'typoscript',
                                        'style' => 'width:98%; height: 60%;'
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
                'types' => array(
                    'firstType' => array(
                        'showitem' => 'foo,bodytext;;;wizards[t3editorTypoScript|someOtherWizard],bar',
                    ),
                    'secondType' => array(
                        'showitem' => 'foo,bodytext;;;nowrap:wizards[t3editorHtml], bar',
                    ),
                ),
            ),
        );
        $expected = array(
            'aTable' => array(
                'columns' => array(
                    'bodytext' => array(
                        'exclude' => 1,
                        'label' => 'aLabel',
                        'config' => array(
                            'type' => 'text',
                            'rows' => 42,
                        ),
                    ),
                ),
                'types' => array(
                    'firstType' => array(
                        'showitem' => 'foo,bodytext,bar',
                        'columnsOverrides' => array(
                            'bodytext' => array(
                                'config' => array(
                                    'format' => 'typoscript',
                                    'renderType' => 't3editor',
                                ),
                                'defaultExtras' => 'wizards[someOtherWizard]',
                            ),
                        ),
                    ),
                    'secondType' => array(
                        'showitem' => 'foo,bodytext,bar',
                        'columnsOverrides' => array(
                            'bodytext' => array(
                                'config' => array(
                                    'format' => 'html',
                                    'renderType' => 't3editor',
                                ),
                                'defaultExtras' => 'nowrap',
                            ),
                        ),
                    ),
                ),
            ),
        );
        $subject = new TcaMigration();
        $this->assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function migrateRemovesAnUnusedT3edtiorDefinitionIfEnabledByTypeConfig()
    {
        $input = array(
            'aTable' => array(
                'columns' => array(
                    'bodytext' => array(
                        'exclude' => 1,
                        'label' => 'aLabel',
                        'config' => array(
                            'type' => 'text',
                            'rows' => 42,
                            'wizards' => array(
                                't3editorHtml' => array(
                                    'type' => 'userFunc',
                                    'userFunc' => 'TYPO3\CMS\T3editor\FormWizard->main',
                                    'enableByTypeConfig' => 1,
                                    'title' => 't3editor',
                                    'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_table.gif',
                                    'module' => array(
                                        'name' => 'wizard_table'
                                    ),
                                    'params' => array(
                                        'format' => 'html',
                                        'style' => 'width:98%; height: 60%;'
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        );
        $expected = array(
            'aTable' => array(
                'columns' => array(
                    'bodytext' => array(
                        'exclude' => 1,
                        'label' => 'aLabel',
                        'config' => array(
                            'type' => 'text',
                            'rows' => 42,
                        ),
                    ),
                ),
            ),
        );
        $subject = new TcaMigration();
        $this->assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function migrateSpecialConfigurationAndRemoveShowItemStylePointerConfigDoesNotAddMessageIfOnlySyntaxChanged()
    {
        $input = array(
            'aTable' => array(
                'columns' => array(
                    'anotherField' => array(
                    ),
                ),
                'types' => array(
                    0 => array(
                        'showitem' => 'aField;;;',
                    ),
                    1 => array()
                ),
            ),
        );
        $subject = new TcaMigration();
        $subject->migrate($input);
        $this->assertEmpty($subject->getMessages());
    }

    /**
     * @test
     */
    public function migrateShowItemMovesAdditionalPaletteToOwnPaletteDefinition()
    {
        $input = array(
            'aTable' => array(
                'types' => array(
                    'firstType' => array(
                        'showitem' => 'field1;field1Label,field2;fieldLabel2;palette1,field2;;palette2',
                    ),
                ),
            ),
        );
        $expected = array(
            'aTable' => array(
                'types' => array(
                    'firstType' => array(
                        'showitem' => 'field1;field1Label,field2;fieldLabel2,--palette--;;palette1,field2,--palette--;;palette2',
                    ),
                ),
            ),
        );
        $subject = new TcaMigration();
        $this->assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function migrateIconsForFormFieldWizardsToNewLocation()
    {
        $input = array(
            'aTable' => array(
                'columns' => array(
                    'bodytext' => array(
                        'config' => array(
                            'wizards' => array(
                                't3editorHtml' => array(
                                    'icon' => 'wizard_table.gif',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        );

        $expected = array(
            'aTable' => array(
                'columns' => array(
                    'bodytext' => array(
                        'config' => array(
                            'wizards' => array(
                                't3editorHtml' => array(
                                    'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_table.gif',
                                ),
                            ),
                        ),
                    ),
                ),
            ),
        );

        $subject = new TcaMigration();
        $this->assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function migrateExtAndSysextPathToEXTPath()
    {
        $input = array(
            'aTable' => array(
                'columns' => array(
                    'foo' => array(
                        'config' => array(
                            'type' => 'select',
                            'items' => array(
                                array('foo', 0, 'ext/myext/foo/bar.gif'),
                                array('bar', 1, 'sysext/myext/foo/bar.gif'),
                            ),
                        ),
                    ),
                ),
            ),
        );

        $expected = array(
            'aTable' => array(
                'columns' => array(
                    'foo' => array(
                        'config' => array(
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => array(
                                array('foo', 0, 'EXT:myext/foo/bar.gif'),
                                array('bar', 1, 'EXT:myext/foo/bar.gif'),
                            ),
                        ),
                    ),
                ),
            ),
        );

        $subject = new TcaMigration();
        $this->assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function migratePathWhichStartsWithIToEXTPath()
    {
        $input = array(
            'aTable' => array(
                'columns' => array(
                    'foo' => array(
                        'config' => array(
                            'type' => 'select',
                            'items' => array(
                                array('foo', 0, 'i/tt_content.gif'),
                            ),
                        ),
                    ),
                ),
            ),
        );

        $expected = array(
            'aTable' => array(
                'columns' => array(
                    'foo' => array(
                        'config' => array(
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => array(
                                array('foo', 0, 'EXT:t3skin/icons/gfx/i/tt_content.gif'),
                            ),
                        ),
                    ),
                ),
            ),
        );

        $subject = new TcaMigration();
        $this->assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function migrateRemovesIconsInOptionTags()
    {
        $input = array(
            'aTable' => array(
                'columns' => array(
                    'foo' => array(
                        'config' => array(
                            'type' => 'select',
                            'iconsInOptionTags' => 1,
                        ),
                    ),
                ),
            ),
        );
        $expected = array(
            'aTable' => array(
                'columns' => array(
                    'foo' => array(
                        'config' => array(
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                        ),
                    ),
                ),
            ),
        );

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
                            'renderType' => 'colorpicker',
                        ],
                    ],
                ],
            ],
        ];

        $subject = new TcaMigration();
        $this->assertEquals($expected, $subject->migrate($input));
    }
}

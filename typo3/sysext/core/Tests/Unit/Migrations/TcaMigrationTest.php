<?php
declare(strict_types = 1);
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
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class TcaMigrationTest extends UnitTestCase
{
    /**
     * @test
     */
    public function missingTypeThrowsException(): void
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
    public function migrateReturnsGivenArrayUnchangedIfNoMigrationNeeded(): void
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
    public function migrateAddsMissingColumnsConfig(): void
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'aField' => [
                        'exclude' => true,
                    ],
                    'bField' => [
                    ],
                    'cField' => [
                        'config' => 'i am a string but should be an array',
                    ],
                    'dField' => [
                        // This kept as is, 'config' is not added. This is relevant
                        // for "flex" data structure arrays with section containers
                        // that have 'type'=>'array' on this level and an 'el' sub array
                        // with details.
                        'type' => 'array',
                    ],
                ]
            ],
        ];
        $expected = [
            'aTable' => [
                'columns' => [
                    'aField' => [
                        'exclude' => true,
                        'config' => [
                            'type' => 'none',
                        ],
                    ],
                    'bField' => [
                        'config' => [
                            'type' => 'none',
                        ],
                    ],
                    'cField' => [
                        'config' => [
                            'type' => 'none',
                        ],
                    ],
                    'dField' => [
                        'type' => 'array',
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
    public function migrateRemovesAnUnusedT3edtiorDefinitionIfEnabledByTypeConfig(): void
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
    public function migrateSpecialConfigurationAndRemoveShowItemStylePointerConfigDoesNotAddMessageIfOnlySyntaxChanged(): void
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'anotherField' => [
                        'config' => [
                            'type' => 'text',
                        ],
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
    public function migrateKeepsGivenExtensionReference(): void
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
     * @return array
     */
    public function migrateRemovesRteTransformOptionsDataProvider(): array
    {
        return [
            'columns richtext configuration' => [
                [
                    // Given config section
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'text',
                                ],
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
                                'config' => [
                                    'type' => 'text',
                                    'enableRichtext' => true,
                                    'richtextConfiguration' => 'default',
                                ],
                            ]
                        ]
                    ]
                ],
            ],
            'columns richtext configuration without bracket' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'text',
                                ],
                                'defaultExtras' => 'richtext:rte_transform'
                            ]
                        ]
                    ]
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'text',
                                    'enableRichtext' => true,
                                    'richtextConfiguration' => 'default',
                                ],
                            ]
                        ]
                    ]
                ],
            ],
            'columns richtext with mode' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'text',
                                ],
                                'defaultExtras' => 'richtext:rte_transform[mode=ts_css]'
                            ]
                        ]
                    ]
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'text',
                                    'enableRichtext' => true,
                                    'richtextConfiguration' => 'default',
                                ],
                            ]
                        ]
                    ]
                ],
            ],
            'columns richtext with mode and others' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'text',
                                ],
                                'defaultExtras' => 'richtext:rte_transform[flag=rte_enabled|mode=ts_css]'
                            ]
                        ]
                    ]
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'text',
                                    'enableRichtext' => true,
                                    'richtextConfiguration' => 'default',
                                ],
                            ]
                        ]
                    ]
                ],
            ],
            'columns richtext with array with mode and others' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'text',
                                ],
                                'defaultExtras' => 'richtext[]:rte_transform[flag=rte_enabled|mode=ts_css]'
                            ]
                        ]
                    ]
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'text',
                                    'enableRichtext' => true,
                                    'richtextConfiguration' => 'default',
                                ],
                            ]
                        ]
                    ]
                ],
            ],
            'columns richtext * with mode and others' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'text',
                                ],
                                'defaultExtras' => 'richtext[*]:rte_transform[flag=rte_enabled|mode=ts_css]'
                            ]
                        ]
                    ]
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'text',
                                    'enableRichtext' => true,
                                    'richtextConfiguration' => 'default',
                                ],
                            ]
                        ]
                    ]
                ],
            ],
            'columns richtext cut-copy-paste with mode and others' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'text',
                                ],
                                'defaultExtras' => 'richtext[cut|copy|paste]:rte_transform[flag=rte_enabled|mode=ts_css]'
                            ]
                        ]
                    ]
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'text',
                                    'enableRichtext' => true,
                                    'richtextConfiguration' => 'default',
                                ],
                            ]
                        ]
                    ]
                ],
            ],
            'columnsOverrides richtext with brackets' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'text',
                                ],
                            ],
                        ],
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
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'text',
                                ],
                            ],
                        ],
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'config' => [
                                            'enableRichtext' => true,
                                            'richtextConfiguration' => 'default',
                                        ],
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            'columnsOverrides richtext' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'text',
                                ],
                            ],
                        ],
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
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'text',
                                ],
                            ],
                        ],
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'config' => [
                                            'enableRichtext' => true,
                                            'richtextConfiguration' => 'default',
                                        ],
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            'columnsOverrides richtext with defalut mode' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'text',
                                ],
                            ],
                        ],
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
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'text',
                                ],
                            ],
                        ],
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'config' => [
                                            'enableRichtext' => true,
                                            'richtextConfiguration' => 'default',
                                        ],
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            'columnsOverrides richtext with mode and others' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'text',
                                ],
                            ],
                        ],
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
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'text',
                                ],
                            ],
                        ],
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'config' => [
                                            'enableRichtext' => true,
                                            'richtextConfiguration' => 'default',
                                        ],
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            'columnsOverrides richtext brackets mode and others' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'text',
                                ],
                            ],
                        ],
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
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'text',
                                ],
                            ],
                        ],
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'config' => [
                                            'enableRichtext' => true,
                                            'richtextConfiguration' => 'default',
                                        ],
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            'columnsOverrides richtext star with mode and others' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'text',
                                ],
                            ],
                        ],
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
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'text',
                                ],
                            ],
                        ],
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'config' => [
                                            'enableRichtext' => true,
                                            'richtextConfiguration' => 'default',
                                        ],
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            'columnsOverrides richtext cut-copy-paste ith mode and others' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'text',
                                ],
                            ],
                        ],
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
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'text',
                                ],
                            ],
                        ],
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'config' => [
                                            'enableRichtext' => true,
                                            'richtextConfiguration' => 'default',
                                        ],
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
    public function migrateRemovesRteTransformOptions(array $givenConfig, array $expectedConfig): void
    {
        $subject = new TcaMigration();
        $this->assertEquals($expectedConfig, $subject->migrate($givenConfig));
    }

    /**
     * @return array
     */
    public function migrateSelectTreeOptionsDataProvider(): array
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
    public function migrateSelectTreeOptions(array $input, array $expected): void
    {
        $subject = new TcaMigration();
        $this->assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @return array
     */
    public function migrateTsTemplateSoftReferencesDataProvider(): array
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
    public function migrateTsTemplateSoftReferences(array $givenConfig, array $expectedConfig): void
    {
        $subject = new TcaMigration();
        $this->assertEquals($expectedConfig, $subject->migrate($givenConfig));
    }

    /**
     * @return array
     */
    public function migrateShowIfRTESettingDataProvider(): array
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
    public function migrateShowIfRTESetting(array $givenConfig, array $expectedConfig): void
    {
        $subject = new TcaMigration();
        $this->assertEquals($expectedConfig, $subject->migrate($givenConfig));
    }

    /**
     * @return array
     */
    public function migrateWorkspaceSettingsDataProvider(): array
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
    public function migrateWorkspaceSettings(array $givenConfig, array $expectedConfig): void
    {
        $subject = new TcaMigration();
        $this->assertEquals($expectedConfig, $subject->migrate($givenConfig));
    }

    /**
     * @return array
     */
    public function migrateTranslationTableDataProvider(): array
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
    public function migrateTranslationTable(array $givenConfig, array $expectedConfig): void
    {
        $subject = new TcaMigration();
        $this->assertEquals($expectedConfig, $subject->migrate($givenConfig));
    }

    /**
     * @return array
     */
    public function migrateL10nModeDefinitionsDataProvider(): array
    {
        return [
            'remove l10n_mode noCopy' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aColumn' => [
                                'l10n_mode' => 'noCopy',
                                'config' => [
                                    'type' => 'text',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aColumn' => [
                                'config' => [
                                    'type' => 'text',
                                ],
                            ],
                        ],
                    ],
                ]
            ],
            'remove l10n_mode mergeIfNotBlank' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aColumn' => [
                                'l10n_mode' => 'mergeIfNotBlank',
                                'config' => [
                                    'type' => 'text',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aColumn' => [
                                'config' => [
                                    'type' => 'text',
                                    'behaviour' => [
                                        'allowLanguageSynchronization' => true,
                                    ]
                                ]
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
     * @dataProvider migrateL10nModeDefinitionsDataProvider
     */
    public function migrateL10nModeDefinitions(array $givenConfig, array $expectedConfig): void
    {
        $subject = new TcaMigration();
        $this->assertEquals($expectedConfig, $subject->migrate($givenConfig));
    }

    /**
     * @return array
     */
    public function migratePageLocalizationDefinitionsDataProvider(): array
    {
        return [
            'missing l10n_mode' => [
                [
                    'pages' => [
                        'columns' => [
                            'aColumn' => [
                                'config' => [
                                    'type' => 'input',
                                ],
                            ],
                        ],
                    ],
                    'pages_language_overlay' => [
                        'columns' => [
                            'aColumn' => [
                                'config' => [
                                    'type' => 'input',
                                ],
                                'l10n_mode' => 'any-possible-value',
                            ],
                        ],
                    ],
                ],
                [
                    'pages' => [
                        'columns' => [
                            'aColumn' => [
                                'config' => [
                                    'type' => 'input',
                                ],
                                'l10n_mode' => 'any-possible-value',
                            ],
                        ],
                    ],
                ]
            ],
            'missing allowLanguageSynchronization' => [
                [
                    'pages' => [
                        'columns' => [
                            'aColumn' => [
                                'config' => [
                                    'type' => 'input',
                                ],
                            ],
                        ],
                    ],
                    'pages_language_overlay' => [
                        'columns' => [
                            'aColumn' => [
                                'config' => [
                                    'type' => 'input',
                                    'behaviour' => [
                                        'allowLanguageSynchronization' => true,
                                    ]
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'pages' => [
                        'columns' => [
                            'aColumn' => [
                                'config' => [
                                    'type' => 'input',
                                    'behaviour' => [
                                        'allowLanguageSynchronization' => true,
                                    ]
                                ],
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
     * @dataProvider migratePageLocalizationDefinitionsDataProvider
     */
    public function migratePageLocalizationDefinitions(array $givenConfig, array $expectedConfig): void
    {
        $subject = new TcaMigration();
        $this->assertEquals($expectedConfig, $subject->migrate($givenConfig));
    }

    /**
     * @return array
     */
    public function migrateInlineLocalizationModeDataProvider(): array
    {
        return [
            'remove counter-productive localizationMode=keep' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aColumn' => [
                                'config' => [
                                    'type' => 'inline',
                                    'behaviour' => [
                                        'localizationMode' => 'keep',
                                        'allowLanguageSynchronization' => true,
                                    ],
                                ]
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aColumn' => [
                                'config' => [
                                    'type' => 'inline',
                                    'behaviour' => [
                                        'allowLanguageSynchronization' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            ],
            'keep deprecated localizationMode=keep' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aColumn' => [
                                'config' => [
                                    'type' => 'inline',
                                    'behaviour' => [
                                        'localizationMode' => 'keep',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aColumn' => [
                                'config' => [
                                    'type' => 'inline',
                                    'behaviour' => [
                                        'localizationMode' => 'keep',
                                    ],
                                ]
                            ],
                        ],
                    ],
                ]
            ],
            'keep deprecated localizationMode=select' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aColumn' => [
                                'config' => [
                                    'type' => 'inline',
                                    'behaviour' => [
                                        'localizationMode' => 'select',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aColumn' => [
                                'config' => [
                                    'type' => 'inline',
                                    'behaviour' => [
                                        'localizationMode' => 'select',
                                    ],
                                ]
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
     * @dataProvider migrateInlineLocalizationModeDataProvider
     */
    public function migrateInlineLocalizationMode(array $givenConfig, array $expectedConfig): void
    {
        $subject = new TcaMigration();
        $this->assertEquals($expectedConfig, $subject->migrate($givenConfig));
        $this->assertNotEmpty($subject->getMessages());
    }

    /**
     * @return array
     */
    public function migrateMovesRequestUpdateCtrlFieldToColumnsDataProvider(): array
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
                            'aField' => [
                                'config' => [
                                    'type' => 'none',
                                ],
                            ],
                            'cField' => [
                                'config' => [
                                    'type' => 'none',
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
                                'config' => [
                                    'type' => 'none',
                                ],
                                'onChange' => 'reload',
                            ],
                            'cField' => [
                                'config' => [
                                    'type' => 'none',
                                ],
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
    public function migrateMovesRequestUpdateCtrlFieldToColumns(array $input, array $expected): void
    {
        $this->assertEquals($expected, (new TcaMigration())->migrate($input));
    }

    /**
     * @return array
     */
    public function migrateMovesTypeInputDateTimeToRenderTypeDataProvider(): array
    {
        return [
            'simple input with eval date' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'input',
                                    'eval' => 'date',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'input',
                                    'eval' => 'date',
                                    'renderType' => 'inputDateTime',
                                ],
                            ],
                        ],
                    ],
                ]
            ],
            'simple input with eval datetime' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'input',
                                    'eval' => 'datetime',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'input',
                                    'eval' => 'datetime',
                                    'renderType' => 'inputDateTime',
                                ],
                            ],
                        ],
                    ],
                ]
            ],
            'simple input with eval time' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'input',
                                    'eval' => 'time',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'input',
                                    'eval' => 'time',
                                    'renderType' => 'inputDateTime',
                                ],
                            ],
                        ],
                    ],
                ]
            ],
            'simple input with eval timesec' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'input',
                                    'eval' => 'timesec',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'input',
                                    'eval' => 'timesec',
                                    'renderType' => 'inputDateTime',
                                ],
                            ],
                        ],
                    ],
                ]
            ],
            'input with multiple evals' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'input',
                                    'eval' => 'null,date, required',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'input',
                                    'eval' => 'null,date, required',
                                    'renderType' => 'inputDateTime',
                                ],
                            ],
                        ],
                    ],
                ]
            ],
        ];
    }

    /**
     * @param array $input
     * @param array $expected
     * @test
     * @dataProvider migrateMovesTypeInputDateTimeToRenderTypeDataProvider
     */
    public function migrateMovesTypeInputDateTimeToRenderType(array $input, array $expected): void
    {
        $this->assertEquals($expected, (new TcaMigration())->migrate($input));
    }

    /**
     * @return array
     */
    public function migrateMovesWizardsWithEnableByTypeConfigToColumnsOverridesDataProvider(): array
    {
        return [
            'enableByTypeConfig on multiple wizards' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'exclude' => true,
                                'label' => 'aLabel',
                                'config' => [
                                    'type' => 'text',
                                    'wizards' => [
                                        'aWizard' => [
                                            'type' => 'aType',
                                            'title' => 'aTitle',
                                            'enableByTypeConfig' => '1',
                                        ],
                                        'anotherWizard' => [
                                            'type' => 'aType',
                                            'title' => 'anotherTitle',
                                            'enableByTypeConfig' => 1,
                                        ],
                                        'yetAnotherWizard' => [
                                            'type' => 'aType',
                                            'title' => 'yetAnotherTitle',
                                        ],
                                        'andYetAnotherWizard' => [
                                            'type' => 'aType',
                                            'title' => 'yetAnotherTitle',
                                            'enableByTypeConfig' => 0,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'types' => [
                            'firstType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'defaultExtras' => 'nowrap:wizards[aWizard|anotherWizard|aNotExistingWizard]:enable-tab',
                                    ],
                                ],
                            ],
                            'secondType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'defaultExtras' => 'wizards[aWizard]',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'exclude' => true,
                                'label' => 'aLabel',
                                'config' => [
                                    'type' => 'text',
                                    'wizards' => [
                                        'yetAnotherWizard' => [
                                            'type' => 'aType',
                                            'title' => 'yetAnotherTitle',
                                        ],
                                        'andYetAnotherWizard' => [
                                            'type' => 'aType',
                                            'title' => 'yetAnotherTitle',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'types' => [
                            'firstType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'config' => [
                                            'wrap' => 'off',
                                            'enableTabulator' => true,
                                            'wizards' => [
                                                'aWizard' => [
                                                    'type' => 'aType',
                                                    'title' => 'aTitle',
                                                ],
                                                'anotherWizard' => [
                                                    'type' => 'aType',
                                                    'title' => 'anotherTitle',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'secondType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'config' => [
                                            'wizards' => [
                                                'aWizard' => [
                                                    'type' => 'aType',
                                                    'title' => 'aTitle',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'empty wizard array is removed' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'exclude' => true,
                                'label' => 'aLabel',
                                'config' => [
                                    'type' => 'input',
                                    'wizards' => [
                                        'aWizard' => [
                                            'type' => 'aType',
                                            'title' => 'aTitle',
                                            'enableByTypeConfig' => 1,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'types' => [
                            'firstType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'defaultExtras' => 'wizards[aWizard]',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'exclude' => true,
                                'label' => 'aLabel',
                                'config' => [
                                    'type' => 'input',
                                ],
                            ],
                        ],
                        'types' => [
                            'firstType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'config' => [
                                            'wizards' => [
                                                'aWizard' => [
                                                    'type' => 'aType',
                                                    'title' => 'aTitle',
                                                ],
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
    }

    /**
     * @param array $input
     * @param array $expected
     * @test
     * @dataProvider migrateMovesWizardsWithEnableByTypeConfigToColumnsOverridesDataProvider
     */
    public function migrateMovesWizardsWithEnableByTypeConfigToColumnsOverrides(array $input, array $expected): void
    {
        $this->assertEquals($expected, (new TcaMigration())->migrate($input));
    }

    /**
     * @return array
     */
    public function migrateRewritesColorpickerWizardDataProvider(): array
    {
        return [
            'colorpicker in columns field' => [
                [
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
                ],
                [
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
                ],
            ],
            'colorpicker is not migrated if custom renderType is already given' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aCol' => [
                                'config' => [
                                    'type' => 'input',
                                    'renderType' => 'myPersonalRenderType',
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
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aCol' => [
                                'config' => [
                                    'type' => 'input',
                                    'renderType' => 'myPersonalRenderType',
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
                ],
            ],
            'colorpicker in a type columnsOverrides field' => [
                [
                    'aTable' => [
                        'columns' => [
                          'aField' => [
                              'config' => [
                                  'type' => 'input',
                              ]
                          ]
                        ],
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
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
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'input',
                                ]
                            ]
                        ],
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'config' => [
                                            'type' => 'input',
                                            'renderType' => 'colorpicker',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            ]
        ];
    }

    /**
     * @param array $input
     * @param array $expected
     * @test
     * @dataProvider migrateRewritesColorpickerWizardDataProvider
     */
    public function migrateRewritesColorpickerWizard(array $input, array $expected): void
    {
        $this->assertEquals($expected, (new TcaMigration())->migrate($input));
    }

    /**
     * @return array
     */
    public function migrateMovesSelectWizardToValuePickerDataProvider(): array
    {
        return [
            'select wizard without mode' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'text',
                                    'wizards' => [
                                        'target_picker' => [
                                            'type' => 'select',
                                            'items' => [
                                                [ 'aLabel', 'aValue' ],
                                                [ 'anotherLabel', 'anotherValue' ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'text',
                                    'valuePicker' => [
                                        'items' => [
                                            [ 'aLabel', 'aValue' ],
                                            [ 'anotherLabel', 'anotherValue' ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'select wizard with empty mode' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'input',
                                    'wizards' => [
                                        'target_picker' => [
                                            'type' => 'select',
                                            'mode' => '',
                                            'items' => [
                                                [ 'aLabel', 'aValue' ],
                                                [ 'anotherLabel', 'anotherValue' ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'input',
                                    'valuePicker' => [
                                        'mode' => '',
                                        'items' => [
                                            [ 'aLabel', 'aValue' ],
                                            [ 'anotherLabel', 'anotherValue' ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'select wizard with prepend mode' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'input',
                                    'wizards' => [
                                        'target_picker' => [
                                            'type' => 'select',
                                            'mode' => 'prepend',
                                            'items' => [
                                                [ 'aLabel', 'aValue' ],
                                                [ 'anotherLabel', 'anotherValue' ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'input',
                                    'valuePicker' => [
                                        'mode' => 'prepend',
                                        'items' => [
                                            [ 'aLabel', 'aValue' ],
                                            [ 'anotherLabel', 'anotherValue' ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'select wizard with append mode' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'input',
                                    'wizards' => [
                                        'target_picker' => [
                                            'type' => 'select',
                                            'mode' => 'append',
                                            'items' => [
                                                [ 'aLabel', 'aValue' ],
                                                [ 'anotherLabel', 'anotherValue' ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'input',
                                    'valuePicker' => [
                                        'mode' => 'append',
                                        'items' => [
                                            [ 'aLabel', 'aValue' ],
                                            [ 'anotherLabel', 'anotherValue' ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'select wizard with broken mode' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'input',
                                    'wizards' => [
                                        'target_picker' => [
                                            'type' => 'select',
                                            'mode' => 'foo',
                                            'items' => [
                                                [ 'aLabel', 'aValue' ],
                                                [ 'anotherLabel', 'anotherValue' ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'input',
                                    'valuePicker' => [
                                        'items' => [
                                            [ 'aLabel', 'aValue' ],
                                            [ 'anotherLabel', 'anotherValue' ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'select wizard without items is not migrated' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'input',
                                    'wizards' => [
                                        'target_picker' => [
                                            'type' => 'select',
                                            'mode' => '',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'input',
                                    'wizards' => [
                                        'target_picker' => [
                                            'type' => 'select',
                                            'mode' => '',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'select wizard with broken items is not migrated' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'input',
                                    'wizards' => [
                                        'target_picker' => [
                                            'type' => 'select',
                                            'mode' => '',
                                            'items' => 'foo',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'input',
                                    'wizards' => [
                                        'target_picker' => [
                                            'type' => 'select',
                                            'mode' => '',
                                            'items' => 'foo',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'two wizards' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'input',
                                    'wizards' => [
                                        'target_picker' => [
                                            'type' => 'select',
                                            'mode' => '',
                                            'items' => [
                                                [ 'aLabel', 'aValue' ],
                                                [ 'anotherLabel', 'anotherValue' ],
                                            ],
                                        ],
                                        'differentWizard' => [
                                            'type' => 'foo',
                                        ]
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'input',
                                    'valuePicker' => [
                                        'mode' => '',
                                        'items' => [
                                            [ 'aLabel', 'aValue' ],
                                            [ 'anotherLabel', 'anotherValue' ],
                                        ],
                                    ],
                                    'wizards' => [
                                        'differentWizard' => [
                                            'type' => 'foo',
                                        ],
                                    ]
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'select value wizard to value Picker columnsOverrides field' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'text',
                                ]
                            ]
                        ],
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'config' => [
                                            'wizards' => [
                                                'target_picker' => [
                                                    'type' => 'select',
                                                    'items' => [
                                                        [ 'aLabel', 'aValue' ],
                                                        [ 'anotherLabel', 'anotherValue' ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'text',
                                ]
                            ]
                        ],
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'config' => [
                                            'valuePicker' => [
                                                'items' => [
                                                    [ 'aLabel', 'aValue' ],
                                                    [ 'anotherLabel', 'anotherValue' ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            ]
        ];
    }

    /**
     * @param array $input
     * @param array $expected
     * @test
     * @dataProvider migrateMovesSelectWizardToValuePickerDataProvider
     */
    public function migrateMovesSelectWizardToValuePicker(array $input, array $expected): void
    {
        $this->assertEquals($expected, (new TcaMigration())->migrate($input));
    }

    /**
     * @return array
     */
    public function migrateMovesSliderWizardToSliderConfigurationDataProvider(): array
    {
        return [
            'slider wizard with no options' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'input',
                                    'wizards' => [
                                        'slider' => [
                                            'type' => 'slider',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'input',
                                    'slider' => [],
                                ],
                            ],
                        ],
                    ],
                ]
            ],
            'slider wizard with options' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'input',
                                    'wizards' => [
                                        'slider' => [
                                            'type' => 'slider',
                                            'width' => 200,
                                            'step' => 10,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'input',
                                    'slider' => [
                                        'width' => 200,
                                        'step' => 10,
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'two wizards' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'input',
                                    'wizards' => [
                                        'slider' => [
                                            'type' => 'slider',
                                            'width' => 200,
                                        ],
                                        'differentWizard' => [
                                            'type' => 'foo',
                                        ]
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'input',
                                    'slider' => [
                                        'width' => 200,
                                    ],
                                    'wizards' => [
                                        'differentWizard' => [
                                            'type' => 'foo',
                                        ],
                                    ]
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'slider wizard to columnsOverrides field' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'input',
                                ]
                            ]
                        ],
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'config' => [
                                            'wizards' => [
                                                'slider' => [
                                                    'type' => 'slider',
                                                    'width' => 200,
                                                ],
                                                'differentWizard' => [
                                                    'type' => 'foo',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'input',
                                ]
                            ]
                        ],
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'config' => [
                                            'slider' => [
                                                'width' => 200,
                                            ],
                                            'wizards' => [
                                                'differentWizard' => [
                                                    'type' => 'foo',
                                                ],
                                            ]
                                        ],
                                    ],
                                ],
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
     * @dataProvider migrateMovesSliderWizardToSliderConfigurationDataProvider
     */
    public function migrateMovesSliderWizardToSliderConfiguration(array $input, array $expected): void
    {
        $this->assertEquals($expected, (new TcaMigration())->migrate($input));
    }

    /**
     * @return array
     */
    public function migrateMovesLinkWizardToRenderTypeWithFieldControlDataProvider(): array
    {
        return [
            'simple link wizard without options' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'input',
                                    'wizards' => [
                                        'link' => [
                                            'type' => 'popup',
                                            'module' => [
                                                'name' => 'wizard_link',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'input',
                                    'renderType' => 'inputLink',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'link wizard with options' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'input',
                                    'wizards' => [
                                        'link' => [
                                            'type' => 'popup',
                                            'title' => 'aLinkTitle',
                                            'module' => [
                                                'name' => 'wizard_link',
                                            ],
                                            'JSopenParams' => 'height=800,width=600,status=0,menubar=0,scrollbars=1',
                                            'params' => [
                                                'blindLinkOptions' => 'folder',
                                                'blindLinkFields' => 'class, target',
                                                'allowedExtensions' => 'jpg',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'input',
                                    'renderType' => 'inputLink',
                                    'fieldControl' => [
                                        'linkPopup' => [
                                            'options' => [
                                                'title' => 'aLinkTitle',
                                                'windowOpenParameters' => 'height=800,width=600,status=0,menubar=0,scrollbars=1',
                                                'blindLinkOptions' => 'folder',
                                                'blindLinkFields' => 'class, target',
                                                'allowedExtensions' => 'jpg',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'link wizard does not migrate if renderType is already set' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'input',
                                    'renderType' => 'aRenderType',
                                    'wizards' => [
                                        'link' => [
                                            'type' => 'popup',
                                            'module' => [
                                                'name' => 'wizard_link',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'input',
                                    'renderType' => 'aRenderType',
                                    'wizards' => [
                                        'link' => [
                                            'type' => 'popup',
                                            'module' => [
                                                'name' => 'wizard_link',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'two wizards' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'input',
                                    'wizards' => [
                                        'link' => [
                                            'type' => 'popup',
                                            'module' => [
                                                'name' => 'wizard_link',
                                            ],
                                        ],
                                        'differentWizard' => [
                                            'type' => 'foo',
                                        ]
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'input',
                                    'renderType' => 'inputLink',
                                    'wizards' => [
                                        'differentWizard' => [
                                            'type' => 'foo',
                                        ],
                                    ]
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'link wizard columnsOverrides field' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'input',
                                ]
                            ]
                        ],
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'config' => [
                                            'wizards' => [
                                                'link' => [
                                                    'type' => 'popup',
                                                    'title' => 'aLinkTitle',
                                                    'module' => [
                                                        'name' => 'wizard_link',
                                                    ],
                                                    'JSopenParams' => 'height=800,width=600,status=0,menubar=0,scrollbars=1',
                                                    'params' => [
                                                        'blindLinkOptions' => 'folder',
                                                        'blindLinkFields' => 'class, target',
                                                        'allowedExtensions' => 'jpg',
                                                    ],
                                                ],
                                                'differentWizard' => [
                                                    'type' => 'foo',
                                                ]
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'input',
                                ]
                            ]
                        ],
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'config' => [
                                            'renderType' => 'inputLink',
                                            'fieldControl' => [
                                                'linkPopup' => [
                                                    'options' => [
                                                        'title' => 'aLinkTitle',
                                                        'windowOpenParameters' => 'height=800,width=600,status=0,menubar=0,scrollbars=1',
                                                        'blindLinkOptions' => 'folder',
                                                        'blindLinkFields' => 'class, target',
                                                        'allowedExtensions' => 'jpg',
                                                    ],
                                                ],
                                            ],
                                            'wizards' => [
                                                'differentWizard' => [
                                                    'type' => 'foo',
                                                ],
                                            ]
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
            ]
        ];
    }

    /**
     * @param array $input
     * @param array $expected
     * @test
     * @dataProvider migrateMovesLinkWizardToRenderTypeWithFieldControlDataProvider
     */
    public function migrateMovesLinkWizardToRenderTypeWithFieldControl(array $input, array $expected): void
    {
        $this->assertEquals($expected, (new TcaMigration())->migrate($input));
    }

    /**
     * @return array
     */
    public function migrateMovesEditWizardToFieldControlDataProvider(): array
    {
        return [
            'simple link wizard without options' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'group',
                                    'wizards' => [
                                        'edit' => [
                                            'type' => 'popup',
                                            'module' => [
                                                'name' => 'wizard_edit',
                                            ],
                                            'icon' => 'actions-open',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'group',
                                    'fieldControl' => [
                                        'editPopup' => [
                                            'disabled' => false,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'simple link wizard with options' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'select',
                                    'renderType' => 'selectMultipleSideBySide',
                                    'wizards' => [
                                        'edit' => [
                                            'type' => 'popup',
                                            'title' => 'aLabel',
                                            'module' => [
                                                'name' => 'wizard_edit',
                                            ],
                                            'popup_onlyOpenIfSelected' => 1,
                                            'icon' => 'actions-open',
                                            'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1'
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'select',
                                    'renderType' => 'selectMultipleSideBySide',
                                    'fieldControl' => [
                                        'editPopup' => [
                                            'disabled' => false,
                                            'options' => [
                                                'title' => 'aLabel',
                                                'windowOpenParameters' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'edit wizard in columnsOverrides' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'group',
                                ],
                            ],
                        ],
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'config' => [
                                            'wizards' => [
                                                'edit' => [
                                                    'type' => 'popup',
                                                    'title' => 'aLabel',
                                                    'module' => [
                                                        'name' => 'wizard_edit',
                                                    ],
                                                    'icon' => 'actions-open',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'group',
                                ],
                            ],
                        ],
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'config' => [
                                            'fieldControl' => [
                                                'editPopup' => [
                                                    'disabled' => false,
                                                    'options' => [
                                                        'title' => 'aLabel',
                                                    ],
                                                ],
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
    }

    /**
     * @param array $input
     * @param array $expected
     * @test
     * @dataProvider migrateMovesEditWizardToFieldControlDataProvider
     */
    public function migrateMovesEditWizardToFieldControl(array $input, array $expected): void
    {
        $this->assertEquals($expected, (new TcaMigration())->migrate($input));
    }

    /**
     * @return array
     */
    public function migrateMovesAddWizardToFieldControlDataProvider(): array
    {
        return [
            'simple add wizard without options' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'group',
                                    'wizards' => [
                                        'edit' => [
                                            'type' => 'script',
                                            'module' => [
                                                'name' => 'wizard_add',
                                            ],
                                            'icon' => 'actions-add',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'group',
                                    'fieldControl' => [
                                        'addRecord' => [
                                            'disabled' => false,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'simple add wizard with options' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'select',
                                    'renderType' => 'selectMultipleSideBySide',
                                    'wizards' => [
                                        'edit' => [
                                            'type' => 'script',
                                            'title' => 'aLabel',
                                            'module' => [
                                                'name' => 'wizard_add',
                                            ],
                                            'icon' => 'actions-add',
                                            'params' => [
                                                'table' => 'aTable',
                                                'pid' => 'aPid',
                                                'setValue' => 'prepend',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'select',
                                    'renderType' => 'selectMultipleSideBySide',
                                    'fieldControl' => [
                                        'addRecord' => [
                                            'disabled' => false,
                                            'options' => [
                                                'title' => 'aLabel',
                                                'table' => 'aTable',
                                                'pid' => 'aPid',
                                                'setValue' => 'prepend',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'add wizard in columnsOverrides' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'group',
                                ],
                            ],
                        ],
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'config' => [
                                            'wizards' => [
                                                'edit' => [
                                                    'type' => 'script',
                                                    'title' => 'aLabel',
                                                    'module' => [
                                                        'name' => 'wizard_add',
                                                    ],
                                                    'icon' => 'actions-add',
                                                    'params' => [
                                                        'table' => 'aTable',
                                                        'pid' => 'aPid',
                                                        'setValue' => 'prepend',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'group',
                                ],
                            ],
                        ],
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'config' => [
                                            'fieldControl' => [
                                                'addRecord' => [
                                                    'disabled' => false,
                                                    'options' => [
                                                        'title' => 'aLabel',
                                                        'table' => 'aTable',
                                                        'pid' => 'aPid',
                                                        'setValue' => 'prepend',
                                                    ],
                                                ],
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
    }

    /**
     * @param array $input
     * @param array $expected
     * @test
     * @dataProvider migrateMovesAddWizardToFieldControlDataProvider
     */
    public function migrateMovesAddWizardToFieldControl(array $input, array $expected): void
    {
        $this->assertEquals($expected, (new TcaMigration())->migrate($input));
    }

    /**
     * @return array
     */
    public function migrateMovesListWizardToFieldControlDataProvider(): array
    {
        return [
            'simple list wizard without options' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'group',
                                    'wizards' => [
                                        'edit' => [
                                            'type' => 'script',
                                            'module' => [
                                                'name' => 'wizard_list',
                                            ],
                                            'icon' => 'actions-system-list-open',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'group',
                                    'fieldControl' => [
                                        'listModule' => [
                                            'disabled' => false,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'simple list wizard with options' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'select',
                                    'renderType' => 'selectMultipleSideBySide',
                                    'wizards' => [
                                        'edit' => [
                                            'type' => 'script',
                                            'title' => 'aLabel',
                                            'module' => [
                                                'name' => 'wizard_list',
                                            ],
                                            'icon' => 'actions-system-list-open',
                                            'params' => [
                                                'table' => 'aTable',
                                                'pid' => 'aPid',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'select',
                                    'renderType' => 'selectMultipleSideBySide',
                                    'fieldControl' => [
                                        'listModule' => [
                                            'disabled' => false,
                                            'options' => [
                                                'title' => 'aLabel',
                                                'table' => 'aTable',
                                                'pid' => 'aPid',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'list wizard in columnsOverrides' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'group',
                                ],
                            ],
                        ],
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'config' => [
                                            'wizards' => [
                                                'edit' => [
                                                    'type' => 'script',
                                                    'title' => 'aLabel',
                                                    'module' => [
                                                        'name' => 'wizard_list',
                                                    ],
                                                    'icon' => 'actions-system-list-open',
                                                    'params' => [
                                                        'table' => 'aTable',
                                                        'pid' => 'aPid',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'group',
                                ],
                            ],
                        ],
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'config' => [
                                            'fieldControl' => [
                                                'listModule' => [
                                                    'disabled' => false,
                                                    'options' => [
                                                        'title' => 'aLabel',
                                                        'table' => 'aTable',
                                                        'pid' => 'aPid',
                                                    ],
                                                ],
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
    }

    /**
     * @param array $input
     * @param array $expected
     * @test
     * @dataProvider migrateMovesListWizardToFieldControlDataProvider
     */
    public function migrateMovesListWizardToFieldControl(array $input, array $expected): void
    {
        $this->assertEquals($expected, (new TcaMigration())->migrate($input));
    }

    /**
     * @return array
     */
    public function migrateMovesLastDefaultExtrasValuesDataProvider(): array
    {
        return [
            'rte_only is removed' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'text',
                                ],
                                'defaultExtras' => 'rte-only',
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'text',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'rte_only is removed in columnsOverrides' => [
                [
                    'aTable' => [
                        'columns' => [],
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'defaultExtras' => 'rte-only',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [],
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [],
                                ]
                            ]
                        ]
                    ],
                ],
            ],
            'enable-tab, fixed-font, nowrap is migrated' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'text',
                                ],
                                'defaultExtras' => 'enable-tab : fixed-font:nowrap',
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'text',
                                    'enableTabulator' => true,
                                    'fixedFont' => true,
                                    'wrap' => 'off',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'enable-tab, fixed-font, nowrap is migrated in columnsOverrides' => [
                [
                    'aTable' => [
                        'columns' => [],
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'defaultExtras' => 'enable-tab : fixed-font:nowrap',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [],
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'config' => [
                                            'enableTabulator' => true,
                                            'fixedFont' => true,
                                            'wrap' => 'off',
                                        ],
                                    ],
                                ],
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
     * @dataProvider migrateMovesLastDefaultExtrasValuesDataProvider
     */
    public function migrateMovesLastDefaultExtrasValues(array $input, array $expected): void
    {
        $this->assertEquals($expected, (new TcaMigration())->migrate($input));
    }

    /**
     * @return array
     */
    public function migrateMovesTableWizardToRenderTypeDataProvider(): array
    {
        return [
            'simple table wizard without options' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'text',
                                    'wizards' => [
                                        'table' => [
                                            'type' => 'script',
                                            'icon' => 'content-table',
                                            'module' => [
                                                'name' => 'wizard_table'
                                            ],
                                            'notNewRecords' => 1,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'text',
                                    'renderType' => 'textTable',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'simple table wizard without options in columnsOverrides' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'text',
                                ],
                            ],
                        ],
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'config' => [
                                            'wizards' => [
                                                'table' => [
                                                    'type' => 'script',
                                                    'icon' => 'content-table',
                                                    'module' => [
                                                        'name' => 'wizard_table'
                                                    ],
                                                    'notNewRecords' => 1,
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'text',
                                ],
                            ],
                        ],
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'config' => [
                                            'renderType' => 'textTable',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'simple table wizard with default options' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'text',
                                    'wizards' => [
                                        'table' => [
                                            'type' => 'script',
                                            'icon' => 'content-table',
                                            'module' => [
                                                'name' => 'wizard_table'
                                            ],
                                            'params' => [
                                                'xmlOutput' => 0
                                            ],
                                            'notNewRecords' => 1,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'text',
                                    'renderType' => 'textTable',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'simple table wizard with default options in columnsOverrides' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'text',
                                ],
                            ],
                        ],
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'config' => [
                                            'wizards' => [
                                                'table' => [
                                                    'type' => 'script',
                                                    'icon' => 'content-table',
                                                    'module' => [
                                                        'name' => 'wizard_table'
                                                    ],
                                                    'params' => [
                                                        'xmlOutput' => 0
                                                    ],
                                                    'notNewRecords' => 1,
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'text',
                                ],
                            ],
                        ],
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'config' => [
                                            'renderType' => 'textTable',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'simple table wizard with options' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'text',
                                    'wizards' => [
                                        'table' => [
                                            'type' => 'script',
                                            'title' => 'aTitle',
                                            'icon' => 'content-table',
                                            'module' => [
                                                'name' => 'wizard_table'
                                            ],
                                            'params' => [
                                                'xmlOutput' => 1,
                                                'numNewRows' => 23,
                                            ],
                                            'notNewRecords' => 1,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'text',
                                    'renderType' => 'textTable',
                                    'fieldControl' => [
                                        'tableWizard' => [
                                            'options' => [
                                                'title' => 'aTitle',
                                                'xmlOutput' => 1,
                                                'numNewRows' => 23,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'simple table wizard with options in columnsOverrides' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'text',
                                ],
                            ],
                        ],
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'config' => [
                                            'wizards' => [
                                                'table' => [
                                                    'type' => 'script',
                                                    'title' => 'aTitle',
                                                    'icon' => 'content-table',
                                                    'module' => [
                                                        'name' => 'wizard_table'
                                                    ],
                                                    'params' => [
                                                        'xmlOutput' => '1',
                                                        'numNewRows' => 23,
                                                    ],
                                                    'notNewRecords' => 1,
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'text',
                                ],
                            ],
                        ],
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'config' => [
                                            'renderType' => 'textTable',
                                            'fieldControl' => [
                                                'tableWizard' => [
                                                    'options' => [
                                                        'title' => 'aTitle',
                                                        'xmlOutput' => 1,
                                                        'numNewRows' => 23,
                                                    ],
                                                ],
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
    }

    /**
     * @param array $input
     * @param array $expected
     * @test
     * @dataProvider migrateMovesTableWizardToRenderTypeDataProvider
     */
    public function migrateMovesTableWizardToRenderType(array $input, array $expected): void
    {
        $this->assertEquals($expected, (new TcaMigration())->migrate($input));
    }

    /**
     * @return array
     */
    public function migrateMovesFullScreenRichtextWizardToFieldControlDataProvider(): array
    {
        return [
            'simple rte wizard' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'text',
                                    'enableRichtext' => true,
                                    'wizards' => [
                                        'RTE' => [
                                            'notNewRecords' => true,
                                            'RTEonly' => true,
                                            'type' => 'script',
                                            'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext.W.RTE',
                                            'icon' => 'actions-wizard-rte',
                                            'module' => [
                                                'name' => 'wizard_rte'
                                            ]
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'text',
                                    'enableRichtext' => true,
                                    'fieldControl' => [
                                        'fullScreenRichtext' => [
                                            'disabled' => false,
                                            'options' => [
                                                'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext.W.RTE',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'wizard is moved to columnsOverrides if enableRichtext is not set on columns' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'text',
                                    'wizards' => [
                                        'RTE' => [
                                            'notNewRecords' => true,
                                            'RTEonly' => true,
                                            'type' => 'script',
                                            'icon' => 'actions-wizard-rte',
                                            'module' => [
                                                'name' => 'wizard_rte'
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'config' => [
                                            'enableRichtext' => true,
                                        ],
                                    ],
                                ],
                            ],
                            'anotherType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'config' => [
                                            'someProperty' => 'someValue',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'label' => 'foo',
                                'config' => [
                                    'type' => 'text',
                                ],
                            ],
                        ],
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'config' => [
                                            'enableRichtext' => true,
                                            'fieldControl' => [
                                                'fullScreenRichtext' => [
                                                    'disabled' => false,
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            'anotherType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'config' => [
                                            'someProperty' => 'someValue',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'simple rte wizard in columnsOverrides' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'text',
                                ],
                            ],
                        ],
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'config' => [
                                            'wizards' => [
                                                'RTE' => [
                                                    'notNewRecords' => true,
                                                    'RTEonly' => true,
                                                    'type' => 'script',
                                                    'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext.W.RTE',
                                                    'icon' => 'actions-wizard-rte',
                                                    'module' => [
                                                        'name' => 'wizard_rte'
                                                    ]
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'text',
                                ],
                            ],
                        ],
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'config' => [
                                            'fieldControl' => [
                                                'fullScreenRichtext' => [
                                                    'disabled' => false,
                                                    'options' => [
                                                        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext.W.RTE',
                                                    ],
                                                ],
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
    }

    /**
     * @param array $input
     * @param array $expected
     * @test
     * @dataProvider migrateMovesFullScreenRichtextWizardToFieldControlDataProvider
     */
    public function migrateMovesFullScreenRichtextWizardToFieldControl(array $input, array $expected): void
    {
        $this->assertEquals($expected, (new TcaMigration())->migrate($input));
    }

    /**
     * @return array
     */
    public function migrateSuggestWizardDataProvider(): array
    {
        return [
            'no suggest wizard in main field but configured in columnsOverrides' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'group',
                                    'internal_type' => 'db',
                                    'allowed' => 'aTable',
                                ],
                            ],
                        ],
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'config' => [
                                            'wizards' => [
                                                'suggest' => [
                                                    'type' => 'suggest',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'group',
                                    'internal_type' => 'db',
                                    'allowed' => 'aTable',
                                    'hideSuggest' => true,
                                ],
                            ],
                        ],
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'config' => [
                                            'hideSuggest' => false,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'no suggest wizard in main field but configured in columnsOverrides with options' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'group',
                                    'internal_type' => 'db',
                                    'allowed' => 'aTable',
                                ],
                            ],
                        ],
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'config' => [
                                            'wizards' => [
                                                'suggest' => [
                                                    'type' => 'suggest',
                                                    'default' => [
                                                        'minimumCharacters' => 23,
                                                    ],
                                                    'aTable' => [
                                                        'searchCondition' => 'doktype = 1'
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'group',
                                    'internal_type' => 'db',
                                    'allowed' => 'aTable',
                                    'hideSuggest' => true,
                                ],
                            ],
                        ],
                        'types' => [
                            'aType' => [
                                'columnsOverrides' => [
                                    'aField' => [
                                        'config' => [
                                            'hideSuggest' => false,
                                            'suggestOptions' => [
                                                'default' => [
                                                    'minimumCharacters' => 23,
                                                ],
                                                'aTable' => [
                                                    'searchCondition' => 'doktype = 1'
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'suggest wizard configured without options' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'group',
                                    'internal_type' => 'db',
                                    'allowed' => 'aTable',
                                    'wizards' => [
                                        'suggest' => [
                                            'type' => 'suggest',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'group',
                                    'internal_type' => 'db',
                                    'allowed' => 'aTable',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'suggest wizard with options' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'group',
                                    'internal_type' => 'db',
                                    'allowed' => 'aTable',
                                    'wizards' => [
                                        'suggest' => [
                                            'type' => 'suggest',
                                            'default' => [
                                                'minimumCharacters' => 23,
                                                'anOption' => 'anOptionValue',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'group',
                                    'internal_type' => 'db',
                                    'allowed' => 'aTable',
                                    'suggestOptions' => [
                                        'default' => [
                                            'minimumCharacters' => 23,
                                            'anOption' => 'anOptionValue',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'suggest wizard with table specific options' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'group',
                                    'internal_type' => 'db',
                                    'allowed' => 'aTable',
                                    'wizards' => [
                                        'suggest' => [
                                            'type' => 'suggest',
                                            'default' => [
                                                'minimumCharacters' => 23,
                                            ],
                                            'aTable' => [
                                                'searchCondition' => 'doktype = 1'
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'group',
                                    'internal_type' => 'db',
                                    'allowed' => 'aTable',
                                    'suggestOptions' => [
                                        'default' => [
                                            'minimumCharacters' => 23,
                                        ],
                                        'aTable' => [
                                            'searchCondition' => 'doktype = 1'
                                        ],
                                    ],
                                ],
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
     * @dataProvider migrateSuggestWizardDataProvider
     */
    public function migrateSuggestWizard(array $input, array $expected): void
    {
        $this->assertEquals($expected, (new TcaMigration())->migrate($input));
    }

    /**
     * @return array
     */
    public function migrateOptionsOfTypeGroupDataProvider(): array
    {
        return [
            'selectedListStyle is dropped' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'group',
                                    'selectedListStyle' => 'data-foo: bar',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'group',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'show_thumbs true is dropped' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'group',
                                    'show_thumbs' => true,
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'group',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'show_thumbs false internal_type db disables tableList' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'group',
                                    'internal_type' => 'db',
                                    'show_thumbs' => false,
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'group',
                                    'internal_type' => 'db',
                                    'fieldWizard' => [
                                        'recordsOverview' => [
                                            'disabled' => true,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'show_thumbs false internal_type file disables fileThumbnails' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'group',
                                    'internal_type' => 'file',
                                    'show_thumbs' => false,
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'group',
                                    'internal_type' => 'file',
                                    'fieldWizard' => [
                                        'fileThumbnails' => [
                                            'disabled' => true,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'disable_controls browser sets fieldControl elementBrowser disabled' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'group',
                                    'disable_controls' => 'browser',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'group',
                                    'fieldControl' => [
                                        'elementBrowser' => [
                                            'disabled' => true,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'disable_controls list is dropped' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'group',
                                    'disable_controls' => 'list,browser',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'group',
                                    'fieldControl' => [
                                        'elementBrowser' => [
                                            'disabled' => true,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'disable_controls delete sets hideDeleteIcon true' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'group',
                                    'disable_controls' => 'delete',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'group',
                                    'hideDeleteIcon' => true,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'disable_controls allowedTables sets fieldWizard tableList disabled' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'group',
                                    'disable_controls' => 'allowedTables',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'group',
                                    'fieldWizard' => [
                                        'tableList' => [
                                            'disabled' => true,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'disable_controls upload sets fieldWizard fileUpload disabled' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'group',
                                    'disable_controls' => 'upload',
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'group',
                                    'fieldWizard' => [
                                        'fileUpload' => [
                                            'disabled' => true,
                                        ],
                                    ],
                                ],
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
     * @dataProvider migrateOptionsOfTypeGroupDataProvider
     */
    public function migrateOptionsOfTypeGroup(array $input, array $expected): void
    {
        $this->assertEquals($expected, (new TcaMigration())->migrate($input));
    }

    /**
     * @return array
     */
    public function migrateSelectSingleShowIconTableDataProvider(): array
    {
        return [
            'showIconTable enabled selectIcons field wizard' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'select',
                                    'renderType' => 'selectSingle',
                                    'showIconTable' => true,
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'select',
                                    'renderType' => 'selectSingle',
                                    'fieldWizard' => [
                                        'selectIcons' => [
                                            'disabled' => false,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'selicon_cols is removed' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'select',
                                    'renderType' => 'selectSingle',
                                    'selicon_cols' => 4,
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'select',
                                    'renderType' => 'selectSingle',
                                ],
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
     * @dataProvider migrateSelectSingleShowIconTableDataProvider
     */
    public function migrateSelectSingleShowIconTable(array $input, array $expected): void
    {
        $this->assertEquals($expected, (new TcaMigration())->migrate($input));
    }

    /**
     * @return array
     */
    public function migrateImageManipulationRatiosDataProvider(): array
    {
        return [
            'enableZoom is removed' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'imageManipulation',
                                    'enableZoom' => true,
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'imageManipulation',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'ratios migration ignored if cropVariants config is present' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'imageManipulation',
                                    'ratios' => [
                                        4 / 3 => '4:3',
                                    ],
                                    'cropVariants' => [],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'imageManipulation',
                                    'cropVariants' => [],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'ratios are migrated' => [
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'imageManipulation',
                                    'ratios' => [
                                        '1.3333333333333333' => '4:3',
                                        '1.7777777777777777' => '16:9',
                                        '1' => '1:1',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'aTable' => [
                        'columns' => [
                            'aField' => [
                                'config' => [
                                    'type' => 'imageManipulation',
                                    'cropVariants' => [
                                        'default' => [
                                            'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_wizards.xlf:imwizard.crop_variant.default',
                                            'allowedAspectRatios' => [
                                                '1.33' => [
                                                    'title' => '4:3',
                                                    'value' => 4 / 3,
                                                ],
                                                '1.78' => [
                                                    'title' => '16:9',
                                                    'value' => 16 / 9,
                                                ],
                                                '1.00' => [
                                                    'title' => '1:1',
                                                    'value' => 1.0,
                                                ],
                                            ],
                                            'cropArea' => [
                                                'x' => 0.0,
                                                'y' => 0.0,
                                                'width' => 1.0,
                                                'height' => 1.0,
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
    }

    /**
     * @param array $input
     * @param array $expected
     * @test
     * @dataProvider migrateImageManipulationRatiosDataProvider
     */
    public function migrateImageManipulationRatios(array $input, array $expected): void
    {
        $this->assertEquals($expected, (new TcaMigration())->migrate($input));
    }

    /**
     * @test
     */
    public function migrateinputDateTimeMaxNotDefinedAndRenderTypeNotDefined(): void
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'foo' => [
                        'config' => [
                            'type' => 'input'
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
                            'type' => 'input'
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
    public function migrateinputDateTimeMaxNotDefinedAndRenderTypeNotInputDateTime(): void
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'foo' => [
                        'config' => [
                            'type' => 'input',
                            'renderType' => 'fooBar'
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
                            'type' => 'input',
                            'renderType' => 'fooBar'
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
    public function migrateinputDateTimeMaxNotDefined(): void
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'foo' => [
                        'config' => [
                            'type' => 'input',
                            'renderType' => 'inputDateTime'
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
                            'type' => 'input',
                            'renderType' => 'inputDateTime'
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
    public function migrateinputDateTimeMaxDefined(): void
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'foo' => [
                        'config' => [
                            'type' => 'input',
                            'renderType' => 'inputDateTime',
                            'max' => 42,
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
                            'type' => 'input',
                            'renderType' => 'inputDateTime',
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
    public function migrateinputDateTimeMaxDefinedAndRenderTypeNotDefined(): void
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'foo' => [
                        'config' => [
                            'type' => 'input',
                            'max' => 42,
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
                            'type' => 'input',
                            'max' => 42,
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
    public function migrateinputDateTimeMaxDefinedAndRenderTypeNotDateTime(): void
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'foo' => [
                        'config' => [
                            'type' => 'input',
                            'renderType' => 'fooBar',
                            'max' => 42,
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
                            'type' => 'input',
                            'renderType' => 'fooBar',
                            'max' => 42,
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
    public function migrateForeignTypesOverride(): void
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'foo' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_types' => [
                                '0' => [
                                    'showitem' => 'bar'
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
                    'foo' => [
                        'config' => [
                            'type' => 'inline',
                            'overrideChildTca' => [
                                'types' => [
                                    '0' => [
                                        'showitem' => 'bar'
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
     * @test
     */
    public function migrateForeignTypesMergedIntoExistingOverrideChildTca(): void
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'foo' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_types' => [
                                '0' => [
                                    // This does NOT override existing 'showitem'='baz' below
                                    'showitem' => 'doesNotOverrideExistingSetting',
                                    // This is added to existing types 0 below
                                    'bitmask_value_field' => 42,
                                ],
                                'otherType' => [
                                    'showitem' => 'aField',
                                ],
                            ],
                            'overrideChildTca' => [
                                'types' => [
                                    '0' => [
                                        'showitem' => 'baz'
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
                    'foo' => [
                        'config' => [
                            'type' => 'inline',
                            'overrideChildTca' => [
                                'types' => [
                                    '0' => [
                                        'showitem' => 'baz',
                                        'bitmask_value_field' => 42,
                                    ],
                                    'otherType' => [
                                        'showitem' => 'aField',
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
     * @test
     */
    public function migrateForeignDefaultsOverride(): void
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'foo' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_record_defaults' => [
                                'aField' => 'doesNotOverrideExistingOverrideChildTcaDefault',
                                'bField' => 'aDefault',
                            ],
                            'overrideChildTca' => [
                                'columns' => [
                                    'aField' => [
                                        'config' => [
                                            'default' => 'aDefault'
                                        ],
                                    ],
                                    'cField' => [
                                        'config' => [
                                            'default' => 'aDefault'
                                        ],
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
                    'foo' => [
                        'config' => [
                            'type' => 'inline',
                            'overrideChildTca' => [
                                'columns' => [
                                    'aField' => [
                                        'config' => [
                                            'default' => 'aDefault'
                                        ],
                                    ],
                                    'bField' => [
                                        'config' => [
                                            'default' => 'aDefault'
                                        ],
                                    ],
                                    'cField' => [
                                        'config' => [
                                            'default' => 'aDefault'
                                        ],
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
     * @test
     */
    public function migrateForeignSelectorOverrides(): void
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'foo' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_selector' => 'uid_local',
                            'foreign_selector_fieldTcaOverride' => [
                                'label' => 'aDifferentLabel',
                                'config' => [
                                    'aGivenSetting' => 'overrideValue',
                                    'aNewSetting' => 'anotherNewValue',
                                    'anExistingSettingInOverrideChildTca' => 'doesNotOverrideExistingOverrideChildTcaDefault',
                                    'appearance' => [
                                        'elementBrowserType' => 'file',
                                        'elementBrowserAllowed' => 'jpg,png'
                                    ],
                                ],
                            ],
                            'overrideChildTca' => [
                                'columns' => [
                                    'uid_local' => [
                                        'config' => [
                                            'anExistingSettingInOverrideChildTca' => 'notOverridenByOldSetting',
                                        ],
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
                    'foo' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_selector' => 'uid_local',
                            'overrideChildTca' => [
                                'columns' => [
                                    'uid_local' => [
                                        'label' => 'aDifferentLabel',
                                        'config' => [
                                            'anExistingSettingInOverrideChildTca' => 'notOverridenByOldSetting',
                                            'aGivenSetting' => 'overrideValue',
                                            'aNewSetting' => 'anotherNewValue',
                                            'appearance' => [
                                                'elementBrowserType' => 'file',
                                                'elementBrowserAllowed' => 'jpg,png'
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
        $subject = new TcaMigration();
        $this->assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function migrateAllOverridesFromColumnOverride(): void
    {
        $input = [
            'aTable' => [
                'types' => [
                    'textmedia' => [
                        'columnsOverrides' => [
                            'assets' => [
                                'config' => [
                                    'type' => 'inline',
                                    'foreign_selector' => 'uid_local',
                                    'foreign_types' => [
                                        '0' => [
                                            'showitem' => 'bar'
                                        ],
                                    ],
                                    'foreign_selector_fieldTcaOverride' => [
                                        'label' => 'aDifferentLabel',
                                        'config' => [
                                            'aGivenSetting' => 'overrideValue',
                                            'aNewSetting' => 'anotherNewValue',
                                            'appearance' => [
                                                'elementBrowserType' => 'file',
                                                'elementBrowserAllowed' => 'jpg,png'
                                            ],
                                        ],
                                    ],
                                    'foreign_record_defaults' => [
                                        'aField' => 'overriddenValue',
                                        'bField' => 'overriddenValue',
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
                'types' => [
                    'textmedia' => [
                        'columnsOverrides' => [
                            'assets' => [
                                'config' => [
                                    'type' => 'inline',
                                    'foreign_selector' => 'uid_local',
                                    'overrideChildTca' => [
                                        'types' => [
                                            '0' => [
                                                'showitem' => 'bar'
                                            ],
                                        ],
                                        'columns' => [
                                            'uid_local' => [
                                                'label' => 'aDifferentLabel',
                                                'config' => [
                                                    'aGivenSetting' => 'overrideValue',
                                                    'aNewSetting' => 'anotherNewValue',
                                                    'appearance' => [
                                                        'elementBrowserType' => 'file',
                                                        'elementBrowserAllowed' => 'jpg,png'
                                                    ],
                                                ],
                                            ],
                                            'aField' => [
                                                'config' => [
                                                    'default' => 'overriddenValue'
                                                ],
                                            ],
                                            'bField' => [
                                                'config' => [
                                                    'default' => 'overriddenValue'
                                                ],
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
        $subject = new TcaMigration();
        $this->assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function migratePartlyOverridesFromColumnOverride(): void
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'assets' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_selector' => 'uid_local',
                            'overrideChildTca' => [
                                'types' => [
                                    '0' => [
                                        'showitem' => 'foo'
                                    ],
                                ],
                                'columns' => [
                                    'uid_local' => [
                                        'label' => 'Label',
                                        'config' => [
                                            'appearance' => [
                                                'elementBrowserType' => 'file',
                                                'elementBrowserAllowed' => 'jpg,png'
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'types' => [
                    'textmedia' => [
                        'columnsOverrides' => [
                            'assets' => [
                                'config' => [
                                    'foreign_types' => [
                                        '0' => [
                                            'showitem' => 'bar'
                                        ],
                                    ],
                                    'foreign_selector_fieldTcaOverride' => [
                                        'config' => [
                                            'appearance' => [
                                                'elementBrowserAllowed' => 'jpg,png'
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
        $expected = [
            'aTable' => [
                'columns' => [
                    'assets' => [
                        'config' => [
                            'type' => 'inline',
                            'foreign_selector' => 'uid_local',
                            'overrideChildTca' => [
                                'types' => [
                                    '0' => [
                                        'showitem' => 'foo'
                                    ],
                                ],
                                'columns' => [
                                    'uid_local' => [
                                        'label' => 'Label',
                                        'config' => [
                                            'appearance' => [
                                                'elementBrowserType' => 'file',
                                                'elementBrowserAllowed' => 'jpg,png'
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'types' => [
                    'textmedia' => [
                        'columnsOverrides' => [
                            'assets' => [
                                'config' => [
                                    'overrideChildTca' => [
                                        'types' => [
                                            '0' => [
                                                'showitem' => 'bar'
                                            ],
                                        ],
                                        'columns' => [
                                            'uid_local' => [
                                                'config' => [
                                                    'appearance' => [
                                                        'elementBrowserAllowed' => 'jpg,png'
                                                    ],
                                                ],
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
        $subject = new TcaMigration();
        $this->assertEquals($expected, $subject->migrate($input));
    }

    /**
     * @test
     */
    public function migrateLogsDeprecationWithGroupInternalTypeFile()
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'foo' => [
                        'config' => [
                            'type' => 'group',
                            'internal_type' => 'file',
                        ],
                    ],
                ],
            ],
        ];
        $subject = new TcaMigration();
        $subject->migrate($input);
        $this->assertNotEmpty($subject->getMessages());
    }

    /**
     * @test
     */
    public function migrateLogsDeprecationWithGroupInternalTypeFileReference()
    {
        $input = [
            'aTable' => [
                'columns' => [
                    'foo' => [
                        'config' => [
                            'type' => 'group',
                            'internal_type' => 'file_reference',
                        ],
                    ],
                ],
            ],
        ];
        $subject = new TcaMigration();
        $subject->migrate($input);
        $this->assertNotEmpty($subject->getMessages());
    }
}

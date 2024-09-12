<?php

return [
    'ctrl' => [
        'title' => 'Form engine - required',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg',
        'versioningWS' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'translationSource' => 'l10n_source',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],

    'columns' => [
        'notrequired_1' => [
            'label' => 'notrequired_1',
            'config' => [
                'type' => 'input',
            ],
        ],

        'input_1' => [
            'label' => 'input_1 eval=required',
            'config' => [
                'type' => 'input',
                'max' => 23,
                'required' => true,
            ],
        ],
        'input_2' => [
            'label' => 'input_2 eval=required,trim,date',
            'config' => [
                'type' => 'datetime',
                'format' => 'date',
                'required' => true,
            ],
        ],
        'color_1' => [
            'label' => 'color_1',
            'config' => [
                'type' => 'color',
                'required' => true,
            ],
        ],
        'link_1' => [
            'label' => 'link_1 eval=required, type=link',
            'config' => [
                'type' => 'link',
                'size' => 60,
                'required' => true,
            ],
        ],

        'text_1' => [
            'label' => 'text_1 eval=required',
            'config' => [
                'type' => 'text',
                'required' => true,
            ],
        ],

        'select_1' => [
            'label' => 'select_1 selectMultipleSideBySide, minitems=2, maxitems=5',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 3,
                'maxitems' => 5,
                'minitems' => 2,
                'items' => [
                    ['label' => 'foo1', 'value' => 1],
                    ['label' => 'foo2', 'value' => 2],
                    ['label' => 'foo3', 'value' => 3],
                    ['label' => 'foo4', 'value' => 4],
                    ['label' => 'foo5', 'value' => 5],
                    ['label' => 'foo6', 'value' => 6],
                ],
            ],
        ],
        'select_2' => [
            'label' => 'select_2 selectSingle, minitems=1, maxitems=1',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'foo1', 'value' => 1],
                    ['label' => 'foo2', 'value' => 2],
                    ['label' => 'foo3', 'value' => 3],
                ],
                // size > 1 triggers "singlebox" mode
                'size' => 2,
                'minitems' => 1,
            ],
        ],
        'select_3' => [
            'label' => 'select_3, selectSingleBox, minitems=1, maxitems=2',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingleBox',
                'items' => [
                    ['label' => 'foo 1', 'value' => 1],
                    ['label' => 'foo 2', 'value' => 2],
                    ['label' => 'divider', 'value' => '--div--'],
                    ['label' => 'foo 3', 'value' => 3],
                    ['label' => 'foo 4', 'value' => 4],
                ],
                'minitems' => 1,
                'maxitems' => 2,
            ],
        ],
        'select_4' => [
            'label' => 'select_4 selectCheckBox, minitems=1, maxitems=2',
            'config' => [
                'type' => 'select',
                // @todo: required handling on this type does not work yet
                'renderType' => 'selectCheckBox',
                'items' => [
                    ['label' => 'foo1', 'value' => 1],
                    ['label' => 'foo2', 'value' => 2],
                    ['label' => 'foo3', 'value' => 3],
                ],
                'minitems' => 1,
                // @todo: maxitems does not work?
                'maxitems' => 2,
            ],
        ],
        'select_5' => [
            'label' => 'select_5 selectTree, minitems=1, maxitems=3',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectTree',
                'foreign_table' => 'pages',
                'size' => 20,
                'minitems' => 1,
                'maxitems' => 3,
                'treeConfig' => [
                    'expandAll' => true,
                    'parentField' => 'pid',
                    'appearance' => [
                        'showHeader' => true,
                    ],
                ],
            ],
        ],

        'group_1' => [
            'label' => 'group_1 db, minitems=1, maxitems=3',
            'config' => [
                'type' => 'group',
                'allowed' => 'tx_styleguide_staticdata',
                'minitems' => 1,
                'maxitems' => 3,
            ],
        ],
        'group_2' => [
            'label' => 'group_2 db, minitems = 1, relationship=manyToOne, size=1',
            'config' => [
                'type' => 'group',
                'allowed' => 'tx_styleguide_staticdata',
                'size' => 1,
                'minitems' => 1,
                'relationship' => 'manyToOne',
            ],
        ],

        'rte_1' => [
            'label' => 'rte_1 eval=required',
            'config' => [
                'type' => 'text',
                'rows' => '15',
                'cols' => '80',
                'enableRichtext' => true,
                'required' => true,
            ],
        ],
        'rte_2' => [
            'label' => 'rte_2 inline',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_styleguide_required_rte_2_child',
                'foreign_field' => 'parentid',
                'foreign_table_field' => 'parenttable',
            ],
        ],

        'inline_1' => [
            'label' => 'inline_1 minitems=1, relationship=manyToOne',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_styleguide_required_inline_1_child',
                'foreign_field' => 'parentid',
                'foreign_table_field' => 'parenttable',
                'minitems' => 1,
                'relationship' => 'manyToOne',
            ],
        ],
        'inline_2' => [
            'label' => 'inline_2 required field in inline child',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_styleguide_required_inline_2_child',
                'foreign_field' => 'parentid',
                'foreign_table_field' => 'parenttable',
            ],
        ],
        'inline_3' => [
            'label' => 'inline_3 minitems=1, maxitems=3, required field in inline child',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_styleguide_required_inline_3_child',
                'foreign_field' => 'parentid',
                'foreign_table_field' => 'parenttable',
                'minitems' => 1,
                'maxitems' => 3,
            ],
        ],

        'flex_1' => [
            'label' => 'flex_1 required field in flex',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => '
                        <T3DataStructure>
                            <ROOT>
                                <type>array</type>
                                <el>
                                    <text_1>
                                        <label>text required</label>
                                        <config>
                                            <type>text</type>
                                            <required>1</required>
                                        </config>
                                    </text_1>
                                </el>
                            </ROOT>
                        </T3DataStructure>
                    ',
                ],
            ],
        ],
        'flex_2' => [
            'label' => 'flex_2 tabs, section container, inline',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => '
                        <T3DataStructure>
                            <sheets>
                                <sGeneral>
                                    <ROOT>
                                        <sheetTitle>tab</sheetTitle>
                                        <type>array</type>
                                        <el>
                                            <input_1>
                                                <label>input_1, required=1</label>
                                                <config>
                                                    <type>input</type>
                                                    <required>1</required>
                                                </config>
                                            </input_1>
                                        </el>
                                    </ROOT>
                                </sGeneral>
                                <sSections>
                                    <ROOT>
                                        <sheetTitle>section</sheetTitle>
                                        <type>array</type>
                                        <el>
                                            <section_1>
                                                <title>section_1</title>
                                                <type>array</type>
                                                <section>1</section>
                                                <el>
                                                    <container_1>
                                                        <type>array</type>
                                                        <title>container_1</title>
                                                        <el>
                                                            <input_1>
                                                                <label>input_1, required=1</label>
                                                                <config>
                                                                    <type>input</type>
                                                                    <required>1</required>
                                                                </config>
                                                            </input_1>
                                                        </el>
                                                    </container_1>
                                                </el>
                                            </section_1>
                                        </el>
                                    </ROOT>
                                </sSections>
                                <sInline>
                                    <ROOT>
                                        <sheetTitle>inline</sheetTitle>
                                        <type>array</type>
                                        <el>
                                            <inline_1>
                                                <label>inline_1 required field in inline child</label>
                                                <config>
                                                    <type>inline</type>
                                                    <foreign_table>tx_styleguide_required_flex_2_inline_1_child</foreign_table>
                                                    <foreign_field>parentid</foreign_field>
                                                    <foreign_table_field>parenttable</foreign_table_field>
                                                </config>
                                            </inline_1>
                                        </el>
                                    </ROOT>
                                </sInline>
                            </sheets>
                        </T3DataStructure>
                    ',
                ],
            ],
        ],

        'palette_input_1' => [
            'label' => 'palette_input_1 eval=required',
            'config' => [
                'type' => 'input',
                'required' => true,
            ],
        ],
        'palette_input_2' => [
            'label' => 'palette_input_2 eval=required',
            'config' => [
                'type' => 'input',
                'required' => true,
            ],
        ],

    ],

    'types' => [
        '0' => [
            'showitem' => '
                --div--;not required,
                    notrequired_1,
                --div--;Input,
                    input_1, input_2,
                --div--;Link,
                    link_1,
                --div--;Text,
                    text_1,
                --div--;Rte,
                    rte_1, rte_2,
                --div--;Select,
                    select_1, select_2, select_3, select_4, select_5,
                --div--;Group,
                    group_1, group_2,
                --div--;Inline,
                    inline_1, inline_2, inline_3,
                --div--;Flex,
                    flex_1, flex_2,
                --div--;Color,
                    color_1,
                --div--;palette,
                    --palette--;palette_1;palette_1,
            ',
        ],
    ],

    'palettes' => [
        'palette_1' => [
            'showitem' => 'palette_input_1, palette_input_2',
        ],
    ],

];

<?php

return [
    'ctrl' => [
        'title' => 'Form engine elements - rte',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'translationSource' => 'l10n_source',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
    ],

    'columns' => [

        'hidden' => [
            'exclude' => 1,
            'config' => [
                'type' => 'check',
                'items' => [
                    '1' => [
                        '0' => 'Disable',
                    ],
                ],
            ],
        ],
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'special' => 'languages',
                'items' => [
                    [
                        'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.allLanguages',
                        -1,
                        'flags-multiple'
                    ],
                ],
                'default' => 0,
            ]
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'Translation parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        '',
                        0
                    ]
                ],
                'foreign_table' => 'tx_styleguide_elements_rte',
                'foreign_table_where' => 'AND {#tx_styleguide_elements_rte}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_elements_rte}.{#sys_language_uid} IN (-1,0)',
                'default' => 0
            ]
        ],
        'l10n_source' => [
            'exclude' => true,
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'Translation source',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        '',
                        0
                    ]
                ],
                'foreign_table' => 'tx_styleguide_elements_rte',
                'foreign_table_where' => 'AND {#tx_styleguide_elements_rte}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_elements_rte}.{#uid}!=###THIS_UID###',
                'default' => 0
            ]
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => ''
            ]
        ],

        'rte_1' => [
            'exclude' => 1,
            'label' => 'rte_1 description',
            'description' => 'field description',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'fieldControl' => [
                    'fullScreenRichtext' => [
                        'disabled' => false,
                    ],
                ],
            ],
        ],
        'rte_2' => [
            'exclude' => 1,
            'label' => 'rte_2 default value',
            'config' => [
                'type' => 'text',
                'default' => 'rte_2',
                'enableRichtext' => true,
            ],
        ],
        'rte_3' => [
            'exclude' => 1,
            'label' => 'rte_3 eval=null',
            'config' => [
                'type' => 'text',
                'eval' => 'null',
                'enableRichtext' => true,
            ],
        ],
        'rte_4' => [
            'exclude' => 1,
            'label' => 'rte_4 richtextConfiguration=minimal',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'richtextConfiguration' => 'minimal'
            ],
        ],
        'rte_5' => [
            'exclude' => 1,
            'label' => 'rte_5 richtextConfiguration=full',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'richtextConfiguration' => 'full'
            ],
        ],

        'rte_inline_1' => [
            'exclude' => 1,
            'label' => 'rte_inline_1',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_styleguide_elements_rte_inline_1_child',
                'foreign_field' => 'parentid',
                'foreign_table_field' => 'parenttable',
            ],
        ],

        'rte_flex_1' => [
            'exclude' => 1,
            'label' => 'rte_flex_1',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => '
                        <T3DataStructure>
                            <sheets>
                                <sGeneral>
                                    <ROOT>
                                        <TCEforms>
                                            <sheetTitle>tab</sheetTitle>
                                        </TCEforms>
                                        <type>array</type>
                                        <el>
                                            <rte_1>
                                                <TCEforms>
                                                    <label>rte_1</label>
                                                    <config>
                                                        <type>text</type>
                                                        <enableRichtext>1</enableRichtext>
                                                    </config>
                                                </TCEforms>
                                            </rte_1>
                                            <rte.2>
                                                <TCEforms>
                                                    <label>rte.2</label>
                                                    <config>
                                                        <type>text</type>
                                                        <enableRichtext>1</enableRichtext>
                                                    </config>
                                                </TCEforms>
                                            </rte.2>
                                        </el>
                                    </ROOT>
                                </sGeneral>
                                <sSections>
                                    <ROOT>
                                        <TCEforms>
                                            <sheetTitle>section</sheetTitle>
                                        </TCEforms>
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
                                                            <rte_1>
                                                                <TCEforms>
                                                                    <label>rte_1</label>
                                                                    <config>
                                                                        <type>text</type>
                                                                        <enableRichtext>1</enableRichtext>
                                                                    </config>
                                                                </TCEforms>
                                                            </rte_1>
                                                        </el>
                                                    </container_1>
                                                </el>
                                            </section_1>
                                        </el>
                                    </ROOT>
                                </sSections>
                                <sInline>
                                    <ROOT>
                                        <TCEforms>
                                            <sheetTitle>inline</sheetTitle>
                                        </TCEforms>
                                        <type>array</type>
                                        <el>
                                            <inline_1>
                                                <TCEforms>
                                                    <label>inline_1</label>
                                                    <config>
                                                        <type>inline</type>
                                                        <foreign_table>tx_styleguide_elements_rte_flex_1_inline_1_child</foreign_table>
                                                        <foreign_field>parentid</foreign_field>
                                                        <foreign_table_field>parenttable</foreign_table_field>
                                                    </config>
                                                </TCEforms>
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

        'input_palette_1' => [
            'exclude' => 1,
            'label' => 'input_palette_1',
            'config' => [
                'type' => 'input',
            ],
        ],

        'rte_palette_1' => [
            'exclude' => 1,
            'label' => 'rte_palette_1',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
            ],
        ],

    ],

    'types' => [
        '0' => [
            'showitem' => '
                --div--;rte,
                    rte_1, rte_2, rte_3, rte_4, rte_5,
                --div--;in inline,
                    rte_inline_1,
                --div--;in flex,
                    rte_flex_1,
                --div--;in palette,
                    --palette--;palette;rte_1,
            ',
        ],
    ],

    'palettes' => [
        'rte_1' => [
            'showitem' => 'input_palette_1,--linebreak--,rte_palette_1',
        ],
    ],

];

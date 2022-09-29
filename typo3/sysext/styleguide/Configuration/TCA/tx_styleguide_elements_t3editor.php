<?php

return [
    'ctrl' => [
        'title' => 'Form engine elements - t3editor',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
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
            'config' => [
                'type' => 'check',
                'items' => [
                    ['Disable'],
                ],
            ],
        ],
        'sys_language_uid' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',
            ],
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
                        0,
                    ],
                ],
                'foreign_table' => 'tx_styleguide_elements_t3editor',
                'foreign_table_where' => 'AND {#tx_styleguide_elements_t3editor}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_elements_t3editor}.{#sys_language_uid} IN (-1,0)',
                'default' => 0,
            ],
        ],
        'l10n_source' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'Translation source',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        '',
                        0,
                    ],
                ],
                'foreign_table' => 'tx_styleguide_elements_t3editor',
                'foreign_table_where' => 'AND {#tx_styleguide_elements_t3editor}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_elements_t3editor}.{#uid}!=###THIS_UID###',
                'default' => 0,
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => '',
            ],
        ],

        't3editor_1' => [
            'label' => 't3editor_1 format=html, rows=7',
            'description' => 'field description',
            'config' => [
                'type' => 'text',
                'renderType' => 't3editor',
                'format' => 'html',
                'rows' => 7,
            ],
        ],
        't3editor_reload_1' => [
            'label' => 't3editor_reload_1',
            'onChange' => 'reload',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label1',
                        0,
                    ],
                    [
                        'label2',
                        1,
                    ],
                ],
            ],
        ],
        't3editor_2' => [
            'label' => 't3editor_2',
            'description' => 'readOnly=true',
            'config' => [
                'type' => 'text',
                'renderType' => 't3editor',
                'format' => 'html',
                'readOnly' => true,
            ],
        ],
        't3editor_inline_1' => [
            'label' => 't3editor_inline_1',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_styleguide_elements_t3editor_inline_1_child',
                'foreign_field' => 'parentid',
                'foreign_table_field' => 'parenttable',
            ],
        ],
        't3editor_flex_1' => [
            'label' => 't3editor_flex_1',
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
                                            <t3editor_1>
                                                <label>t3editor_1 description</label>
                                                <description>field description</description>
                                                <config>
                                                    <type>text</type>
                                                    <renderType>t3editor</renderType>
                                                    <format>html</format>
                                                </config>
                                            </t3editor_1>
                                        </el>
                                    </ROOT>
                                </sGeneral>
                                <sSection>
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
                                                            <t3editor_1>
                                                                <label>t3editor_1 description</label>
                                                                <description>field description</description>
                                                                <config>
                                                                    <type>text</type>
                                                                    <renderType>t3editor</renderType>
                                                                    <format>html</format>
                                                                </config>
                                                            </t3editor_1>
                                                        </el>
                                                    </container_1>
                                                </el>
                                            </section_1>
                                        </el>
                                    </ROOT>
                                </sSection>
                                <sInline>
                                    <ROOT>
                                        <sheetTitle>inline</sheetTitle>
                                        <type>array</type>
                                        <el>
                                            <inline_1>
                                                <label>inline_1</label>
                                                <config>
                                                    <type>inline</type>
                                                    <foreign_table>tx_styleguide_elements_t3editor_flex_1_inline_1_child</foreign_table>
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

    ],

    'types' => [
        '0' => [
            'showitem' => '
                --div--;t3editor,
                    t3editor_reload_1,
                    t3editor_1,
                    t3editor_2,
                --div--;in inline,
                    t3editor_inline_1,
                --div--;in flex,
                    t3editor_flex_1,
            ',
        ],
    ],

];

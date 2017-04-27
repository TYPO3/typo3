<?php
return [
    'ctrl' => [
        'title' => 'Form engine elements - rsainput',
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
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
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
        'starttime' => [
            'exclude' => 1,
            'label' => 'Publish Date',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime',
                'default' => '0'
            ],
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly'
        ],
        'endtime' => [
            'exclude' => 1,
            'label' => 'Expiration Date',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime',
                'default' => '0',
                'range' => [
                    'upper' => mktime(0, 0, 0, 12, 31, 2020)
                ]
            ],
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly'
        ],
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'special' => 'languages',
                'items' => [
                    [
                        'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.allLanguages',
                        -1,
                        'flags-multiple'
                    ],
                ],
                'default' => 0,
            ]
        ],
        'l10n_parent' => [
            'exclude' => true,
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
                'foreign_table' => 'tx_styleguide_elements_rsainput',
                'foreign_table_where' => 'AND tx_styleguide_elements_rsainput.pid=###CURRENT_PID### AND tx_styleguide_elements_rsainput.sys_language_uid IN (-1,0)',
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
                'foreign_table' => 'tx_styleguide_elements_rsainput',
                'foreign_table_where' => 'AND tx_styleguide_elements_rsainput.pid=###CURRENT_PID### AND tx_styleguide_elements_rsainput.uid!=###THIS_UID###',
                'default' => 0
            ]
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => ''
            ]
        ],

        'rsainput_1' => [
            'exclude' => 1,
            'label' => 'rsainput_1',
            'config' => [
                'type' => 'input',
                'renderType' => 'rsaInput',
            ],
        ],
        'rsainput_inline_1' => [
            'exclude' => 1,
            'label' => 'rsainput_inline_1',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_styleguide_elements_rsainput_inline_1_child',
                'foreign_field' => 'parentid',
                'foreign_table_field' => 'parenttable',
            ],
        ],
        'rsainput_flex_1' => [
            'exclude' => 1,
            'label' => 'rsainput_flex_1',
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
                                            <rsainput_1>
                                                <TCEforms>
                                                    <label>rsainput_1</label>
                                                    <config>
                                                        <type>input</type>
                                                        <renderType>rsaInput</renderType>
                                                    </config>
                                                </TCEforms>
                                            </rsainput_1>
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
                                                            <rsainput_1>
                                                                <TCEforms>
                                                                    <label>rsainput_1</label>
                                                                    <config>
                                                                        <type>input</type>
                                                                        <renderType>rsaInput</renderType>
                                                                    </config>
                                                                </TCEforms>
                                                            </rsainput_1>
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
                                                        <foreign_table>tx_styleguide_elements_rsainput_flex_1_inline_1_child</foreign_table>
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


    ],


    'types' => [
        '0' => [
            'showitem' => '
                --div--;rsainput,
                    rsainput_1,
                --div--;inline,
                    rsainput_inline_1,
                --div--;flex,
                    rsainput_flex_1,
                --div--;meta,
                disable, starttime, endtime, sys_language_uid, l10n_parent, l10n_source,
            ',
        ],


    ],


];

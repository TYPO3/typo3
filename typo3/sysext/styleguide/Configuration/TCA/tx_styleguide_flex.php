<?php

return [
    'ctrl' => [
        'title' => 'Form engine - flex',
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
                'foreign_table' => 'tx_styleguide_flex',
                'foreign_table_where' => 'AND {#tx_styleguide_flex}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_flex}.{#sys_language_uid} IN (-1,0)',
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
                'foreign_table' => 'tx_styleguide_flex',
                'foreign_table_where' => 'AND {#tx_styleguide_flex}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_flex}.{#uid}!=###THIS_UID###',
                'default' => 0
            ]
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => ''
            ]
        ],

        'flex_file_1' => [
            'exclude' => 1,
            'label' => 'flex_file_1 simple flexform in external file',
            'description' => 'field description',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => 'FILE:EXT:styleguide/Configuration/FlexForms/Simple.xml',
                ],
            ],
        ],

        'flex_5' => [
            'exclude' => 1,
            'label' => 'flex_5 no sheets description',
            'description' => 'field description',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => '
                        <T3DataStructure>
                            <ROOT>
                                <type>array</type>
                                <el>
                                    <input_1>
                                        <TCEforms>
                                            <label>input_1</label>
                                            <config>
                                                <type>input</type>
                                            </config>
                                        </TCEforms>
                                    </input_1>
                                    <input_2>
                                        <TCEforms>
                                            <label>input_2, default value</label>
                                            <config>
                                                <type>input</type>
                                                <default>foo</default>
                                            </config>
                                        </TCEforms>
                                    </input_2>
                                    <passthrough_1>
                                        <TCEforms>
                                            <label>passthrough_1</label>
                                            <config>
                                                <type>passthrough</type>
                                            </config>
                                        </TCEforms>
                                    </passthrough_1>
                                    <passthrough_2>
                                        <TCEforms>
                                            <label>passthrough_2 with default value</label>
                                            <config>
                                                <type>passthrough</type>
                                                <default>passthrough default</default>
                                            </config>
                                        </TCEforms>
                                    </passthrough_2>
                                </el>
                            </ROOT>
                        </T3DataStructure>
                    ',
                ],
            ],
        ],

        'flex_1' => [
            'exclude' => 1,
            'label' => 'flex_1 sheet description',
            'description' => 'field description',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => '
                        <T3DataStructure>
                            <sheets>
                                <sSheetdescription_1>
                                    <ROOT>
                                        <TCEforms>
                                            <sheetTitle>sheet description 1</sheetTitle>
                                            <sheetDescription>
                                                sheetDescription: Lorem ipsum dolor sit amet, consectetur adipiscing elit.
                                                Nam id ante ornare, iaculis elit a, malesuada augue. Etiam neque odio,
                                                condimentum sed dolor vitae, sollicitudin varius lacus. Pellentesque sit amet aliquam arcu.
                                                Phasellus ut euismod felis. Fusce at tempor turpis.

                                                Nam eu arcu id lorem vestibulum tristique vel in erat. Phasellus maximus, arcu nec
                                                condimentum venenatis, mauris nisl venenatis tellus, eget suscipit arcu nunc et purus.
                                                Nunc luctus congue vulputate. Donec placerat, lorem vitae rhoncus euismod, ipsum ligula
                                                tempor sapien, ac sodales metus mauris et lacus. Donec in ante a lectus semper rutrum nec
                                                ut orci. Quisque id mi ultrices lacus fermentum consequat quis sed odio. Sed quis turpis
                                                rutrum, convallis sem vitae, cursus enim. Maecenas sit amet sem nisi.
                                            </sheetDescription>
                                            <sheetShortDescr>
                                                sheetShortDescr: Lorem ipsum dolor sit amet, consectetur adipiscing elit.
                                                Nam id ante ornare, iaculis elit a, malesuada augue. Etiam neque odio,
                                                condimentum sed dolor vitae, sollicitudin varius lacus. Pellentesque sit amet aliquam arcu.
                                                Phasellus ut euismod felis. Fusce at tempor turpis.
                                            </sheetShortDescr>
                                        </TCEforms>
                                        <type>array</type>
                                        <el>
                                            <input_1>
                                                <TCEforms>
                                                    <label>input_1</label>
                                                    <config>
                                                        <type>input</type>
                                                    </config>
                                                </TCEforms>
                                            </input_1>
                                        </el>
                                    </ROOT>
                                </sSheetdescription_1>
                                <sSheetdescription_2>
                                    <ROOT>
                                        <TCEforms>
                                            <sheetTitle>sheet description 2</sheetTitle>
                                            <sheetDescription>
                                                foo
                                            </sheetDescription>
                                            <sheetShortDescr>
                                                bar
                                           </sheetShortDescr>
                                        </TCEforms>
                                        <type>array</type>
                                        <el>
                                            <input_2>
                                                <TCEforms>
                                                    <label>input_2</label>
                                                    <config>
                                                        <type>input</type>
                                                    </config>
                                                </TCEforms>
                                            </input_2>
                                        </el>
                                    </ROOT>
                                </sSheetdescription_2>
                            </sheets>
                        </T3DataStructure>
                    ',
                ],
            ],
        ],

        'flex_2' => [
            'exclude' => 1,
            'label' => 'flex_2 section container',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => '
                        <T3DataStructure>
                            <sheets>
                                <sSection>
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
                                                            <input_1>
                                                                <TCEforms>
                                                                    <label>input_1 description</label>
                                                                    <description>field description</description>
                                                                    <config>
                                                                        <type>input</type>
                                                                    </config>
                                                                </TCEforms>
                                                            </input_1>
                                                            <input_2>
                                                                <TCEforms>
                                                                    <label>input_2 renderType=colorpicker</label>
                                                                    <config>
                                                                        <type>input</type>
                                                                        <renderType>colorpicker</renderType>
                                                                        <size>10</size>
                                                                    </config>
                                                                </TCEforms>
                                                            </input_2>
                                                        </el>
                                                    </container_1>
                                                    <container_2>
                                                        <type>array</type>
                                                        <title>container_2</title>
                                                        <el>
                                                            <text_1>
                                                                <TCEforms>
                                                                    <label>text_1 default "foo"</label>
                                                                    <config>
                                                                        <type>text</type>
                                                                        <default>foo</default>
                                                                    </config>
                                                                </TCEforms>
                                                            </text_1>
                                                        </el>
                                                    </container_2>
                                                </el>
                                            </section_1>
                                        </el>
                                    </ROOT>
                                </sSection>
                            </sheets>
                        </T3DataStructure>
                    ',
                ],
            ],
        ],

        'flex_3' => [
            'exclude' => 1,
            'label' => 'flex_3 inline',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => '
                        <T3DataStructure>
                            <sheets>
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
                                                        <foreign_table>tx_styleguide_flex_flex_3_inline_1_child</foreign_table>
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

        'flex_6' => [
            'exclude' => 1,
            'label' => 'flex_6',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => '
                        <T3DataStructure>
                            <sheets>
                                <sheet_1>
                                    <ROOT>
                                        <TCEforms>
                                            <sheetTitle>sheet_1</sheetTitle>
                                        </TCEforms>
                                        <type>array</type>
                                        <el>
                                            <select_1>
                                                <TCEforms>
                                                    <label>select_1</label>
                                                    <onChange>reload</onChange>
                                                    <config>
                                                        <type>select</type>
                                                        <renderType>selectSingle</renderType>
                                                        <items type="array">
                                                            <numIndex index="0" type="array">
                                                                <numIndex index="0">input_2 and input_3 not shown</numIndex>
                                                                <numIndex index="1">0</numIndex>
                                                            </numIndex>
                                                            <numIndex index="1" type="array">
                                                                <numIndex index="0">input_2 and input_3 shown</numIndex>
                                                                <numIndex index="1">1</numIndex>
                                                            </numIndex>
                                                        </items>
                                                        <maxitems>1</maxitems>
                                                        <size>1</size>
                                                    </config>
                                                </TCEforms>
                                            </select_1>
                                            <select_2>
                                                <TCEforms>
                                                    <label>select_2</label>
                                                    <onChange>reload</onChange>
                                                    <config>
                                                        <type>select</type>
                                                        <renderType>selectSingle</renderType>
                                                        <items type="array">
                                                            <numIndex index="0" type="array">
                                                                <numIndex index="0">input_1 of sheet_2 not shown</numIndex>
                                                                <numIndex index="1">0</numIndex>
                                                            </numIndex>
                                                            <numIndex index="1" type="array">
                                                                <numIndex index="0">input_1 of sheet_2 shown</numIndex>
                                                                <numIndex index="1">1</numIndex>
                                                            </numIndex>
                                                        </items>
                                                        <maxitems>1</maxitems>
                                                        <size>1</size>
                                                    </config>
                                                </TCEforms>
                                            </select_2>
                                            <input_1>
                                                <TCEforms>
                                                    <label>input_1</label>
                                                    <displayCond>FIELD:parentRec.flex_6_select_1:=:0</displayCond>
                                                    <config>
                                                        <type>input</type>
                                                    </config>
                                                </TCEforms>
                                            </input_1>
                                            <input_2>
                                                <TCEforms>
                                                    <label>input_2</label>
                                                    <displayCond>FIELD:select_1:=:1</displayCond>
                                                    <config>
                                                        <type>input</type>
                                                    </config>
                                                </TCEforms>
                                            </input_2>
                                            <input_3>
                                                <TCEforms>
                                                    <label>input_3</label>
                                                    <displayCond>FIELD:sheet_1.select_1:=:1</displayCond>
                                                    <config>
                                                        <type>input</type>
                                                    </config>
                                                </TCEforms>
                                            </input_3>
                                        </el>
                                    </ROOT>
                                </sheet_1>
                                <sheet_2>
                                    <ROOT>
                                        <TCEforms>
                                            <sheetTitle>sheet_2</sheetTitle>
                                        </TCEforms>
                                        <type>array</type>
                                        <el>
                                            <input_1>
                                                <TCEforms>
                                                    <label>input_1</label>
                                                    <displayCond>FIELD:sheet_1.select_2:=:1</displayCond>
                                                    <config>
                                                        <type>input</type>
                                                    </config>
                                                </TCEforms>
                                            </input_1>
                                        </el>
                                    </ROOT>
                                </sheet_2>
                                <sheet_3>
                                    <ROOT>
                                        <TCEforms>
                                            <sheetTitle>sheet_3</sheetTitle>
                                        </TCEforms>
                                        <type>array</type>
                                        <el>
                                            <input_1>
                                                <TCEforms>
                                                    <label>input_1</label>
                                                    <config>
                                                        <type>input</type>
                                                        <default>foo</default>
                                                    </config>
                                                </TCEforms>
                                            </input_1>
                                            <input_2>
                                                <TCEforms>
                                                    <label>input_2</label>
                                                    <config>
                                                        <type>input</type>
                                                        <default>bar</default>
                                                    </config>
                                                </TCEforms>
                                            </input_2>
                                            <input_3>
                                                <TCEforms>
                                                    <label>input_3 (depends on input_1=foo AND input_2=bar)</label>
                                                    <displayCond>
                                                        <and>
                                                            <value1>FIELD:sheet_3.input_1:=:foo</value1>
                                                            <value2>FIELD:sheet_3.input_2:=:bar</value2>
                                                        </and>
                                                    </displayCond>
                                                    <config>
                                                        <type>input</type>
                                                    </config>
                                                </TCEforms>
                                            </input_3>
                                        </el>
                                    </ROOT>
                                </sheet_3>
                            </sheets>
                        </T3DataStructure>
                    ',
                ],
            ],
        ],
        'flex_6_select_1' => [
            'exclude' => 1,
            'label' => 'flex_6_select_1',
            'onChange' => 'reload',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    0 => [
                        'input_1 shown',
                        0,
                    ],
                    1 => [
                        'input_1 not shown',
                        1,
                    ],
                ],
            ]
        ],

        'flex_4' => [
            'exclude' => 1,
            'label' => 'flex_4',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => '
                        <T3DataStructure>
                            <sheets>
                                <sheet_1>
                                    <ROOT>
                                        <TCEforms>
                                            <sheetTitle>sheet_1</sheetTitle>
                                        </TCEforms>
                                        <type>array</type>
                                        <el>
                                            <select_1>
                                                <TCEforms>
                                                    <label>select_1</label>
                                                    <onChange>reload</onChange>
                                                    <config>
                                                        <type>select</type>
                                                        <renderType>selectSingle</renderType>
                                                        <items type="array">
                                                            <numIndex index="0" type="array">
                                                                <numIndex index="0">sheet_2 not shown</numIndex>
                                                                <numIndex index="1">0</numIndex>
                                                            </numIndex>
                                                            <numIndex index="1" type="array">
                                                                <numIndex index="0">sheet_2 shown</numIndex>
                                                                <numIndex index="1">1</numIndex>
                                                            </numIndex>
                                                        </items>
                                                        <maxitems>1</maxitems>
                                                        <size>1</size>
                                                    </config>
                                                </TCEforms>
                                            </select_1>
                                        </el>
                                    </ROOT>
                                </sheet_1>
                                <sheet_2>
                                    <ROOT>
                                        <TCEforms>
                                            <sheetTitle>sheet_2</sheetTitle>
                                            <displayCond>FIELD:sheet_1.select_1:=:1</displayCond>
                                        </TCEforms>
                                        <type>array</type>
                                        <el>
                                            <input_1>
                                                <TCEforms>
                                                    <label>input_1</label>
                                                    <config>
                                                        <type>input</type>
                                                    </config>
                                                </TCEforms>
                                            </input_1>
                                        </el>
                                    </ROOT>
                                </sheet_2>
                                <sheet_3>
                                    <ROOT>
                                        <TCEforms>
                                            <sheetTitle>sheet_3</sheetTitle>
                                            <displayCond>FIELD:parentRec.flex_4_select_1:=:1</displayCond>
                                        </TCEforms>
                                        <type>array</type>
                                        <el>
                                            <input_2>
                                                <TCEforms>
                                                    <label>input_2</label>
                                                    <config>
                                                        <type>input</type>
                                                    </config>
                                                </TCEforms>
                                            </input_2>
                                        </el>
                                    </ROOT>
                                </sheet_3>
                            </sheets>
                        </T3DataStructure>
                    ',
                ],
            ],
        ],
        'flex_4_select_1' => [
            'exclude' => 1,
            'label' => 'flex_4_select_1',
            'onChange' => 'reload',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'default' => 1,
                'items' => [
                    0 => [
                        'sheet_3 not shown',
                        0,
                    ],
                    1 => [
                        'sheet_3 shown',
                        1,
                    ],
                ],
            ],
        ],

        'flex_7' => [
            'exclude' => 1,
            'label' => 'flex_7',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => '
                        <T3DataStructure>
                            <sheets>
                                <sheet_1>
                                    <ROOT>
                                        <TCEforms>
                                            <sheetTitle>sheet_1</sheetTitle>
                                        </TCEforms>
                                        <type>array</type>
                                        <el>
                                            <select_1>
                                                <TCEforms>
                                                    <label>select_1</label>
                                                    <onChange>reload</onChange>
                                                    <config>
                                                        <type>select</type>
                                                        <renderType>selectSingle</renderType>
                                                        <items type="array">
                                                            <numIndex index="0" type="array">
                                                                <numIndex index="0">input_3 and input_4 not shown</numIndex>
                                                                <numIndex index="1">0</numIndex>
                                                            </numIndex>
                                                            <numIndex index="1" type="array">
                                                                <numIndex index="0">input_3 and input_4 shown</numIndex>
                                                                <numIndex index="1">1</numIndex>
                                                            </numIndex>
                                                        </items>
                                                        <maxitems>1</maxitems>
                                                        <size>1</size>
                                                    </config>
                                                </TCEforms>
                                            </select_1>
                                            <section_1>
                                                <title>section_1</title>
                                                <type>array</type>
                                                <section>1</section>
                                                <el>
                                                    <container_1>
                                                        <type>array</type>
                                                        <title>container_1</title>
                                                        <el>
                                                             <select_2>
                                                                <TCEforms>
                                                                    <label>select_2</label>
                                                                    <onChange>reload</onChange>
                                                                    <config>
                                                                        <type>select</type>
                                                                        <renderType>selectSingle</renderType>
                                                                        <items type="array">
                                                                            <numIndex index="0" type="array">
                                                                                <numIndex index="0">input_5 not shown</numIndex>
                                                                                <numIndex index="1">0</numIndex>
                                                                            </numIndex>
                                                                            <numIndex index="1" type="array">
                                                                                <numIndex index="0">input_5 shown</numIndex>
                                                                                <numIndex index="1">1</numIndex>
                                                                            </numIndex>
                                                                        </items>
                                                                        <maxitems>1</maxitems>
                                                                        <size>1</size>
                                                                    </config>
                                                                </TCEforms>
                                                            </select_2>
                                                            <input_1>
                                                                <TCEforms>
                                                                    <label>input_1 always shown</label>
                                                                    <config>
                                                                        <type>input</type>
                                                                    </config>
                                                                </TCEforms>
                                                            </input_1>
                                                            <input_2>
                                                                <TCEforms>
                                                                    <label>input_2</label>
                                                                    <displayCond>FIELD:parentRec.flex_7_select_1:=:1</displayCond>
                                                                    <config>
                                                                        <type>input</type>
                                                                    </config>
                                                                </TCEforms>
                                                            </input_2>
                                                            <input_3>
                                                                <TCEforms>
                                                                    <label>input_3</label>
                                                                    <displayCond>FIELD:select_1:=:1</displayCond>
                                                                    <config>
                                                                        <type>input</type>
                                                                    </config>
                                                                </TCEforms>
                                                            </input_3>
                                                            <input_4>
                                                                <TCEforms>
                                                                    <label>input_4</label>
                                                                    <displayCond>FIELD:sheet_1.select_1:=:1</displayCond>
                                                                    <config>
                                                                        <type>input</type>
                                                                    </config>
                                                                </TCEforms>
                                                            </input_4>
                                                            <input_5>
                                                                <TCEforms>
                                                                    <label>input_5</label>
                                                                    <displayCond>FIELD:select_2:=:1</displayCond>
                                                                    <config>
                                                                        <type>input</type>
                                                                    </config>
                                                                </TCEforms>
                                                            </input_5>
                                                            <input_6>
                                                                <TCEforms>
                                                                    <label>input_6</label>
                                                                    <displayCond>FIELD:sheet_2.select_1:=:1</displayCond>
                                                                    <config>
                                                                        <type>input</type>
                                                                    </config>
                                                                </TCEforms>
                                                            </input_6>
                                                        </el>
                                                    </container_1>
                                                </el>
                                            </section_1>
                                        </el>
                                    </ROOT>
                                </sheet_1>
                                <sheet_2>
                                    <ROOT>
                                        <TCEforms>
                                            <sheetTitle>sheet_2</sheetTitle>
                                        </TCEforms>
                                        <type>array</type>
                                        <el>
                                            <select_1>
                                                <TCEforms>
                                                    <label>select_1</label>
                                                    <onChange>reload</onChange>
                                                    <config>
                                                        <type>select</type>
                                                        <renderType>selectSingle</renderType>
                                                        <items type="array">
                                                            <numIndex index="0" type="array">
                                                                <numIndex index="0">input_6 on sheet_1 containers not shown</numIndex>
                                                                <numIndex index="1">0</numIndex>
                                                            </numIndex>
                                                            <numIndex index="1" type="array">
                                                                <numIndex index="0">input_6 on sheet_1 containers shown</numIndex>
                                                                <numIndex index="1">1</numIndex>
                                                            </numIndex>
                                                        </items>
                                                        <maxitems>1</maxitems>
                                                        <size>1</size>
                                                    </config>
                                                </TCEforms>
                                            </select_1>
                                        </el>
                                    </ROOT>
                                </sheet_2>
                            </sheets>
                        </T3DataStructure>
                    ',
                ],
            ],
        ],
        'flex_7_select_1' => [
            'exclude' => 1,
            'label' => 'flex_7_select_1',
            'onChange' => 'reload',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'default' => 1,
                'items' => [
                    0 => [
                        'input_2 not shown',
                        0,
                    ],
                    1 => [
                        'input_2 shown',
                        1,
                    ],
                ],
            ],
        ],

    ],

    'types' => [
        '0' => [
            'showitem' => '
                --div--;simple,
                    flex_file_1,
                --div--;no sheets,
                    flex_5,
                --div--;sheet description,
                    flex_1,
                --div--;section container,
                    flex_2,
                --div--;inline,
                    flex_3,
                --div--;displayCond fields,
                    flex_6_select_1, flex_6,
                --div--;displayCond sheets,
                    flex_4_select_1, flex_4,
                --div--;displayCond container fields,
                    flex_7_select_1, flex_7,
            ',
        ],
    ],

];

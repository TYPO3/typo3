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
                    ['Disable'],
                ],
            ],
        ],
        'sys_language_uid' => [
            'exclude' => true,
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
                'foreign_table' => 'tx_styleguide_flex',
                'foreign_table_where' => 'AND {#tx_styleguide_flex}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_flex}.{#sys_language_uid} IN (-1,0)',
                'default' => 0,
            ],
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
                        0,
                    ],
                ],
                'foreign_table' => 'tx_styleguide_flex',
                'foreign_table_where' => 'AND {#tx_styleguide_flex}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_flex}.{#uid}!=###THIS_UID###',
                'default' => 0,
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => '',
            ],
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
                                    <checkbox_1>
                                       <TCEforms>
                                           <label>checkbox_1 one checkbox with label</label>
                                           <config>
                                               <type>check</type>
                                               <items type="array">
                                                   <numIndex index="0" type="array">
                                                       <numIndex index="0">foo</numIndex>
                                                       <numIndex index="1"></numIndex>
                                                   </numIndex>
                                               </items>
                                           </config>
                                       </TCEforms>
                                    </checkbox_1>
                                    <checkbox_2>
                                       <TCEforms>
                                           <label>checkbox_2 cols=3</label>
                                           <config>
                                               <type>check</type>
                                               <items type="array">
                                                   <numIndex index="0" type="array">
                                                       <numIndex index="0">foo1</numIndex>
                                                       <numIndex index="1"></numIndex>
                                                   </numIndex>
                                                   <numIndex index="1" type="array">
                                                       <numIndex index="0">foo2</numIndex>
                                                       <numIndex index="1"></numIndex>
                                                   </numIndex>
                                                   <numIndex index="2" type="array">
                                                       <numIndex index="0">foo3</numIndex>
                                                       <numIndex index="1"></numIndex>
                                                   </numIndex>
                                                   <numIndex index="3" type="array">
                                                       <numIndex index="0">foo4</numIndex>
                                                       <numIndex index="1"></numIndex>
                                                   </numIndex>
                                               </items>
                                               <cols>3</cols>
                                           </config>
                                       </TCEforms>
                                    </checkbox_2>
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

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
        'default_sortby' => 'ORDER BY crdate',
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
                'size' => '13',
                'max' => '20',
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
                'size' => '13',
                'max' => '20',
                'eval' => 'datetime',
                'default' => '0',
                'range' => [
                    'upper' => mktime(0, 0, 0, 12, 31, 2020)
                ]
            ],
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly'
        ],


        'flex_1' => [
            'exclude' => 1,
            'label' => 'flex_1 sheet description',
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
                                                                    <label>input_1</label>
                                                                    <config>
                                                                        <type>input</type>
                                                                    </config>
                                                                </TCEforms>
                                                            </input_1>
                                                        </el>
                                                    </container_1>
                                                    <container_2>
                                                        <type>array</type>
                                                        <title>container_2</title>
                                                        <el>
                                                            <text_1>
                                                                <TCEforms>
                                                                    <label>text_1</label>
                                                                    <config>
                                                                        <type>text</type>
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
        'flex_4' => [
            'exclude' => 1,
            'label' => 'flex_4 condition',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => '
                        <T3DataStructure>
                            <sheets>
                                <sheet1>
                                    <ROOT>
                                        <TCEforms>
                                            <sheetTitle>selector</sheetTitle>
                                        </TCEforms>
                                        <type>array</type>
                                        <el>
                                            <switchableControllerActions>
                                                <TCEforms>
                                                    <label>select view</label>
                                                    <onChange>reload</onChange>
                                                    <config>
                                                        <type>select</type>
                                                        <renderType>selectSingle</renderType>
                                                        <items type="array">
                                                            <numIndex index="0" type="array">
                                                                <numIndex index="0">sheet 2 not shown</numIndex>
                                                                <numIndex index="1"></numIndex>
                                                            </numIndex>
                                                            <numIndex index="1" type="array">
                                                                <numIndex index="0">sheet 2 shown</numIndex>
                                                                <numIndex index="1">ControllerName->actionname;</numIndex>
                                                            </numIndex>
                                                        </items>
                                                        <maxitems>1</maxitems>
                                                        <size>1</size>
                                                    </config>
                                                </TCEforms>
                                            </switchableControllerActions>
                                        </el>
                                    </ROOT>
                                </sheet1>
                                <sheet2>
                                    <ROOT>
                                        <TCEforms>
                                            <sheetTitle>sheet 2</sheetTitle>
                                            <displayCond><![CDATA[FIELD:sheet1.switchableControllerActions:=:ControllerName->actionname;]]></displayCond>
                                        </TCEforms>
                                        <type>array</type>
                                        <el>
                                            <settings.foo>
                                                <TCEforms>
                                                    <label>foo</label>
                                                    <config>
                                                        <type>input</type>
                                                    </config>
                                                </TCEforms>
                                            </settings.foo>
                                        </el>
                                    </ROOT>
                                </sheet2>
                            </sheets>
                        </T3DataStructure>
                    ',
                ],
            ],
        ],
        'flex_5' => [
            'exclude' => 1,
            'label' => 'flex_5 no sheets',
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
                                </el>
                            </ROOT>
                        </T3DataStructure>
                    ',
                ],
            ],
        ],


    ],


    'types' => [
        '0' => [
            'showitem' => '
                --div--;no sheets,
                    flex_5,
                --div--;sheet description,
                    flex_1,
                --div--;section container,
                    flex_2,
                --div--;inline,
                    flex_3,
                --div--;condition,
                    flex_4,
            ',
        ],
    ],


];

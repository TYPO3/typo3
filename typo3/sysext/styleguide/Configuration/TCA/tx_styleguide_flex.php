<?php

return [
    'ctrl' => [
        'title' => 'Form engine - flex',
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
        'flex_file_1' => [
            'label' => 'flex_file_1 simple flexform in external file',
            'description' => 'field description',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => 'FILE:EXT:styleguide/Configuration/FlexForms/Simple.xml',
                ],
            ],
        ],

        'flex_file_2' => [
            'label' => 'flex_file_2 more complex flexform in external file',
            'description' => 'field description',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => 'FILE:EXT:styleguide/Configuration/FlexForms/MultipleSheets.xml',
                ],
            ],
        ],

        'flex_5' => [
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
                                        <label>input_1</label>
                                        <config>
                                            <type>input</type>
                                        </config>
                                    </input_1>
                                    <input_2>
                                        <label>input_2, default value</label>
                                        <config>
                                            <type>input</type>
                                            <default>foo</default>
                                        </config>
                                    </input_2>
                                    <passthrough_1>
                                        <label>passthrough_1</label>
                                        <config>
                                            <type>passthrough</type>
                                        </config>
                                    </passthrough_1>
                                    <passthrough_2>
                                        <label>passthrough_2 with default value</label>
                                        <config>
                                            <type>passthrough</type>
                                            <default>passthrough default</default>
                                        </config>
                                    </passthrough_2>
                                    <checkbox_1>
                                       <label>checkbox_1 one checkbox with label</label>
                                       <config>
                                           <type>check</type>
                                           <items type="array">
                                               <numIndex index="0" type="array">
                                                   <label>foo</label>
                                                   <value></value>
                                               </numIndex>
                                           </items>
                                       </config>
                                    </checkbox_1>
                                    <checkbox_2>
                                       <label>checkbox_2 cols=3</label>
                                       <config>
                                           <type>check</type>
                                           <items type="array">
                                               <numIndex index="0" type="array">
                                                   <label>foo1</label>
                                                   <value></value>
                                               </numIndex>
                                               <numIndex index="1" type="array">
                                                   <label>foo2</label>
                                                   <value></value>
                                               </numIndex>
                                               <numIndex index="2" type="array">
                                                   <label>foo3</label>
                                                   <value></value>
                                               </numIndex>
                                               <numIndex index="3" type="array">
                                                   <label>foo4</label>
                                                   <value></value>
                                               </numIndex>
                                           </items>
                                           <cols>3</cols>
                                       </config>
                                    </checkbox_2>
                                </el>
                            </ROOT>
                        </T3DataStructure>
                    ',
                ],
            ],
        ],

        'flex_1' => [
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
                                        <type>array</type>
                                        <el>
                                            <input_1>
                                                <label>input_1</label>
                                                <config>
                                                    <type>input</type>
                                                </config>
                                            </input_1>
                                        </el>
                                    </ROOT>
                                </sSheetdescription_1>
                                <sSheetdescription_2>
                                    <ROOT>
                                        <sheetTitle>sheet description 2</sheetTitle>
                                        <sheetDescription>
                                            foo
                                        </sheetDescription>
                                        <sheetShortDescr>
                                            bar
                                       </sheetShortDescr>
                                        <type>array</type>
                                        <el>
                                            <input_2>
                                                <label>input_2</label>
                                                <config>
                                                    <type>input</type>
                                                </config>
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
            'label' => 'flex_2 section container',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => '
                        <T3DataStructure>
                            <sheets>
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
                                                            <input_1>
                                                                <label>input_1 description</label>
                                                                <description>field description</description>
                                                                <config>
                                                                    <type>input</type>
                                                                </config>
                                                            </input_1>
                                                            <color_1>
                                                                <label>color_1</label>
                                                                <config>
                                                                    <type>color</type>
                                                                    <size>10</size>
                                                                </config>
                                                            </color_1>
                                                        </el>
                                                    </container_1>
                                                    <container_2>
                                                        <type>array</type>
                                                        <title>container_2</title>
                                                        <el>
                                                            <text_1>
                                                                <label>text_1 default "foo"</label>
                                                                <config>
                                                                    <type>text</type>
                                                                    <default>foo</default>
                                                                </config>
                                                            </text_1>
                                                        </el>
                                                    </container_2>
                                                </el>
                                            </section_1>
                                        </el>
                                    </ROOT>
                                </sSection>
                                <sSection2>
                                    <ROOT>
                                        <sheetTitle>section2</sheetTitle>
                                        <type>array</type>
                                        <el>
                                            <section_2>
                                                <title>section_2</title>
                                                <type>array</type>
                                                <section>1</section>
                                                <el>
                                                    <container_1>
                                                        <type>array</type>
                                                        <title>container_1</title>
                                                        <el>
                                                            <input_1>
                                                                <label>input_1 description</label>
                                                                <description>field description</description>
                                                                <config>
                                                                    <type>input</type>
                                                                </config>
                                                            </input_1>
                                                            <color_1>
                                                                <label>color_1</label>
                                                                <config>
                                                                    <type>color</type>
                                                                    <size>10</size>
                                                                </config>
                                                            </color_1>
                                                        </el>
                                                    </container_1>
                                                    <container_2>
                                                        <type>array</type>
                                                        <title>container_2</title>
                                                        <el>
                                                            <text_1>
                                                                <label>text_1 default "foo"</label>
                                                                <config>
                                                                    <type>text</type>
                                                                    <default>foo</default>
                                                                </config>
                                                            </text_1>
                                                        </el>
                                                    </container_2>
                                                </el>
                                            </section_2>
                                        </el>
                                    </ROOT>
                                </sSection2>
                            </sheets>
                        </T3DataStructure>
                    ',
                ],
            ],
        ],

        'flex_3' => [
            'label' => 'flex_3 inline',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => '
                        <T3DataStructure>
                            <sheets>
                                <sInline>
                                    <ROOT>
                                        <sheetTitle>inline</sheetTitle>
                                        <type>array</type>
                                        <el>
                                            <inline_1>
                                                <label>inline_1</label>
                                                <config>
                                                    <type>inline</type>
                                                    <foreign_table>tx_styleguide_flex_flex_3_inline_1_child</foreign_table>
                                                    <foreign_field>parentid</foreign_field>
                                                    <foreign_table_field>parenttable</foreign_table_field>
                                                    <overrideChildTca>
                                                        <columns>
                                                            <file_1>
                                                                <description>Overridden description via overrideChildTca in flex</description>
                                                                <config>
                                                                    <allowed>common-image-types</allowed>
                                                                </config>
                                                            </file_1>
                                                        </columns>
                                                    </overrideChildTca>
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

        'flex_6' => [
            'label' => 'flex_6 file',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => '
                        <T3DataStructure>
                            <sheets>
                                <sInline>
                                    <ROOT>
                                        <sheetTitle>file</sheetTitle>
                                        <type>array</type>
                                        <el>
                                            <file_1>
                                                <label>file_1</label>
                                                <config>
                                                    <type>file</type>
                                                    <allowed>common-media-types</allowed>
                                                    <maxitems>5</maxitems>
                                                </config>
                                            </file_1>
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
                --div--;complex,
                    flex_file_2,
                --div--;no sheets,
                    flex_5,
                --div--;sheet description,
                    flex_1,
                --div--;section container,
                    flex_2,
                --div--;inline,
                    flex_3,
                --div--;file,
                    flex_6,
            ',
        ],
    ],

];

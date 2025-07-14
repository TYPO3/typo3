<?php

return [
    'ctrl' => [
        'title' => 'Form engine elements - rte',
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
        'rte_1' => [
            'label' => 'rte_1 description',
            'description' => 'field description',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
            ],
        ],
        'rte_2' => [
            'label' => 'rte_2 default value, acts as placeholder for rte_7',
            'config' => [
                'type' => 'text',
                'default' => '<p><strong>rte_2</strong> default.</p>',
                'enableRichtext' => true,
            ],
        ],
        'rte_3' => [
            'label' => 'rte_3 nullable=true, richtextConfiguration=RTE-Styleguide',
            'config' => [
                'type' => 'text',
                'nullable' => true,
                'enableRichtext' => true,
                'richtextConfiguration' => 'RTE-Styleguide',
            ],
        ],
        'rte_4' => [
            'label' => 'rte_4 richtextConfiguration=minimal',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'richtextConfiguration' => 'minimal',
            ],
        ],
        'rte_5' => [
            'label' => 'rte_5 richtextConfiguration=full',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'richtextConfiguration' => 'full',
            ],
        ],
        'rte_6' => [
            'label' => 'rte_6 with placeholder, no default, null handling',
            'config' => [
                'type' => 'text',
                'default' => null,
                'enableRichtext' => true,
                'mode' => 'useOrOverridePlaceholder',
                'nullable' => true,
                'placeholder' => '<p>My placeholder text - it is long and will be shorted, and <strong>HTML</strong> will be stripped. Default is empty.</p>',
            ],
        ],
        'rte_7' => [
            'label' => 'rte_7 with placeholder from rte_2, no default, no userOrOverridePlaceholder',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'mode' => 'useOrOverridePlaceholder',
                'placeholder' => '__row|rte_2',
            ],
        ],
        'rte_8' => [
            'label' => 'rte_8 with placeholder, default value, null handling',
            'config' => [
                'type' => 'text',
                'default' => '<p>Actual <strong>HTML</strong>.</p>',
                'enableRichtext' => true,
                'mode' => 'useOrOverridePlaceholder',
                'nullable' => true,
                // Placeholder will only appear once the "default" is cleared from the CKEditor textarea!
                'placeholder' => '<p>My placeholder text - it is long and will be shorted, and <strong>HTML</strong> will be stripped. Default is set.</p>',
            ],
        ],
        'rte_9' => [
            'label' => 'rte_9 with short placeholder, no default, null handling',
            'config' => [
                'type' => 'text',
                'default' => null,
                'enableRichtext' => true,
                'mode' => 'useOrOverridePlaceholder',
                'nullable' => true,
                'placeholder' => 'Short placeholder',
            ],
        ],
        'rte_10' => [
            'label' => 'rte_10 with short placeholder, short default, null handling',
            'config' => [
                'type' => 'text',
                'default' => 'Short default',
                'enableRichtext' => true,
                'mode' => 'useOrOverridePlaceholder',
                'nullable' => true,
                'placeholder' => 'Short placeholder',
            ],
        ],

        'rte_11' => [
            'label' => 'rte_11 with placeholder, no default, null handling, no useOrOverridePlaceholder',
            'config' => [
                'type' => 'text',
                'default' => null,
                'enableRichtext' => true,
                'nullable' => true,
                'placeholder' => '<p>My placeholder text - it is long and will be shorted, and <strong>HTML</strong> will be stripped. Default is empty.</p>',
            ],
        ],
        'rte_12' => [
            'label' => 'rte_12 with placeholder from rte_2, no default, no useOrOverridePlaceholder',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'placeholder' => '__row|rte_2',
            ],
        ],
        'rte_13' => [
            'label' => 'rte_13 with placeholder, default value, null handling, no useOrOverridePlaceholder',
            'config' => [
                'type' => 'text',
                'default' => '<p>Actual <strong>HTML</strong>.</p>',
                'enableRichtext' => true,
                'nullable' => true,
                // Placeholder will only appear once the "default" is cleared from the CKEditor textarea!
                'placeholder' => '<p>My placeholder text - it is long and will be shorted, and <strong>HTML</strong> will be stripped. Default is set.</p>',
            ],
        ],
        'rte_14' => [
            'label' => 'rte_14 with short placeholder, no default, null handling, no useOrOverridePlaceholder',
            'config' => [
                'type' => 'text',
                'default' => null,
                'enableRichtext' => true,
                'nullable' => true,
                'placeholder' => 'Short placeholder',
            ],
        ],
        'rte_15' => [
            'label' => 'rte_15 with short placeholder, short default, null handling, no useOrOverridePlaceholder',
            'config' => [
                'type' => 'text',
                'default' => 'Short default',
                'enableRichtext' => true,
                'nullable' => true,
                'placeholder' => 'Short placeholder',
            ],
        ],

        'rte_inline_1' => [
            'label' => 'rte_inline_1',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_styleguide_elements_rte_inline_1_child',
                'foreign_field' => 'parentid',
                'foreign_table_field' => 'parenttable',
            ],
        ],

        'rte_flex_1' => [
            'label' => 'rte_flex_1',
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
                                            <rte_1>
                                                <label>rte_1</label>
                                                <config>
                                                    <type>text</type>
                                                    <enableRichtext>1</enableRichtext>
                                                </config>
                                            </rte_1>
                                            <rte.2>
                                                <label>rte.2</label>
                                                <config>
                                                    <type>text</type>
                                                    <enableRichtext>1</enableRichtext>
                                                </config>
                                            </rte.2>
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
                                                            <rte_1>
                                                                <label>rte_1</label>
                                                                <config>
                                                                    <type>text</type>
                                                                    <enableRichtext>1</enableRichtext>
                                                                </config>
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
                                        <sheetTitle>inline</sheetTitle>
                                        <type>array</type>
                                        <el>
                                            <inline_1>
                                                <label>inline_1</label>
                                                <config>
                                                    <type>inline</type>
                                                    <foreign_table>tx_styleguide_elements_rte_flex_1_inline_1_child</foreign_table>
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

        'input_palette_1' => [
            'label' => 'input_palette_1',
            'config' => [
                'type' => 'input',
            ],
        ],

        'rte_palette_1' => [
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
                --div--;rte with useOrOverridePlaceholder,
                    rte_6, rte_7, rte_8, rte_9, rte_10,
                --div--;rte without useOrOverridePlaceholder,
                    rte_11, rte_12, rte_13, rte_14, rte_15,
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

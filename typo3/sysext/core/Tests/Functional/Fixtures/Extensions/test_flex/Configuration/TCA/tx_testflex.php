<?php

return [
    'ctrl' => [
        'title' => 'DataHandler Testing test_flex',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'iconfile' => 'EXT:test_flex/Resources/Public/Icons/Extension.svg',
        'versioningWS' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'translationSource' => 'l10n_source',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],

    'columns' => [
        'flex_1' => [
            'label' => 'flex_1 file',
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
                                                </config>
                                            </file_1>
                                            <irre_csv_1>
                                                <label>irre_csv_1</label>
                                                <config>
                                                    <type>inline</type>
                                                    <foreign_table>tx_testirrecsv_hotel</foreign_table>
                                                </config>
                                            </irre_csv_1>
                                            <irre_foreignfield_1>
                                                <label>irre_foreignfield_1</label>
                                                <config>
                                                    <type>inline</type>
                                                    <foreign_table>tx_testirreforeignfield_hotel</foreign_table>
                                                    <foreign_field>parentid</foreign_field>
                                                    <foreign_table_field>parenttable</foreign_table_field>
                                                    <foreign_match_fields type="array">
                                                        <parentidentifier>flex_1.irre_foreignfield_1</parentidentifier>
                                                    </foreign_match_fields>
                                                </config>
                                            </irre_foreignfield_1>
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
                --div--;flex,
                    flex_1,
                --div--;meta,
                    sys_language_uid, l10n_parent, l10n_source,
            ',
        ],
    ],

];

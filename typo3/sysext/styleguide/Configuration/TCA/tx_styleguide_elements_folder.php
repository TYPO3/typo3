<?php

return [
    'ctrl' => [
        'title' => 'Form engine elements - folder',
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
        'folder_1' => [
            'label' => 'folder_1 description',
            'description' => 'field description',
            'config' => [
                'type' => 'folder',
            ],
        ],
        'folder_2' => [
            'label' => 'folder_2 hideMoveIcons=true',
            'description' => 'field description',
            'config' => [
                'type' => 'folder',
                'hideMoveIcons' => true,
            ],
        ],
        'folder_3' => [
            'label' => 'folder_3 relationship=manyToOne',
            'description' => 'field description',
            'config' => [
                'type' => 'folder',
                'relationship' => 'manyToOne',
                'size' => 1,
            ],
        ],

        'flex_1' => [
            'label' => 'flex_1',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => '
                        <T3DataStructure>
                            <sheets>

                                <sDb>
                                    <ROOT>
                                        <type>array</type>
                                        <sheetTitle>folder</sheetTitle>
                                        <el>
                                            <folder_1>
                                                <label>folder_1 description</label>
                                                <description>field description</description>
                                                <config>
                                                    <type>folder</type>
                                                </config>
                                            </folder_1>
                                        </el>
                                    </ROOT>
                                </sDb>

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
                --div--;type=folder,
                    folder_1, folder_2, folder_3,
                --div--;in flex,
                    flex_1,
                --div--;meta,
                disable, sys_language_uid, l10n_parent, l10n_source,
            ',
        ],
    ],

];

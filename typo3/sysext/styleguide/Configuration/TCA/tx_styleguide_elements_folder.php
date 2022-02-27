<?php

return [
    'ctrl' => [
        'title' => 'Form engine elements - folder',
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
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        '',
                        0,
                    ],
                ],
                'foreign_table' => 'tx_styleguide_elements_folder',
                'foreign_table_where' => 'AND {#tx_styleguide_elements_folder}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_elements_folder}.{#sys_language_uid} IN (-1,0)',
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
                'foreign_table' => 'tx_styleguide_elements_folder',
                'foreign_table_where' => 'AND {#tx_styleguide_elements_folder}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_elements_folder}.{#uid}!=###THIS_UID###',
                'default' => 0,
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => '',
            ],
        ],

        'folder_1' => [
            'exclude' => 1,
            'label' => 'folder_1 description',
            'description' => 'field description',
            'config' => [
                'type' => 'folder',
            ],
        ],

        'folder_2' => [
            'exclude' => 1,
            'label' => 'folder_2 hideMoveIcons=true',
            'description' => 'field description',
            'config' => [
                'type' => 'folder',
                'hideMoveIcons' => true,
            ],
        ],

        'flex_1' => [
            'exclude' => 1,
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
                                        <TCEforms>
                                            <sheetTitle>folder</sheetTitle>
                                        </TCEforms>
                                        <el>
                                            <folder_1>
                                                <TCEforms>
                                                    <label>folder_1 description</label>
                                                    <description>field description</description>
                                                    <config>
                                                        <type>folder</type>
                                                    </config>
                                                </TCEforms>
                                            </folder_1>
                                        </el>
                                    </ROOT>
                                </sDb>

                                <sSection>
                                    <ROOT>
                                        <type>array</type>
                                        <TCEforms>
                                            <sheetTitle>section</sheetTitle>
                                        </TCEforms>
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
                                                            <folder_1>
                                                                <TCEforms>
                                                                    <label>folder_1</label>
                                                                    <config>
                                                                        <type>folder</type>
                                                                        <size>5</size>
                                                                    </config>
                                                                </TCEforms>
                                                            </folder_1>
                                                        </el>
                                                    </container_1>
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

    ],

    'types' => [
        '0' => [
            'showitem' => '
                --div--;type=folder,
                    folder_1, folder_2,
                --div--;in flex,
                    flex_1,
                --div--;meta,
                disable, sys_language_uid, l10n_parent, l10n_source,
            ',
        ],
    ],

];

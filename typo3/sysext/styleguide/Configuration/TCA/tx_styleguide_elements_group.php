<?php

return [
    'ctrl' => [
        'title' => 'Form engine elements - group',
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
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        '',
                        0
                    ]
                ],
                'foreign_table' => 'tx_styleguide_elements_group',
                'foreign_table_where' => 'AND {#tx_styleguide_elements_group}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_elements_group}.{#sys_language_uid} IN (-1,0)',
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
                'foreign_table' => 'tx_styleguide_elements_group',
                'foreign_table_where' => 'AND {#tx_styleguide_elements_group}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_elements_group}.{#uid}!=###THIS_UID###',
                'default' => 0
            ]
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => ''
            ]
        ],

        'group_db_1' => [
            'exclude' => 1,
            'label' => 'group_db_1 allowed=be_users,be_groups description',
            'description' => 'field description',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'be_users,be_groups',
                'fieldControl' => [
                    'editPopup' => [
                        'disabled' => false,
                    ],
                    'addRecord' => [
                        'disabled' => false,
                    ],
                    'listModule' => [
                        'disabled' => false,
                    ],
                ],
            ],
        ],
        'group_db_2' => [
            'exclude' => 1,
            'label' => 'group_db_2 allowed=be_users,be_groups, recordsOverview disabled',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'be_users,be_groups',
                'fieldWizard' => [
                    'recordsOverview' => [
                        'disabled' => true,
                    ],
                ],
            ],
        ],
        'group_db_9' => [
            'exclude' => 1,
            'label' => 'group_db_9 allowed=be_users,be_groups, disable tableList',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'be_users,be_groups',
                'fieldWizard' => [
                    'tableList' => [
                        'disabled' => true,
                    ],
                ],
            ],
        ],
        'group_db_3' => [
            'exclude' => 1,
            'label' => 'group_db_3 allowed=tx_styleguide_staticdata, disabled elementBrowser',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_styleguide_staticdata',
                'fieldControl' => [
                    'elementBrowser' => [
                        'disabled' => true,
                    ],
                ],
            ],
        ],
        'group_db_8' => [
            'exclude' => 1,
            'label' => 'group_db_8 allowed=tx_styleguide_staticdata, multiple',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'hideSuggest' => false,
                'allowed' => 'tx_styleguide_staticdata',
                'multiple' => true,
            ],
        ],
        'group_db_11' => [
            'exclude' => 1,
            'label' => 'group_db_11 hideSuggest=true allowed=tx_styleguide_staticdata, multiple',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'hideSuggest' => true,
                'allowed' => 'tx_styleguide_staticdata',
                'multiple' => true,
            ],
        ],
        'group_db_4' => [
            'exclude' => 1,
            'label' => 'group_db_4 allowed=tx_styleguide_staticdata, size=1',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_styleguide_staticdata',
                'size' => 1,
                'maxitems' => 1,
            ],
        ],
        'group_db_5' => [
            'exclude' => 1,
            'label' => 'group_db_5 readOnly=1 description',
            'description' => 'field description',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'be_users',
                'readOnly' => 1,
            ]
        ],
        'group_db_7' => [
            'exclude' => 1,
            'label' => 'group_db_7 allowed=be_users, prepend_tname=false',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'be_users',
            ],
        ],
        'group_db_10' => [
            'exclude' => 1,
            'label' => 'group_db_10 allowed=pages size=1',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages',
                'maxitems' => 1,
                'minitems' => 0,
                'size' => 1,
                'suggestOptions' => [
                    'default' => [
                        'additionalSearchFields' => 'nav_title, alias, url',
                        'addWhere' => 'AND pages.doktype = 1'
                    ]
                ]
            ]
        ],

        'group_folder_1' => [
            'exclude' => 1,
            'label' => 'group_folder_1 desription',
            'description' => 'field description',
            'config' => [
                'type' => 'group',
                'internal_type' => 'folder',
            ],
        ],

        'group_requestUpdate_1' => [
            'exclude' => 1,
            'label' => 'group_requestUpdate_1',
            'onChange' => 'reload',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'be_users,be_groups',
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
                                            <sheetTitle>internal_type=db</sheetTitle>
                                        </TCEforms>
                                        <el>
                                            <group_db_1>
                                                <TCEforms>
                                                    <label>group_db_1 description</label>
                                                    <description>field description</description>
                                                    <config>
                                                        <type>group</type>
                                                        <internal_type>db</internal_type>
                                                        <allowed>tx_styleguide_staticdata</allowed>
                                                    </config>
                                                </TCEforms>
                                            </group_db_1>
                                            <group_db_2>
                                                <TCEforms>
                                                    <label>group_db_2 suggest, order by uid DESC</label>
                                                    <config>
                                                        <type>group</type>
                                                        <internal_type>db</internal_type>
                                                        <allowed>tx_styleguide_staticdata</allowed>
                                                        <suggestOptions>
                                                            <default>
                                                                <orderBy>uid DESC</orderBy>
                                                            </default>
                                                        </suggestOptions>
                                                        <fieldControl>
                                                            <editPopup>
                                                                <renderType>editPopup</renderType>
                                                                <disabled>0</disabled>
                                                            </editPopup>
                                                            <addRecord>
                                                                <renderType>addRecord</renderType>
                                                                <disabled>0</disabled>
                                                                <options>
                                                                    <setValue>prepend</setValue>
                                                                </options>
                                                            </addRecord>
                                                            <listModule>
                                                                <renderType>listModule</renderType>
                                                                <disabled>0</disabled>
                                                            </listModule>
                                                        </fieldControl>
                                                    </config>
                                                </TCEforms>
                                            </group_db_2>
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
                                                            <group_db_1>
                                                                <TCEforms>
                                                                    <label>group_db_1</label>
                                                                    <config>
                                                                        <type>group</type>
                                                                        <internal_type>db</internal_type>
                                                                        <allowed>pages</allowed>
                                                                        <size>5</size>
                                                                    </config>
                                                                </TCEforms>
                                                            </group_db_1>
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
                --div--;internal_type=db,
                    group_db_1, group_db_2, group_db_9, group_db_3, group_db_8, group_db_11, group_db_4, group_db_5, group_db_7, group_db_10,
                --div--;internal_type=folder,
                    group_folder_1,
                --div--;in flex,
                    flex_1,
                --div--;requestUpdate,
                    group_requestUpdate_1,
                --div--;meta,
                disable, sys_language_uid, l10n_parent, l10n_source,
            ',
        ],
    ],

];

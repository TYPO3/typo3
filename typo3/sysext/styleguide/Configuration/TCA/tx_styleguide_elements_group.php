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
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
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
                'foreign_table_where' => 'AND tx_styleguide_elements_group.pid=###CURRENT_PID### AND tx_styleguide_elements_group.sys_language_uid IN (-1,0)',
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
                'foreign_table_where' => 'AND tx_styleguide_elements_group.pid=###CURRENT_PID### AND tx_styleguide_elements_group.uid!=###THIS_UID###',
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
            'label' => 'group_db_1 allowed=be_users,be_groups',
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
            'label' => 'group_db_3 allowed=tx_styleguide_staticdata, disable elementBrowser',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_styleguide_staticdata',
                'fieldControl' => [
                    'elementBrowser' => [
                        'disable' => true,
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
            'label' => 'group_db_5 readOnly=1',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'be_users',
                'readOnly' => 1,
            ]
        ],
        'group_db_6' => [
            // @todo: when and why is this useful?
            // @todo: something is totally wrong here, the structure within sys_file_reference ends up being
            // @todo: basically swapped with "foreign" and "local" fields!
            'exclude' => 1,
            'label' => 'group_db_6 FAL relation',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'sys_file',
                'MM' => 'sys_file_reference',
                'MM_match_fields' => [
                    'fieldname' => 'image_fal_group',
                ],
                'prepend_tname' => true,
                'appearance' => [
                    'elementBrowserAllowed' => 'jpg, png, gif',
                    'elementBrowserType' => 'file',
                ],
                'max_size' => 2000,
                'size' => '3',
                'autoSizeMax' => 40,
            ],
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


        'group_file_1' => [
            'exclude' => 1,
            'label' => 'group_file_1',
            'config' => [
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => 'jpg, jpeg, png, gif',
                'disallowed' => 'ai',
                'size' => 3,
                'uploadfolder' => 'uploads/tx_styleguide/',
                'max_size' => 2000,
            ],
        ],
        'group_file_2' => [
            'exclude' => 1,
            'label' => 'group_file_2 hideDeleteIcon, fileUpload disabled',
            'config' => [
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => 'jpg, jpeg, png, gif',
                'size' => 3,
                'uploadfolder' => 'uploads/tx_styleguide/',
                'hideDeleteIcon' => true,
                'fieldWizard' => [
                    'fileUpload' => [
                        'disabled' => true,
                    ],
                ],
            ],
        ],
        'group_file_3' => [
            'exclude' => 1,
            'label' => 'group_file_3 size=1',
            'config' => [
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => 'jpg, jpeg, png, gif',
                'size' => 1,
                'uploadfolder' => 'uploads/tx_styleguide/',
            ],
        ],


        'group_folder_1' => [
            'exclude' => 1,
            'label' => 'group_folder_1',
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
                                                    <label>group_db_1</label>
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
                                                    </config>
                                                </TCEforms>
                                            </group_db_2>
                                        </el>
                                    </ROOT>
                                </sDb>

                                <sFile>
                                    <ROOT>
                                        <type>array</type>
                                        <TCEforms>
                                            <sheetTitle>internal_type=file</sheetTitle>
                                        </TCEforms>
                                        <el>
                                            <group_file_1>
                                                <TCEforms>
                                                    <label>group_file_1</label>
                                                    <config>
                                                        <type>group</type>
                                                        <internal_type>file</internal_type>
                                                        <allowed>jpg, jpeg, png, gif</allowed>
                                                        <size>3</size>
                                                        <uploadfolder>uploads/tx_styleguide/</uploadfolder>
                                                    </config>
                                                </TCEforms>
                                            </group_file_1>
                                        </el>
                                    </ROOT>
                                </sFile>

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
                    group_db_1, group_db_2, group_db_9, group_db_3, group_db_8, group_db_4, group_db_5, group_db_6, group_db_7,
                --div--;internal_type=file,
                    group_file_1, group_file_2, group_file_3,
                --div--;internal_type=folder,
                    group_folder_1,
                --div--;in flex,
                    flex_1,
                --div--;requestUpdate,
                    group_requestUpdate_1,
                --div--;meta,
                disable, starttime, endtime, sys_language_uid, l10n_parent, l10n_source,
            ',
        ],
    ],


];

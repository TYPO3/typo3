<?php

return [
    'ctrl' => [
        'title' => 'Form engine elements - group',
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
        'group_db_1' => [
            'label' => 'group_db_1 allowed=be_users,be_groups description',
            'description' => 'field description',
            'config' => [
                'type' => 'group',
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
            'label' => 'group_db_2 allowed=be_users,be_groups, recordsOverview disabled',
            'config' => [
                'type' => 'group',
                'allowed' => 'be_users,be_groups',
                'fieldWizard' => [
                    'recordsOverview' => [
                        'disabled' => true,
                    ],
                ],
            ],
        ],
        'group_db_9' => [
            'label' => 'group_db_9 allowed=be_users,be_groups, disable tableList',
            'config' => [
                'type' => 'group',
                'allowed' => 'be_users,be_groups',
                'fieldWizard' => [
                    'tableList' => [
                        'disabled' => true,
                    ],
                ],
            ],
        ],
        'group_db_3' => [
            'label' => 'group_db_3 allowed=tx_styleguide_staticdata, disabled elementBrowser',
            'config' => [
                'type' => 'group',
                'allowed' => 'tx_styleguide_staticdata',
                'fieldControl' => [
                    'elementBrowser' => [
                        'disabled' => true,
                    ],
                ],
            ],
        ],
        'group_db_8' => [
            'label' => 'group_db_8 allowed=tx_styleguide_staticdata, multiple',
            'config' => [
                'type' => 'group',
                'hideSuggest' => false,
                'allowed' => 'tx_styleguide_staticdata',
                'multiple' => true,
            ],
        ],
        'group_db_11' => [
            'label' => 'group_db_11 hideSuggest=true allowed=tx_styleguide_staticdata, multiple, autoSizeMax=10',
            'config' => [
                'type' => 'group',
                'hideSuggest' => true,
                'allowed' => 'tx_styleguide_staticdata',
                'multiple' => true,
                'autoSizeMax' => 10,
            ],
        ],
        'group_db_4' => [
            'label' => 'group_db_4 allowed=tx_styleguide_staticdata, size=1',
            'config' => [
                'type' => 'group',
                'allowed' => 'tx_styleguide_staticdata',
                'size' => 1,
                'relationship' => 'manyToOne',
            ],
        ],
        'group_db_5' => [
            'label' => 'group_db_5 readOnly=1 description',
            'description' => 'field description',
            'config' => [
                'type' => 'group',
                'allowed' => 'be_users',
                'readOnly' => 1,
            ],
        ],
        'group_db_7' => [
            'label' => 'group_db_7 allowed=be_users, prepend_tname=false',
            'config' => [
                'type' => 'group',
                'allowed' => 'be_users',
            ],
        ],
        'group_db_10' => [
            'label' => 'group_db_10 allowed=pages size=1',
            'config' => [
                'type' => 'group',
                'allowed' => 'pages',
                'relationship' => 'manyToOne',
                'minitems' => 0,
                'size' => 1,
                'suggestOptions' => [
                    'default' => [
                        'additionalSearchFields' => 'nav_title, url',
                        'addWhere' => 'AND pages.doktype = 1',
                    ],
                ],
            ],
        ],
        'group_db_12' => [
            'label' => 'group_db_12 allowed=*',
            'config' => [
                'type' => 'group',
                'allowed' => '*',
            ],
        ],

        'group_requestUpdate_1' => [
            'label' => 'group_requestUpdate_1',
            'onChange' => 'reload',
            'config' => [
                'type' => 'group',
                'allowed' => 'be_users,be_groups',
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
                                        <sheetTitle>group</sheetTitle>
                                        <el>
                                            <group_db_1>
                                                <label>group_db_1 description</label>
                                                <description>field description</description>
                                                <config>
                                                    <type>group</type>
                                                    <allowed>tx_styleguide_staticdata</allowed>
                                                </config>
                                            </group_db_1>
                                            <group_db_2>
                                                <label>group_db_2 suggest, order by uid DESC</label>
                                                <config>
                                                    <type>group</type>
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
                                            </group_db_2>
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
                --div--;type=group,
                    group_db_1, group_db_2, group_db_9, group_db_3, group_db_8, group_db_11, group_db_4, group_db_5, group_db_7, group_db_10, group_db_12,
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

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
        'origUid' => 't3_origuid',
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

        'hidden' => [
            'config' => [
                'type' => 'check',
                'items' => [
                    ['Disable'],
                ],
            ],
        ],
        'sys_language_uid' => [
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
                'foreign_table' => 'tx_styleguide_elements_group',
                'foreign_table_where' => 'AND {#tx_styleguide_elements_group}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_elements_group}.{#sys_language_uid} IN (-1,0)',
                'default' => 0,
            ],
        ],
        'l10n_source' => [
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
                'foreign_table' => 'tx_styleguide_elements_group',
                'foreign_table_where' => 'AND {#tx_styleguide_elements_group}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_elements_group}.{#uid}!=###THIS_UID###',
                'default' => 0,
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => '',
            ],
        ],

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
                'maxitems' => 1,
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
                'maxitems' => 1,
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

                                <sSection>
                                    <ROOT>
                                        <type>array</type>
                                        <sheetTitle>section</sheetTitle>
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
                                                                <label>group_db_1</label>
                                                                <config>
                                                                    <type>group</type>
                                                                    <allowed>pages</allowed>
                                                                    <size>5</size>
                                                                </config>
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
                --div--;type=group,
                    group_db_1, group_db_2, group_db_9, group_db_3, group_db_8, group_db_11, group_db_4, group_db_5, group_db_7, group_db_10,
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

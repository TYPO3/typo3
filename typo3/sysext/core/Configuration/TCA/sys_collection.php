<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_collection',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'descriptionColumn' => 'description',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'default_sortby' => 'crdate',
        'delete' => 'deleted',
        'type' => 'type',
        'rootLevel' => -1,
        'searchFields' => 'title,description',
        'typeicon_column' => 'type',
        'typeicon_classes' => [
            'default' => 'apps-clipboard-list',
            'static' => 'apps-clipboard-list',
            'filter' => 'actions-system-tree-search-open'
        ],
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'fe_group' => 'fe_group'
        ],
    ],
    'interface' => [
        'showRecordFieldList' => 'title, description, table_name, items'
    ],
    'columns' => [
        't3ver_label' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.versionLabel',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 30
            ]
        ],
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'items' => [
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.allLanguages', -1],
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.default_value', 0]
                ],
                'default' => 0,
                'fieldWizard' => [
                    'selectIcons' => [
                        'disabled' => false,
                    ],
                ],
            ]
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0]
                ],
                'foreign_table' => 'sys_file_collection',
                'foreign_table_where' => 'AND sys_file_collection.pid=###CURRENT_PID### AND sys_file_collection.sys_language_uid IN (-1,0)'
            ]
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => ''
            ]
        ],
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'default' => 0
            ]
        ],
        'starttime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'date,int',
                'default' => 0,
            ]
        ],
        'endtime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'date,int',
                'default' => 0,
                'range' => [
                    'upper' => mktime(0, 0, 0, 1, 1, 2038),
                ]
            ]
        ],
        'fe_group' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.fe_group',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0],
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.hide_at_login', -1],
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.any_login', -2],
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.usergroups', '--div--']
                ],
                'foreign_table' => 'fe_groups'
            ]
        ],
        'table_name' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_collection.table_name',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'special' => 'tables'
            ]
        ],
        'items' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_collection.items',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'prepend_tname' => true,
                'allowed' => '*',
                'MM' => 'sys_collection_entries',
                'MM_hasUidField' => true,
                'multiple' => true,
                'size' => 5
            ]
        ],
        'title' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_collection.title',
            'config' => [
                'type' => 'input',
                'size' => 60,
                'eval' => 'required'
            ]
        ],
        'description' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_collection.description',
            'config' => [
                'type' => 'text',
                'cols' => 60,
                'rows' => 5
            ]
        ],
        'type' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_collection.type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_collection.type.static', 'static']
                ],
                'default' => 'static'
            ]
        ]
    ],
    'types' => [
        'static' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    type,title,table_name,items,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    hidden,--palette--;;timeRestriction,fe_group,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    description,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
        ],
    ],
    'palettes' => [
        'timeRestriction' => ['showitem' => 'starttime, endtime'],
        'language' => ['showitem' => 'sys_language_uid, l10n_parent'],
    ]
];

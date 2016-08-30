<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:lang/locallang_tca.xlf:sys_collection',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'default_sortby' => 'ORDER BY crdate',
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
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.versionLabel',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'max' => '30'
            ]
        ],
        'sys_language_uid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'items' => [
                    ['LLL:EXT:lang/locallang_general.xlf:LGL.allLanguages', -1],
                    ['LLL:EXT:lang/locallang_general.xlf:LGL.default_value', 0]
                ],
                'default' => 0,
                'showIconTable' => true,
            ]
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.l18n_parent',
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
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'default' => '0'
            ]
        ],
        'starttime' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'input',
                'size' => '8',
                'max' => '20',
                'eval' => 'date',
                'default' => '0',
            ]
        ],
        'endtime' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'input',
                'size' => '8',
                'max' => '20',
                'eval' => 'date',
                'default' => '0',
                'range' => [
                    'upper' => mktime(0, 0, 0, 1, 1, 2038),
                ]
            ]
        ],
        'fe_group' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.fe_group',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0],
                    ['LLL:EXT:lang/locallang_general.xlf:LGL.hide_at_login', -1],
                    ['LLL:EXT:lang/locallang_general.xlf:LGL.any_login', -2],
                    ['LLL:EXT:lang/locallang_general.xlf:LGL.usergroups', '--div--']
                ],
                'foreign_table' => 'fe_groups'
            ]
        ],
        'table_name' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_collection.table_name',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'special' => 'tables'
            ]
        ],
        'items' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_collection.items',
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
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_collection.title',
            'config' => [
                'type' => 'input',
                'size' => '60',
                'eval' => 'required'
            ]
        ],
        'description' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_collection.description',
            'config' => [
                'type' => 'text',
                'cols' => '60',
                'rows' => '5'
            ]
        ],
        'type' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_collection.type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:lang/locallang_tca.xlf:sys_collection.type.static', 'static']
                ],
                'default' => 'static'
            ]
        ]
    ],
    'types' => [
        'static' => [
            'showitem' => 'title, --palette--;;1, type, description,table_name, items',
        ],
    ],
    'palettes' => [
        '1' => ['showitem' => 'starttime, endtime, fe_group, sys_language_uid, l10n_parent, l10n_diffsource, hidden']
    ]
];

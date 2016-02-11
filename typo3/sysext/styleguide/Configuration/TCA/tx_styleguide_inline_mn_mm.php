<?php
return [
    'ctrl' => [
        'title' => 'Form engine - inline mn mm',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
    ],


    'columns' => [


        'sys_language_uid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'items' => [
                    ['LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1],
                    ['LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0]
                ]
            ]
        ],
        'l18n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'tx_styleguide_inline_mn_mm',
                'foreign_table_where' => 'AND tx_styleguide_inline_mn_mm.pid=###CURRENT_PID###
                    AND tx_styleguide_inline_mn_mm.sys_language_uid IN (-1,0)',
            ]
        ],
        'l18n_diffsource' => [
            'config' => [
                'type' => 'passthrough'
            ]
        ],
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
            'config' => [
                'type' => 'check',
                'default' => '0'
            ]
        ],


        'parentid' => [
            'label' => 'parentid',
            'config' => [
                "type" => "select",
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_styleguide_inline_mn',
                "foreign_table_where" => "AND tx_styleguide_inline_mn.pid=###CURRENT_PID### AND tx_styleguide_inline_mn.sys_language_uid='###REC_FIELD_sys_language_uid###'",
                "maxitems" => 1,
                'localizeReferences' => 1,
            ]
        ],
        'childid' => [
            'label' => 'childid',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_styleguide_inline_mn_child',
                "foreign_table_where" => "AND tx_styleguide_inline_mn_child.pid=###CURRENT_PID### AND tx_styleguide_inline_mn_child.sys_language_uid='###REC_FIELD_sys_language_uid###'",
                'maxitems' => 1,
                'localizeReferences' => 1,
            ]
        ],
        'parentsort' => [
            'config' => [
                'type' => 'passthrough',
            ]
        ],
        'childsort' => [
            'config' => [
                'type' => 'passthrough',
            ]
        ],
        'check_1' => [
            'label' => 'check_1',
            'config' => [
                'type' => 'check',
            ],
        ],
    ],


    'types' => [
        '0' => [
            'showitem' => '
                --div--;General, title, parentid, childid, check_1,
                --div--;Visibility, sys_language_uid, l18n_parent, l18n_diffsource, hidden, hotelsort, branchsort'
        ]
    ],


];

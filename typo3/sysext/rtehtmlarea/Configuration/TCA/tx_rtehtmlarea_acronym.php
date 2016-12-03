<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_db.xlf:tx_rtehtmlarea_acronym',
        'label' => 'term',
        'default_sortby' => 'ORDER BY term',
        'sortby' => 'sorting',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime'
        ],
        'typeicon_classes' => [
            'default' => 'mimetypes-x-tx_rtehtmlarea_acronym'
        ]
    ],
    'interface' => [
        'showRecordFieldList' => 'hidden,sys_language_uid,term,acronym'
    ],
    'columns' => [
        'hidden' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'default' => 0
            ]
        ],
        'starttime' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'input',
                'size' => 8,
                'max' => 20,
                'eval' => 'date',
                'default' => 0,
            ]
        ],
        'endtime' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'input',
                'size' => 8,
                'max' => 20,
                'eval' => 'date',
                'default' => 0,
                'range' => [
                    'upper' => mktime(0, 0, 0, 12, 31, 2020),
                    'lower' => mktime(0, 0, 0, date('m') - 1, date('d'), date('Y'))
                ]
            ]
        ],
        'sys_language_uid' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'items' => [
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.allLanguages', '-1'],
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.default_value', '0']
                ],
                'default' => 0,
                'showIconTable' => true,
            ]
        ],
        'type' => [
            'exclude' => true,
            'label' => 'LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_db.xlf:tx_rtehtmlarea_acronym.type',
            'config' => [
                'type' => 'radio',
                'items' => [
                    ['LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_db.xlf:tx_rtehtmlarea_acronym.type.I.1', '2'],
                    ['LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_db.xlf:tx_rtehtmlarea_acronym.type.I.0', '1']
                ],
                'default' => '2'
            ]
        ],
        'term' => [
            'label' => 'LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_db.xlf:tx_rtehtmlarea_acronym.term',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,required'
            ]
        ],
        'acronym' => [
            'label' => 'LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_db.xlf:tx_rtehtmlarea_acronym.acronym',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,required'
            ]
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    type, term, acronym,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    sys_language_uid,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    hidden,--palette--;;1,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
        ],
    ],
    'palettes' => [
        '1' => [
            'showitem' => 'starttime, endtime',
        ],
    ],
];

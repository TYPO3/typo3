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
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'default' => '0'
            ]
        ],
        'starttime' => [
            'exclude' => 0,
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
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'input',
                'size' => '8',
                'max' => '20',
                'eval' => 'date',
                'default' => '0',
                'range' => [
                    'upper' => mktime(0, 0, 0, 12, 31, 2020),
                    'lower' => mktime(0, 0, 0, date('m') - 1, date('d'), date('Y'))
                ]
            ]
        ],
        'sys_language_uid' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'items' => [
                    ['LLL:EXT:lang/locallang_general.xlf:LGL.allLanguages', '-1'],
                    ['LLL:EXT:lang/locallang_general.xlf:LGL.default_value', '0']
                ],
                'default' => 0,
                'showIconTable' => true,
            ]
        ],
        'type' => [
            'exclude' => 1,
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
            'exclude' => 0,
            'label' => 'LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_db.xlf:tx_rtehtmlarea_acronym.term',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim,required'
            ]
        ],
        'acronym' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:rtehtmlarea/Resources/Private/Language/locallang_db.xlf:tx_rtehtmlarea_acronym.acronym',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim,required'
            ]
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => 'hidden, --palette--;;1, sys_language_uid, type, term, acronym',
        ],
    ],
    'palettes' => [
        '1' => [
            'showitem' => 'starttime, endtime',
        ],
    ],
];

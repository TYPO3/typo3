<?php
return [
    'ctrl' => [
        'title' => 'Form engine - inline mn symmetric mm',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'translationSource' => 'l10n_source',
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
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'items' => [
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.allLanguages', -1],
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.default_value', 0]
                ]
            ]
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'exclude' => 1,
            'label' => 'Translation parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'tx_styleguide_inline_mnsymmetric_mm',
                'foreign_table_where' => 'AND tx_styleguide_inline_mnsymmetric_mm.pid=###CURRENT_PID###
                    AND tx_styleguide_inline_mnsymmetric_mm.sys_language_uid IN (-1,0)',
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
                'foreign_table' => 'tx_styleguide_inline_mnsymmetric_mm',
                'foreign_table_where' => 'AND tx_styleguide_inline_mnsymmetric_mm.pid=###CURRENT_PID### AND tx_styleguide_inline_mnsymmetric_mm.uid!=###THIS_UID###',
                'default' => 0
            ]
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough'
            ]
        ],
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'default' => '0'
            ]
        ],


        'hotelid' => [
            'label' => 'hotelid',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_styleguide_inline_mnsymmetric',
                'maxitems' => 1,
                'localizeReferences' => 1,
            ]
        ],
        'branchid' => [
            'label' => 'branchid',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_styleguide_inline_mnsymmetric',
                'maxitems' => 1,
                'localizeReferences' => 1,
            ]
        ],
        'hotelsort' => [
            'config' => [
                'type' => 'passthrough',
            ]
        ],
        'branchsort' => [
            'config' => [
                'type' => 'passthrough',
            ]
        ],
    ],


    'types' => [
        '0' => [
            'showitem' => '
                --div--;General, title, hotelid, branchid,
                --div--;Visibility, sys_language_uid, l18n_parent, l10n_diffsource, hidden, hotelsort, branchsort'
        ]
    ],


];

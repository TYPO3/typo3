<?php

return [
    'ctrl' => [
        'title' => 'Form engine - inline mn symmetric group mm',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
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
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',
            ],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'Translation parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'tx_styleguide_inline_mnsymmetricgroup_mm',
                'foreign_table_where' => 'AND {#tx_styleguide_inline_mnsymmetricgroup_mm}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_inline_mnsymmetricgroup_mm}.{#sys_language_uid} IN (-1,0)',
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
                'foreign_table' => 'tx_styleguide_inline_mnsymmetricgroup_mm',
                'foreign_table_where' => 'AND {#tx_styleguide_inline_mnsymmetricgroup_mm}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_inline_mnsymmetricgroup_mm}.{#uid}!=###THIS_UID###',
                'default' => 0,
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'hidden' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'default' => '0',
            ],
        ],

        'hotelid' => [
            'label' => 'hotelid',
            'config' => [
                'type' => 'group',
                'allowed' => 'tx_styleguide_inline_mnsymmetricgroup',
                'minitems' => 1,
                'maxitems' => 1,
                'size' => 1,
            ],
        ],
        'branchid' => [
            'label' => 'branchid',
            'config' => [
                'type' => 'group',
                'allowed' => 'tx_styleguide_inline_mnsymmetricgroup',
                'minitems' => 1,
                'maxitems' => 1,
                'size' => 1,
            ],
        ],
        'hotelsort' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'branchsort' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ],

    'types' => [
        '0' => [
            'showitem' => '
                --div--;General, title, hotelid, branchid,
                --div--;Visibility, sys_language_uid, l18n_parent, l10n_diffsource, hidden, hotelsort, branchsort',
        ],
    ],

];

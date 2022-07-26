<?php

return [
    'ctrl' => [
        'title' => 'Form engine - inline mn mm',
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
                'foreign_table' => 'tx_styleguide_inline_mn_mm',
                'foreign_table_where' => 'AND {#tx_styleguide_inline_mn_mm}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_inline_mn_mm}.{#sys_language_uid} IN (-1,0)',
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
                'foreign_table' => 'tx_styleguide_inline_mn_mm',
                'foreign_table_where' => 'AND {#tx_styleguide_inline_mn_mm}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_inline_mn_mm}.{#uid}!=###THIS_UID###',
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

        'parentid' => [
            'label' => 'parentid',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_styleguide_inline_mn',
                'foreign_table_where' => "AND {#tx_styleguide_inline_mn}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_inline_mn}.{#sys_language_uid}='###REC_FIELD_sys_language_uid###'",
                'maxitems' => 1,
                'localizeReferences' => 1,
            ],
        ],
        'childid' => [
            'label' => 'childid',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_styleguide_inline_mn_child',
                'foreign_table_where' => "AND {#tx_styleguide_inline_mn_child}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_inline_mn_child}.{#sys_language_uid}='###REC_FIELD_sys_language_uid###'",
                'maxitems' => 1,
                'localizeReferences' => 1,
            ],
        ],
        'parentsort' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'childsort' => [
            'config' => [
                'type' => 'passthrough',
            ],
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
                --div--;General, parentid, childid, check_1,
                --div--;Visibility, sys_language_uid, l18n_parent, l18n_diffsource, hidden, hotelsort, branchsort',
        ],
    ],

];

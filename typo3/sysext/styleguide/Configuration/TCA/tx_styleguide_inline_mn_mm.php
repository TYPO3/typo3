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
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],

    'columns' => [
        'parentid' => [
            'label' => 'parentid',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_styleguide_inline_mn',
                'foreign_table_where' => "AND {#tx_styleguide_inline_mn}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_inline_mn}.{#sys_language_uid}='###REC_FIELD_sys_language_uid###'",
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

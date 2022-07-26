<?php

return [
    'ctrl' => [
        'title'    => 'Form engine - inline use combination box mm',
        'label' => 'select_child',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'translationSource' => 'l10n_source',
    ],

    'columns' => [

        'select_parent' => [
            'label' => 'select parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_styleguide_inline_usecombinationbox',
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
            ],
        ],
        'select_child' => [
            'label' => 'select child',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_styleguide_inline_usecombinationbox_child',
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
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
            'label' => 'Translation parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'tx_styleguide_inline_usecombinationbox_mm',
                'foreign_table_where' => 'AND {#tx_styleguide_inline_usecombinationbox_mm}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_inline_usecombinationbox_mm}.{#sys_language_uid} IN (-1,0)',
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
                'foreign_table' => 'tx_styleguide_inline_usecombinationbox_mm',
                'foreign_table_where' => 'AND {#tx_styleguide_inline_usecombinationbox_mm}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_inline_usecombinationbox_mm}.{#uid}!=###THIS_UID###',
                'default' => 0,
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],

    ],

    'types' => [
        '1' => [
            'showitem' => 'select_parent, select_child',
        ],
    ],

];

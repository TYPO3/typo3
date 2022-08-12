<?php

return [
    'ctrl' => [
        'title' => 'Form engine - inline 1:n foreign field',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'translationSource' => 'l10n_source',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
    ],

    'columns' => [
        'hidden' => [
            'config' => [
                'type' => 'check',
                'items' => [
                    ['Disable'],
                ],
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
                    [
                        '',
                        0,
                    ],
                ],
                'foreign_table' => 'tx_styleguide_inline_1n',
                'foreign_table_where' => 'AND {#tx_styleguide_inline_1n}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_inline_1n}.{#sys_language_uid} IN (-1,0)',
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
                'foreign_table' => 'tx_styleguide_inline_1n',
                'foreign_table_where' => 'AND {#tx_styleguide_inline_1n}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_inline_1n}.{#uid}!=###THIS_UID###',
                'default' => 0,
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => '',
            ],
        ],

        'inline_1' => [
            'label' => 'inline_1 description',
            'description' => 'field description',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_styleguide_inline_1n_inline_1_child',
                'foreign_field' => 'parentid',
                'foreign_table_field' => 'parenttable',
                'appearance' => [
                    'showSynchronizationLink' => true,
                    'showAllLocalizationLink' => true,
                    'showPossibleLocalizationRecords' => true,
                ],
            ],
        ],
        'inline_2' => [
            'label' => 'inline_2 maxitems=3',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_styleguide_inline_1n_inline_2_child',
                'foreign_field' => 'parentid',
                'foreign_table_field' => 'parenttable',
                'minitems' => 1,
                'maxitems' => 3,
                'appearance' => [
                    'collapseAll' => 1,
                    'levelLinksPosition' => 'both',
                ],
            ],
        ],

    ],

    'types' => [
        '0' => [
            'showitem' => '
                --div--;inline_1,
                    inline_1,
                --div--;inline_2,
                    inline_2,
                --div--;meta,
                    disable, sys_language_uid, l10n_parent, l10n_source,

            ',
        ],
    ],

];

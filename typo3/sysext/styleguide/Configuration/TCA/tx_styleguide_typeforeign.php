<?php

return [
    'ctrl' => [
        'title' => 'Form engine - type from foreign table',
        'label' => 'input_1',
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
        'type' => 'foreign_table:record_type',
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
                    ['', 0],
                ],
                'foreign_table' => 'tx_styleguide_type',
                'foreign_table_where' => 'AND {#tx_styleguide_type}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_type}.{#sys_language_uid} IN (-1,0)',
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
                'foreign_table' => 'tx_styleguide_type',
                'foreign_table_where' => 'AND {#tx_styleguide_type}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_type}.{#uid}!=###THIS_UID###',
                'default' => 0,
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],

        'foreign_table' => [
            'label' => 'type from foreign table',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_styleguide_type',
                'minitems' => 1,
                'maxitems' => 1,
                'size' => 1,
            ],
        ],

        'input_1' => [
            'label' => 'input_1',
            'config' => [
                'type' => 'input',
            ],
        ],
        'color_1' => [
            'label' => 'color_1',
            'config' => [
                'type' => 'color',
            ],
        ],

        'text_1' => [
            'label' => 'text_1',
            'config' => [
                'type' => 'text',
            ],
        ],

    ],

    'types' => [
        '0' => [
            'showitem' => 'foreign_table, input_1, text_1',
        ],
        'withChangedFields' => [
            'showitem' => 'foreign_table, input_1, color_1, text_1',
        ],
        'withColumnsOverrides' => [
            'showitem' => 'foreign_table, input_1, color_1, text_1',
            'columnsOverrides' => [
                'color_1' => [
                    'label' => 'color_1, readOnly, size=10',
                    'config' => [
                        'readOnly' => true,
                        'size' => 10,
                    ],
                ],
                'text_1' => [
                    'config' => [
                        'renderType' => 't3editor',
                        'format' => 'html',
                    ],
                ],
            ],
        ],
    ],

];

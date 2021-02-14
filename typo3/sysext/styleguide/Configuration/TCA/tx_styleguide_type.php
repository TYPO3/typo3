<?php

return [
    'ctrl' => [
        'title' => 'Form engine - type',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
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
        'type' => 'record_type',
    ],

    'columns' => [

        'hidden' => [
            'exclude' => 1,
            'config' => [
                'type' => 'check',
                'items' => [
                    '1' => [
                        '0' => 'Disable',
                    ],
                ],
            ],
        ],
        'sys_language_uid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'items' => [
                    ['LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.allLanguages', -1],
                    ['LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.default_value', 0]
                ],
                'default' => 0,
            ]
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
                'default' => 0
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
                'foreign_table' => 'tx_styleguide_type',
                'foreign_table_where' => 'AND {#tx_styleguide_type}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_type}.{#uid}!=###THIS_UID###',
                'default' => 0
            ]
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough'
            ]
        ],

        'record_type' => [
            'label' => 'type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['type 0', '0'],
                    ['Type with changed fields', 'withChangedFields'],
                    ['Type with columnsOverrides', 'withColumnsOverrides'],
                ],
            ],
        ],
        'input_1' => [
            'label' => 'input_1',
            'config' => [
                'type' => 'input',
            ]
        ],
        'input_2' => [
            'label' => 'input_2, renderType=colorpicker',
            'config' => [
                'type' => 'input',
                'renderType' => 'colorpicker'
            ]
        ],

        'text_1' => [
            'exclude' => 1,
            'label' => 'text_1',
            'config' => [
                'type' => 'text',
            ],
        ],

    ],

    'types' => [
        '0' => [
            'showitem' => 'record_type, input_1, text_1',
        ],
        'withChangedFields' => [
            'showitem' => 'record_type, input_1, input_2, text_1',
        ],
        'withColumnsOverrides' => [
            'showitem' => 'record_type, input_1, input_2, text_1',
            'columnsOverrides' => [
                'input_2' => [
                    'config' => [
                        'renderType' => '',
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

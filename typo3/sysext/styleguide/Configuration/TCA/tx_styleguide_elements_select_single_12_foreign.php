<?php
return [
    'ctrl' => [
        'title' => 'Form engine elements - select foreign single_12',
        'label' => 'group_1',
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'selicon_field' => 'group_1',
        'selicon_field_path' => 'uploads/tx_styleguide',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'translationSource' => 'l10n_source',
    ],


    'columns' => [
        'sys_language_uid' => [
            'exclude' => 1,
            'label'  => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.language',
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
            'label' => 'Translation source',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'tx_styleguide_elements_select_single_12_foreign',
                'foreign_table_where' => 'AND tx_styleguide_elements_select_single_12_foreign.pid=###CURRENT_PID### AND tx_styleguide_elements_select_single_12_foreign.sys_language_uid IN (-1,0)',
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
                'foreign_table' => 'tx_styleguide_elements_select_single_12_foreign',
                'foreign_table_where' => 'AND tx_styleguide_elements_select_single_12_foreign.pid=###CURRENT_PID### AND tx_styleguide_elements_select_single_12_foreign.uid!=###THIS_UID###',
                'default' => 0
            ]
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough'
            ]
        ],
        'hidden' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'default' => '0'
            ],
        ],


        'group_1' => [
            'label' => 'group_1',
            'exclude' => 1,
            'config' => [
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => 'jpg,gif,png,svg',
                'uploadfolder' => 'uploads/tx_styleguide',
                'size' => 1,
                'maxitems' => 1,
            ],
        ],


    ],


    'types' => [
        '0' => [
            'showitem' => 'group_1',
        ],
    ],


];

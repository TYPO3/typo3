<?php

return [
    'ctrl' => [
        'title' => 'Form engine elements - select foreign single_21',
        'label' => 'title',
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'translationSource' => 'l10n_source',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
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
            'label' => 'Translation source',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => '', 'value' => 0],
                ],
                'foreign_table' => 'tx_styleguide_elements_select_single_21_foreign',
                'foreign_table_where' => 'AND {#tx_styleguide_elements_select_single_21_foreign}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_elements_select_single_21_foreign}.{#sys_language_uid} IN (-1,0)',
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
                    ['label' => '', 'value' => 0],
                ],
                'foreign_table' => 'tx_styleguide_elements_select_single_21_foreign',
                'foreign_table_where' => 'AND {#tx_styleguide_elements_select_single_21_foreign}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_elements_select_single_21_foreign}.{#uid}!=###THIS_UID###',
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

        'title' => [
            'label' => 'title',
            'config' => [
                'type' => 'input',
            ],
        ],

        'item_group' => [
            'label' => 'item_group',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'Group 3', 'value' => 'group3'],
                    ['label' => 'Group 4 - uses locallang label', 'value' => 'group4'],
                    ['label' => 'Group 5 - not defined', 'value' => 'group5'],
                ],
            ],
        ],

    ],

    'types' => [
        '0' => [
            'showitem' => 'title,item_group',
        ],
    ],

];

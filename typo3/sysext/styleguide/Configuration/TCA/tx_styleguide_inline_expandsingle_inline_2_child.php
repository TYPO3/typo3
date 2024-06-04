<?php

return [
    'ctrl' => [
        'title' => 'Form engine - inline expand single - inline_2',
        'label' => 'uid',
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
            'label' => 'Translation parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => '', 'value' => 0],
                ],
                'foreign_table' => 'tx_styleguide_inline_expandsingle_inline_2_child',
                'foreign_table_where' => 'AND {#tx_styleguide_inline_expandsingle_inline_2_child}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_inline_expandsingle_inline_2_child}.{#sys_language_uid} IN (-1,0)',
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
                'foreign_table' => 'tx_styleguide_inline_expandsingle_inline_2_child',
                'foreign_table_where' => 'AND {#tx_styleguide_inline_expandsingle_inline_2_child}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_inline_expandsingle_inline_2_child}.{#uid}!=###THIS_UID###',
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
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'parenttable' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],

        'inputdatetime_1' => [
            'label' => 'inputdatetime_1',
            'description' => 'format=date',
            'config' => [
                'type' => 'datetime',
                'format' => 'date',
            ],
        ],
        'inputdatetime_2' => [
            'label' => 'inputdatetime_2',
            'description' => 'dbType=date format=date',
            'config' => [
                'type' => 'datetime',
                'dbType' => 'date',
                'format' => 'date',
            ],
        ],
        'inputdatetime_3' => [
            'label' => 'inputdatetime_3',
            'description' => 'format=datetime eval=datetime',
            'config' => [
                'type' => 'datetime',
                'dbType' => 'datetime',
            ],
        ],
    ],

    'types' => [
        '0' => [
            'showitem' => '
                --div--;tab1,
                    inputdatetime_1, inputdatetime_2,
                --div--;tab2,
                    inputdatetime_3,
            ',
        ],

    ],

];

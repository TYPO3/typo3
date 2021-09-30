<?php

return [
    'ctrl' => [
        'title' => 'Form engine - inline 1:n foreign field child',
        'label' => 'input_1',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'translationSource' => 'l10n_source',
        'enablecolumns' => [
            'disabled' => 'disable',
        ],
    ],

    'columns' => [

        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => '',
            ]
        ],
        'disable' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.disable',
            'config' => [
                'type' => 'check'
            ]
        ],
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language'
            ]
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
                        0
                    ]
                ],
                'foreign_table' => 'tx_styleguide_inline_1n_child',
                'foreign_table_where' => 'AND {#tx_styleguide_inline_1n_child}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_inline_1n_child}.{#sys_language_uid} IN (-1,0)',
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
                'foreign_table' => 'tx_styleguide_inline_1n_child',
                'foreign_table_where' => 'AND {#tx_styleguide_inline_1n_child}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_inline_1n_child}.{#uid}!=###THIS_UID###',
                'default' => 0
            ]
        ],

        'parentid' => [
            'config' => [
                'type' => 'passthrough',
            ]
        ],
        'parenttable' => [
            'config' => [
                'type' => 'passthrough',
            ]
        ],
        'input_1' => [
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'input_1',
            'config' => [
                'type' => 'input',
                'size' => '30',
            ],
        ],
        'input_2' => [
            'exclude' => 1,
            'label' => 'input_2 renderType=colorpicker, valuePicker',
            'config' => [
                'type' => 'input',
                'renderType' => 'colorpicker',
                'size' => 10,
                'valuePicker' => [
                    'items' => [
                        [ 'blue', '#0000FF'],
                        [ 'red', '#FF0000'],
                        [ 'typo3 orange', '#FF8700'],
                    ],
                ],
            ],
        ],
        'input_3' => [
            'exclude' => 1,
            'label' => 'input_3',
            'description' => 'placeholder=__row|input_1 mode=useOrOverridePlaceholder eval=null default=null',
            'config' => [
                'type' => 'input',
                'placeholder' => '__row|input_1',
                'eval' => 'null',
                'default' => null,
                'mode' => 'useOrOverridePlaceholder',
            ],
        ],
        'group_db_1' => [
            'exclude' => 1,
            'label' => 'group_db_1 allowed=tx_styleguide_staticdata',
            'config' => [
                'type' => 'group',
                'allowed' => 'tx_styleguide_staticdata',
            ],
        ],
        'select_tree_1' => [
            'exclude' => 1,
            'label' => 'select_tree_1 pages',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectTree',
                'foreign_table' => 'pages',
                'treeConfig' => [
                    'parentField' => 'pid',
                ],
            ],
        ],

    ],
    'types' => [
        '0' => [
            'showitem' => '
                --div--;General, input_1, input_2, input_3, group_db_1, select_tree_1,
                --div--;meta, disable, sys_language_uid, l10n_parent, l10n_source,
            ',
        ],
    ],

];

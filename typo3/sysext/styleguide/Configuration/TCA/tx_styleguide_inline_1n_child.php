<?php
return [
    'ctrl' => [
        'title' => 'Form engine - inline 1:n foreign field child',
        'label' => 'input_1',
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'disable',
        ],
        'sortby' => 'sorting',
        'default_sortby' => 'ORDER BY crdate',
    ],


    'columns' => [


        'sys_language_uid' => [
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
            'label' => 'Translation parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'tx_styleguide_inline_1n_child',
                'foreign_table_where' => 'AND tx_styleguide_inline_1n_child.pid=###CURRENT_PID### AND tx_styleguide_inline_1n_child.sys_language_uid IN (-1,0)',
            ]
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough'
            ]
        ],
        'disable' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.disable',
            'config' => [
                'type' => 'check'
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
        'group_db_1' => [
            'exclude' => 1,
            'label' => 'group_db_1 allowed=tx_styleguide_staticdata',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
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
                --div--;General, input_1, group_db_1, select_tree_1,
                --div--;Visibility, sys_language_uid, l18n_parent, l18n_diffsource, disable, parentid, parenttable
            ',
        ],
    ],


];

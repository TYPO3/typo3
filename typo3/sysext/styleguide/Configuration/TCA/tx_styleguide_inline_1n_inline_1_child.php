<?php

return [
    'ctrl' => [
        'title' => 'Form engine - inline 1:n inline_1 foreign field child',
        'label' => 'input_1',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
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
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],

    'columns' => [
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
        'input_1' => [
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'input_1',
            'config' => [
                'type' => 'input',
                'size' => '30',
            ],
        ],
        'color_1' => [
            'label' => 'color_1, valuePicker',
            'config' => [
                'type' => 'color',
                'size' => 10,
                'valuePicker' => [
                    'items' => [
                        [ 'label' => 'blue', 'value' => '#0000FF'],
                        [ 'label' => 'red', 'value' => '#FF0000'],
                        [ 'label' => 'typo3 orange', 'value' => '#FF8700'],
                    ],
                ],
            ],
        ],
        'input_3' => [
            'label' => 'input_3',
            'description' => 'placeholder=__row|input_1 mode=useOrOverridePlaceholder nullable=true default=null',
            'config' => [
                'type' => 'input',
                'placeholder' => '__row|input_1',
                'nullable' => true,
                'default' => null,
                'mode' => 'useOrOverridePlaceholder',
            ],
        ],
        'group_db_1' => [
            'label' => 'group_db_1 allowed=tx_styleguide_staticdata',
            'config' => [
                'type' => 'group',
                'allowed' => 'tx_styleguide_staticdata',
            ],
        ],
        'select_tree_1' => [
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
                --div--;General, input_1, color_1, input_3, group_db_1, select_tree_1,
                --div--;meta, disable, sys_language_uid, l10n_parent, l10n_source,
            ',
        ],
    ],

];

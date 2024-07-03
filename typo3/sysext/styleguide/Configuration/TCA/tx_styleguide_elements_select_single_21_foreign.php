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

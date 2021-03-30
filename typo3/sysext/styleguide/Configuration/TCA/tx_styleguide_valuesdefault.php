<?php

return [
    'ctrl' => [
        'title' => 'Form engine - default values new records',
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
                    ['', 0],
                ],
                'foreign_table' => 'tx_styleguide_valuesdefault',
                'foreign_table_where' => 'AND {#tx_styleguide_valuesdefault}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_valuesdefault}.{#sys_language_uid} IN (-1,0)',
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
                'foreign_table' => 'tx_styleguide_valuesdefault',
                'foreign_table_where' => 'AND {#tx_styleguide_valuesdefault}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_valuesdefault}.{#uid}!=###THIS_UID###',
                'default' => 0
            ]
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough'
            ]
        ],

        'input_1' => [
            'exclude' => 1,
            'label' => 'input_1 default=Default value"',
            'config' => [
                'type' => 'input',
                'default' => 'Default value',
            ],
        ],
        'input_2' => [
            'exclude' => 1,
            'label' => 'input_26 default=0, eval=datetime, readonly=1',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'readOnly' => 1,
                'eval' => 'datetime',
                'default' => 0,
            ],
        ],
        'input_3' => [
            'exclude' => 1,
            'label' => 'input_27 default=3, eval=int, range lower=2, range upper=7',
            'config' => [
                'type' => 'input',
                'eval' => 'int',
                'range' => [
                    'lower' => 2,
                    'upper' => 7,
                ],
                'default' => 3,
            ],
        ],

        'text_1' => [
            'exclude' => 1,
            'label' => 'text_12 default="text_12"',
            'config' => [
                'type' => 'text',
                'default' => 'text_12',
            ],
        ],

        'checkbox_1' => [
            'exclude' => 1,
            'label' => 'checkbox_1 default=1',
            'config' => [
                'type' => 'check',
                'default' => 1,
            ]
        ],
        'checkbox_2' => [
            'exclude' => 1,
            'label' => 'checkbox_2 default=1, one checkbox with label',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['foo', ''],
                ],
                'default' => 1
            ]
        ],
        'checkbox_3' => [
            'exclude' => 1,
            'label' => 'checkbox_3 default=5, four checkboxes, 1 and 3 pre-selected',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['foo1', ''],
                    ['foo2', ''],
                    ['foo3', ''],
                    ['foo4', ''],
                ],
                'default' => 5,
            ],
        ],
        'checkbox_4' => [
            'exclude' => 1,
            'label' => 'checkbox_4 cols=inline, "MO" and "WE" pre-selected',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['Mo', ''],
                    ['Tu', ''],
                    ['We', ''],
                    ['Th', ''],
                    ['Fr', ''],
                    ['Sa', ''],
                    ['Su', ''],
                ],
                'cols' => 'inline',
                'default' => 5,
            ],
        ],

        'radio_1' => [
            'exclude' => 1,
            'label' => 'radio_1 default=2, three options, second pre-selected',
            'config' => [
                'type' => 'radio',
                'items' => [
                    ['foo1', 1],
                    ['foo2', 2],
                    ['foo3', 3],
                ],
                'default' => 2,
            ],
        ],
        'radio_2' => [
            'exclude' => 1,
            'label' => 'radio_2 default=y, three options, second pre-selected',
            'config' => [
                'type' => 'radio',
                'items' => [
                    ['foo1', 'x'],
                    ['foo2', 'y'],
                    ['foo3', 'z'],
                ],
                'default' => 'y',
            ],
        ],
        'radio_3' => [
            'exclude' => 1,
            'label' => 'radio_3 empty default',
            'config' => [
                'type' => 'radio',
                'items' => [
                    ['foo1', 'x'],
                    ['foo2', 'y'],
                    ['foo3', 'z'],
                ],
                'default' => '',
            ],
        ],

         // @todo add default value examples for type=none

        'select_1' => [
            'exclude' => 1,
            'label' => 'select_1 default=2, renderType=selectSingle, three items, second pre-selected',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['foo1', 1],
                    ['foo2', 2],
                    ['foo3', 4],
                ],
                'default' => 2,
            ],
        ],
        'select_2' => [
            'exclude' => 1,
            'label' => 'select_2 default=1,3 renderType=selectCheckBox',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'items' => [
                    ['foo 1', 1],
                    ['foo 2', 2],
                    ['foo 3', 3],
                    ['foo 4', 4],
                ],
                'default' => '1,3'
            ],
        ],

    ],

    'types' => [
        '0' => [
            'showitem' => '
                --div--;basic,
                    input_1, input_2, input_3,
                    text_1,
                    checkbox_1, checkbox_2, checkbox_3, checkbox_4,
                    radio_1, radio_2, radio_3,
                --div--;select,
                    select_1,select_2,
            ',
        ],
    ],

];

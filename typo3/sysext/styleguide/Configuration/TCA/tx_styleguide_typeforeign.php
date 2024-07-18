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
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'translationSource' => 'l10n_source',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'type' => 'foreign_table:record_type',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],

    'columns' => [
        'foreign_table' => [
            'label' => 'type from foreign table',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_styleguide_type',
                'minitems' => 1,
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
                        'renderType' => 'codeEditor',
                        'format' => 'html',
                    ],
                ],
            ],
        ],
    ],

];

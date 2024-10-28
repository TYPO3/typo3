<?php

return [
    'ctrl' => [
        'title' => 'Form engine - type',
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
        'type' => 'record_type',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],

    'columns' => [
        'record_type' => [
            'label' => 'type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'type 0', 'value' => '0'],
                    ['label' => 'Type with changed fields', 'value' => 'withChangedFields'],
                    ['label' => 'Type with columnsOverrides', 'value' => 'withColumnsOverrides'],
                    ['label' => 'Type with no fields', 'value' => 'withoutFieldsToRender'],
                ],
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
            'showitem' => 'record_type, input_1, text_1',
        ],
        'withChangedFields' => [
            'showitem' => 'record_type, input_1, color_1, text_1',
        ],
        'withColumnsOverrides' => [
            'showitem' => 'record_type, input_1, color_1, text_1',
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
        'withoutFieldsToRender' => [
            'showitem' => '',
        ],
    ],

];

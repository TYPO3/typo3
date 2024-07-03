<?php

return [
    'ctrl' => [
        'title' => 'Form engine - flex child flex_3 inline_1',
        'label' => 'input_1',
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
            'label' => 'input_1 description',
            'description' => 'field description',
            'config' => [
                'type' => 'input',
            ],
        ],
        'file_1' => [
            'label' => 'input_1 file',
            'description' => 'file description',
            'config' => [
                'type' => 'file',
                'allowed' => 'jpg,png',
            ],
        ],
    ],

    'types' => [
        '0' => [
            'showitem' => 'input_1, file_1',
            'columnsOverrides' => [
                'file_1' => [
                    'label' => 'Overridden label via overrideChildTca in TCA',
                    'config' => [
                        'overrideChildTca' => [
                            'columns' => [
                                'title' => [
                                    'label' => 'Label override via child TCA',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
];

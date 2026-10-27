<?php

return [
    'ctrl' => [
        'title' => 'tx_persist_null',
        'label' => 'input_null',
        'hideAtCopy' => false,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'rootLevel' => -1,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'prependAtCopy' => '',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => implode(',', []),
        ],
    ],
    'columns' => [
        'input_null' => [
            'config' => [
                'type' => 'input',
                'nullable' => true,
            ],
        ],
        'number_null' => [
            'config' => [
                'type' => 'number',
                'nullable' => true,
            ],
        ],
        'number_null_float' => [
            'config' => [
                'type' => 'number',
                'scale' => 2,
                'nullable' => true,
            ],
        ],
    ],
];

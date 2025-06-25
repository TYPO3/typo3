<?php

return [
    'ctrl' => [
        'title' => 'tx_testdatahandler_datetime',
        'label' => 'title',
        'hideAtCopy' => false,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
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
            'showitem' => implode(',', [
                'title',
                'hidden',
                'sys_language_uid',
                'l10n_parent',

                'datetime_int',
                'datetime_int_nullable',
                'datetime_native',

                'date_int',
                'date_int_nullable',
                'date_native',

                'timesec_int',
                'timesec_int_nullable',
                'timesec_native',

                'time_int',
                'time_int_nullable',
                'time_native',
            ]),
        ],
    ],
    'columns' => [
        'title' => [
            'exclude' => false,
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_formlabel',
            'config' => [
                'type' => 'input',
                'size' => 60,
                'max' => 255,
            ],
        ],

        'datetime_int' => [
            'exclude' => false,
            'label' => 'datetime int',
            'config' => [
                'type' => 'datetime',
            ],
        ],
        'datetime_int_nullable' => [
            'exclude' => false,
            'label' => 'datetime int nullable',
            'config' => [
                'type' => 'datetime',
                'nullable' => true,
            ],
        ],
        'datetime_native' => [
            'exclude' => false,
            'label' => 'datetime native',
            'config' => [
                'type' => 'datetime',
                'dbType' => 'datetime',
            ],
        ],

        'date_int' => [
            'exclude' => false,
            'label' => 'date int',
            'config' => [
                'type' => 'datetime',
                'format' => 'date',
            ],
        ],
        'date_int_nullable' => [
            'exclude' => false,
            'label' => 'date int nullable',
            'config' => [
                'type' => 'datetime',
                'format' => 'date',
                'nullable' => true,
            ],
        ],
        'date_native' => [
            'exclude' => false,
            'label' => 'date native',
            'config' => [
                'type' => 'datetime',
                'format' => 'date',
                'dbType' => 'date',
            ],
        ],

        'timesec_int' => [
            'exclude' => false,
            'label' => 'timesec int',
            'config' => [
                'type' => 'datetime',
                'format' => 'timesec',
            ],
        ],
        'timesec_int_nullable' => [
            'exclude' => false,
            'label' => 'timesec int nullable',
            'config' => [
                'type' => 'datetime',
                'format' => 'timesec',
                'nullable' => true,
            ],
        ],
        'timesec_native' => [
            'exclude' => false,
            'label' => 'timesec native',
            'config' => [
                'type' => 'datetime',
                'format' => 'timesec',
                'dbType' => 'time',
            ],
        ],

        'time_int' => [
            'exclude' => false,
            'label' => 'time int',
            'config' => [
                'type' => 'datetime',
                'format' => 'time',
            ],
        ],
        'time_int_nullable' => [
            'exclude' => false,
            'label' => 'time int nullable',
            'config' => [
                'type' => 'datetime',
                'format' => 'time',
                'nullable' => true,
            ],
        ],
        'time_native' => [
            'exclude' => false,
            'label' => 'time native',
            'config' => [
                'type' => 'datetime',
                'format' => 'time',
                'dbType' => 'time',
            ],
        ],
    ],
];

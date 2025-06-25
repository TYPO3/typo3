<?php

return [
    'ctrl' => [
        'title' => 'tx_testdatahandler_datetime_legacy',
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

                'datetime_native_notnull',

                'date_native_notnull',

                'timesec_native_notnull',

                'time_native_notnull',
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

        'datetime_native_notnull' => [
            'exclude' => false,
            'label' => 'datetime native not null',
            'config' => [
                'type' => 'datetime',
                'dbType' => 'datetime',
                'nullable' => false,
            ],
        ],

        'date_native_notnull' => [
            'exclude' => false,
            'label' => 'date native not null',
            'config' => [
                'type' => 'datetime',
                'format' => 'date',
                'dbType' => 'date',
                'nullable' => false,
            ],
        ],

        'timesec_native_notnull' => [
            'exclude' => false,
            'label' => 'timesec native not null',
            'config' => [
                'type' => 'datetime',
                'format' => 'timesec',
                'dbType' => 'time',
                'nullable' => false,
            ],
        ],

        'time_native_notnull' => [
            'exclude' => false,
            'label' => 'time native not null',
            'config' => [
                'type' => 'datetime',
                'format' => 'time',
                'dbType' => 'time',
                'nullable' => false,
            ],
        ],
    ],
];

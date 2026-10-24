<?php

return [
    'ctrl' => [
        'title' => 'tx_testdatahandler_country',
        'label' => 'title',
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
            'showitem' => '',
        ],
    ],
    'columns' => [
        'country' => [
            'label' => 'country',
            'config' => [
                'type' => 'country',
            ],
        ],
        'country_filter_onlycountries' => [
            'label' => 'country',
            'config' => [
                'type' => 'country',
                'filter' => [
                    'onlyCountries' => ['DE', 'FR', 'IT'],
                ],
            ],
        ],
        'country_filter_excludeCountries' => [
            'label' => 'country',
            'config' => [
                'type' => 'country',
                'filter' => [
                    'excludeCountries' => ['DE', 'FR', 'IT'],
                ],
            ],
        ],
        'country_filter_both' => [
            'label' => 'country',
            'config' => [
                'type' => 'country',
                'filter' => [
                    'onlyCountries' => ['DE', 'FR', 'IT'],
                    'excludeCountries' => ['FR'],
                ],
            ],
        ],
        'country_nullable' => [
            'label' => 'country',
            'config' => [
                'type' => 'country',
                'nullable' => true,
            ],
        ],
        'country_default' => [
            'label' => 'country',
            'config' => [
                'type' => 'country',
                'default' => 'DE',
            ],
        ],
    ],
];

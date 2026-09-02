<?php

return [
    'ctrl' => [
        'title' => 'tx_testunique_element',
        'label' => 'title',
        'hideAtCopy' => false,
        'prependAtCopy' => '',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'sortby' => 'sorting',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
        'versioningWS' => true,
    ],
    'types' => [
        '0' => [
            'showitem' => 'title,unique_input,unique_in_pid_input,unique_excluded_input,'
                . 'unique_slug,unique_in_site_slug,unique_in_pid_slug,'
                . 'hidden,sys_language_uid,l10n_parent',
        ],
    ],
    'columns' => [
        'title' => [
            'label' => 'title',
            'config' => [
                'type' => 'input',
                'size' => 60,
                'max' => 255,
            ],
        ],
        'unique_input' => [
            'label' => 'input with eval=unique',
            'config' => [
                'type' => 'input',
                'eval' => 'unique',
            ],
        ],
        'unique_in_pid_input' => [
            'label' => 'input with eval=uniqueInPid',
            'config' => [
                'type' => 'input',
                'eval' => 'uniqueInPid',
            ],
        ],
        'unique_excluded_input' => [
            'label' => 'input with eval=unique, excluded from localization',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'input',
                'eval' => 'unique',
            ],
        ],
        'unique_slug' => [
            'label' => 'slug with eval=unique',
            'config' => [
                'type' => 'slug',
                'generatorOptions' => [
                    'fields' => ['title'],
                ],
                'eval' => 'unique',
            ],
        ],
        'unique_in_site_slug' => [
            'label' => 'slug with eval=uniqueInSite',
            'config' => [
                'type' => 'slug',
                'generatorOptions' => [
                    'fields' => ['title'],
                ],
                'eval' => 'uniqueInSite',
            ],
        ],
        'unique_in_pid_slug' => [
            'label' => 'slug with eval=uniqueInPid',
            'config' => [
                'type' => 'slug',
                'generatorOptions' => [
                    'fields' => ['title'],
                ],
                'eval' => 'uniqueInPid',
            ],
        ],
    ],
];

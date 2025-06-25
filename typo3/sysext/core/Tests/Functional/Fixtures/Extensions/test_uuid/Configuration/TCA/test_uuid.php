<?php

return [
    'ctrl' => [
        'title' => 'tx_uuid',
        'label' => 'title',
        'hideAtCopy' => false,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'sortby' => 'sorting',
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
        'versioningWS' => true,
    ],
    'types' => [
        '0' => [
            'showitem' => 'title,unique_identifier,hidden,sys_language_uid,l10n_parent',
        ],
    ],
    'columns' => [
        'unique_identifier' => [
            'exclude' => false,
            'label' => 'unique_identifier',
            'config' => [
                'type' => 'uuid',
            ],
        ],
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
    ],
];

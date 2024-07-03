<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'DataHandler Test Element',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'prependAtCopy' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.prependAtCopy',
        'sortby' => 'sorting',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'versioningWS' => true,
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'title' => [
            'exclude' => true,
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'Title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'required' => true,
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' =>
                '--div--;LLL:EXT:test_datahandler/Resources/Private/Language/locallang_db.xlf:tabs.general, title,' .
                '--div--;LLL:EXT:test_datahandler/Resources/Private/Language/locallang_db.xlf:tabs.visibility, sys_language_uid, l10n_parent, l10n_diffsource, hidden',
        ],
    ],
    'palettes' => [
        '1' => [
            'showitem' => '',
        ],
    ],
];

<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:test_irre_foreignfield_non_ws/Resources/Private/Language/locallang_db.xlf:tx_testirreforeignfieldnonws_price',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'translationSource' => 'l10n_source',
        'sortby' => 'sorting',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile' => 'EXT:test_irre_foreignfield_non_ws/Resources/Public/Icons/Extension.svg',
        'versioningWS' => false,
        'origUid' => 't3_origuid',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'parentid' => [
            'config' => [
                'type' => 'passthrough',
                'default' => 0,
            ],
        ],
        'parenttable' => [
            'config' => [
                'type' => 'passthrough',
                'default' => '',
            ],
        ],
        'parentidentifier' => [
            'config' => [
                'type' => 'passthrough',
                'default' => '',
            ],
        ],
        'title' => [
            'exclude' => true,
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'LLL:EXT:test_irre_foreignfield_non_ws/Resources/Private/Language/locallang_db.xlf:tx_testirreforeignfieldnonws_price.title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'required' => true,
            ],
        ],
        'price' => [
            'exclude' => true,
            'label' => 'LLL:EXT:test_irre_foreignfield_non_ws/Resources/Private/Language/locallang_db.xlf:tx_testirreforeignfieldnonws_price.price',
            'config' => [
                'type' => 'number',
                'format' => 'decimal',
                'size' => 30,
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' =>
            '--div--;LLL:EXT:test_irre_foreignfield_non_ws/Resources/Private/Language/locallang_db.xlf:tabs.general, title, price,' .
            '--div--;LLL:EXT:test_irre_foreignfield_non_ws/Resources/Private/Language/locallang_db.xlf:tabs.visibility, sys_language_uid, l18n_parent, l18n_diffsource, hidden, parentid',
        ],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];

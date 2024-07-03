<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:test_irre_mm/Resources/Private/Language/locallang_db.xlf:tx_testirremm_price',
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
        'iconfile' => 'EXT:test_irre_mm/Resources/Public/Icons/icon_price.gif',
        'versioningWS' => true,
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'title' => [
            'exclude' => true,
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'LLL:EXT:test_irre_mm/Resources/Private/Language/locallang_db.xlf:tx_testirremm_price.title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'required' => true,
            ],
        ],
        'price' => [
            'exclude' => true,
            'label' => 'LLL:EXT:test_irre_mm/Resources/Private/Language/locallang_db.xlf:tx_testirremm_price.price',
            'config' => [
                'type' => 'number',
                'format' => 'decimal',
                'size' => 30,
            ],
        ],
        'offers' => [
            'exclude' => true,
            'label' => 'LLL:EXT:test_irre_mm/Resources/Private/Language/locallang_db.xlf:tx_testirremm_price.offers',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_testirremm_offer',
                'MM' => 'tx_testirremm_offer_price_rel',
                'MM_opposite_field' => 'prices',
                'maxitems' => 10,
                'appearance' => [
                    'showSynchronizationLink' => 1,
                    'showAllLocalizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                ],
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' =>
            '--div--;LLL:EXT:test_irre_mm/Resources/Private/Language/locallang_db.xlf:tabs.general, title, price, offers,' .
            '--div--;LLL:EXT:test_irre_mm/Resources/Private/Language/locallang_db.xlf:tabs.visibility, sys_language_uid, l18n_parent, l18n_diffsource, hidden',
        ],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];

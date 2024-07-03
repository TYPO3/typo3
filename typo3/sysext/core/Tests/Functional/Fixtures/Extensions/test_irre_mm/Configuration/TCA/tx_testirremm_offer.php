<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:test_irre_mm/Resources/Private/Language/locallang_db.xlf:tx_testirremm_offer',
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
        'iconfile' => 'EXT:test_irre_mm/Resources/Public/Icons/icon_offer.gif',
        'versioningWS' => true,
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'title' => [
            'exclude' => true,
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'LLL:EXT:test_irre_mm/Resources/Private/Language/locallang_db.xlf:tx_testirremm_offer.title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'required' => true,
            ],
        ],
        'hotels' => [
            'exclude' => true,
            'label' => 'LLL:EXT:test_irre_mm/Resources/Private/Language/locallang_db.xlf:tx_testirremm_offer.hotels',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_testirremm_hotel',
                'MM' => 'tx_testirremm_hotel_offer_rel',
                'MM_opposite_field' => 'offers',
                'maxitems' => 10,
                'appearance' => [
                    'showSynchronizationLink' => 1,
                    'showAllLocalizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                ],
            ],
        ],
        'prices' => [
            'exclude' => true,
            'label' => 'LLL:EXT:test_irre_mm/Resources/Private/Language/locallang_db.xlf:tx_testirremm_offer.prices',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_testirremm_price',
                'MM' => 'tx_testirremm_offer_price_rel',
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
            '--div--;LLL:EXT:test_irre_mm/Resources/Private/Language/locallang_db.xlf:tabs.general, title, hotels, prices,' .
            '--div--;LLL:EXT:test_irre_mm/Resources/Private/Language/locallang_db.xlf:tabs.visibility, sys_language_uid, l18n_parent, l18n_diffsource, hidden',
        ],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];

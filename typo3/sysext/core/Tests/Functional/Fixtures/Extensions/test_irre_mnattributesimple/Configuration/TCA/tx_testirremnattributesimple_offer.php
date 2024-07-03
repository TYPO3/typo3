<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:test_irre_mnattributesimple/Resources/Private/Language/locallang_db.xlf:tx_testirremnattributesimple_offer',
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
        'iconfile' => 'EXT:test_irre_mnattributesimple/Resources/Public/Icons/icon_offer.gif',
        'versioningWS' => true,
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'title' => [
            'exclude' => true,
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'LLL:EXT:test_irre_mnattributesimple/Resources/Private/Language/locallang_db.xlf:tx_irretutorial_offer.title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'required' => true,
            ],
        ],
        'hotels' => [
            'exclude' => true,
            'label' => 'LLL:EXT:test_irre_mnattributesimple/Resources/Private/Language/locallang_db.xlf:tx_irretutorial_offer.hotels',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_testirremnattributesimple_hotel_offer_rel',
                'foreign_field' => 'offerid',
                'foreign_sortby' => 'offersort',
                'foreign_label' => 'hotelid',
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
            '--div--;LLL:EXT:test_irre_mnattributesimple/Resources/Private/Language/locallang_db.xlf:tabs.general, title, hotels,' .
            '--div--;LLL:EXT:test_irre_mnattributesimple/Resources/Private/Language/locallang_db.xlf:tabs.visibility, sys_language_uid, l18n_parent, l18n_diffsource, hidden',
        ],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];

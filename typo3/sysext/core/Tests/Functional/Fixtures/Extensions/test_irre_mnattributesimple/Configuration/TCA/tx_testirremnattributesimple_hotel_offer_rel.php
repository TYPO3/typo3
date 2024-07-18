<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:test_irre_mnattributesimple/Resources/Private/Language/locallang_db.xlf:tx_testirremnattributesimple_hotel_offer_rel',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'translationSource' => 'l10n_source',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile' => 'EXT:test_irre_mnattributesimple/Resources/Public/Icons/icon_hotel_offer_rel.gif',
        'versioningWS' => true,
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'hotelid' => [
            'label' => 'LLL:EXT:test_irre_mnattributesimple/Resources/Private/Language/locallang_db.xlf:tx_irretutorial_hotel_offer_rel.hotelid',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_testirremnattributesimple_hotel',
                'default' => 0,
            ],
        ],
        'offerid' => [
            'label' => 'LLL:EXT:test_irre_mnattributesimple/Resources/Private/Language/locallang_db.xlf:tx_irretutorial_hotel_offer_rel.offerid',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_testirremnattributesimple_offer',
                'default' => 0,
            ],
        ],
        'hotelsort' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'offersort' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'quality' => [
            'exclude' => true,
            'label' => 'LLL:EXT:test_irre_mnattributesimple/Resources/Private/Language/locallang_db.xlf:tx_irretutorial_hotel_offer_rel.quality',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'LLL:EXT:test_irre_mnattributesimple/Resources/Private/Language/locallang_db.xlf:tx_irretutorial_hotel_offer_rel.quality.I.0', 'value' => '1'],
                    ['label' => 'LLL:EXT:test_irre_mnattributesimple/Resources/Private/Language/locallang_db.xlf:tx_irretutorial_hotel_offer_rel.quality.I.1', 'value' => '2'],
                    ['label' => 'LLL:EXT:test_irre_mnattributesimple/Resources/Private/Language/locallang_db.xlf:tx_irretutorial_hotel_offer_rel.quality.I.2', 'value' => '3'],
                    ['label' => 'LLL:EXT:test_irre_mnattributesimple/Resources/Private/Language/locallang_db.xlf:tx_irretutorial_hotel_offer_rel.quality.I.3', 'value' => '4'],
                    ['label' => 'LLL:EXT:test_irre_mnattributesimple/Resources/Private/Language/locallang_db.xlf:tx_irretutorial_hotel_offer_rel.quality.I.4', 'value' => '5'],
                ],
            ],
        ],
        'allincl' => [
            'exclude' => true,
            'label' => 'LLL:EXT:test_irre_mnattributesimple/Resources/Private/Language/locallang_db.xlf:tx_irretutorial_hotel_offer_rel.allincl',
            'config' => [
                'type' => 'check',
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' =>
            '--div--;LLL:EXT:test_irre_mnattributesimple/Resources/Private/Language/locallang_db.xlf:tabs.general, title, hotelid, offerid, hotelsort, offersort, quality, allincl,' .
            '--div--;LLL:EXT:test_irre_mnattributesimple/Resources/Private/Language/locallang_db.xlf:tabs.visibility, sys_language_uid, l18n_parent, l18n_diffsource, hidden',
        ],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];

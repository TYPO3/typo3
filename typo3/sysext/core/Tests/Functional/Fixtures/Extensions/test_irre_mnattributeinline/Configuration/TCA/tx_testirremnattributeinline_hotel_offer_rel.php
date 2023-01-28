<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:test_irre_mnattributeinline/Resources/Private/Language/locallang_db.xlf:tx_testirremnattributeinline_hotel_offer_rel',
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
        'iconfile' => 'EXT:test_irre_mnattributeinline/Resources/Public/Icons/icon_hotel_offer_rel.gif',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',
            ],
        ],
        'l18n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => '', 'value' => 0],
                ],
                'foreign_table' => 'tx_testirremnattributeinline_hotel_offer_rel',
                'foreign_table_where' => 'AND {#tx_testirremnattributeinline_hotel_offer_rel}.{#pid}=###CURRENT_PID### AND {#tx_testirremnattributeinline_hotel_offer_rel}.{#sys_language_uid} IN (-1,0)',
                'default' => 0,
            ],
        ],
        'l18n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => '',
            ],
        ],
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'hotelid' => [
            'label' => 'LLL:EXT:test_irre_mnattributeinline/Resources/Private/Language/locallang_db.xlf:tx_testirremnattributeinline__hotel_offer_rel.hotelid',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_testirremnattributeinline_hotel',
                'foreign_table_where' => 'AND {#tx_testirremnattributeinline_hotel}.{#pid}=###CURRENT_PID### AND {#tx_testirremnattributeinline_hotel}.{#sys_language_uid}="###REC_FIELD_sys_language_uid###"',
                'maxitems' => 1,
                'default' => 0,
            ],
        ],
        'offerid' => [
            'label' => 'LLL:EXT:test_irre_mnattributeinline/Resources/Private/Language/locallang_db.xlf:tx_testirremnattributeinline__hotel_offer_rel.offerid',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_testirremnattributeinline_offer',
                'foreign_table_where' => 'AND {#tx_testirremnattributeinline_offer}.{#pid}=###CURRENT_PID### AND {#tx_testirremnattributeinline_offer}.{#sys_language_uid}="###REC_FIELD_sys_language_uid###"',
                'maxitems' => 1,
                'default' => 0,
            ],
        ],
        'prices' => [
            'label' => 'LLL:EXT:test_irre_mnattributeinline/Resources/Private/Language/locallang_db.xlf:tx_testirremnattributeinline__hotel_offer_rel.prices',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_testirremnattributeinline_price',
                'foreign_field' => 'parentid',
                'maxitems' => 10,
                'appearance' => [
                    'showSynchronizationLink' => 1,
                    'showAllLocalizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                ],
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
    ],
    'types' => [
        '0' => ['showitem' =>
            '--div--;LLL:EXT:test_irre_mnattributeinline/Resources/Private/Language/locallang_db.xlf:tabs.general, title, hotelid, offerid, prices,' .
            '--div--;LLL:EXT:test_irre_mnattributeinline/Resources/Private/Language/locallang_db.xlf:tabs.visibility, sys_language_uid, l18n_parent, l18n_diffsource, hidden, hotelsort, offersort',
        ],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];

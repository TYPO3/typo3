<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xlf:tx_irretutorial_mnattr_hotel_offer_rel',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'translationSource' => 'l10n_source',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile' => 'EXT:irre_tutorial/Resources/Public/Icons/icon_tx_irretutorial_hotel_offer_rel.gif',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
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
                    ['', 0],
                ],
                'foreign_table' => 'tx_irretutorial_mnattr_hotel_offer_rel',
                'foreign_table_where' => 'AND tx_irretutorial_mnattr_hotel_offer_rel.pid=###CURRENT_PID### AND tx_irretutorial_mnattr_hotel_offer_rel.sys_language_uid IN (-1,0)',
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
            'label' => 'LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xlf:tx_irretutorial_hotel_offer_rel.hotelid',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_irretutorial_mnattr_hotel',
                'maxitems' => 1,
                'default' => 0,
            ],
        ],
        'offerid' => [
            'label' => 'LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xlf:tx_irretutorial_hotel_offer_rel.offerid',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_irretutorial_mnattr_offer',
                'maxitems' => 1,
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
            'label' => 'LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xlf:tx_irretutorial_hotel_offer_rel.quality',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xlf:tx_irretutorial_hotel_offer_rel.quality.I.0', '1'],
                    ['LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xlf:tx_irretutorial_hotel_offer_rel.quality.I.1', '2'],
                    ['LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xlf:tx_irretutorial_hotel_offer_rel.quality.I.2', '3'],
                    ['LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xlf:tx_irretutorial_hotel_offer_rel.quality.I.3', '4'],
                    ['LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xlf:tx_irretutorial_hotel_offer_rel.quality.I.4', '5'],
                ],
            ],
        ],
        'allincl' => [
            'exclude' => true,
            'label' => 'LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xlf:tx_irretutorial_hotel_offer_rel.allincl',
            'config' => [
                'type' => 'check',
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' =>
            '--div--;LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xlf:tabs.general, title, hotelid, offerid, hotelsort, offersort, quality, allincl,' .
            '--div--;LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xlf:tabs.visibility, sys_language_uid, l18n_parent, l18n_diffsource, hidden',
        ],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];

<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xlf:tx_irretutorial_mnattr_offer',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'translationSource' => 'l10n_source',
        'sortby' => 'sorting',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'iconfile' => 'EXT:irre_tutorial/Resources/Public/Icons/icon_tx_irretutorial_offer.gif',
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
                'foreign_table' => 'tx_irretutorial_mnattr_offer',
                'foreign_table_where' => 'AND tx_irretutorial_mnattr_offer.pid=###CURRENT_PID### AND tx_irretutorial_mnattr_offer.sys_language_uid IN (-1,0)',
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
        'title' => [
            'exclude' => true,
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xlf:tx_irretutorial_offer.title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'required',
            ],
        ],
        'hotels' => [
            'exclude' => true,
            'label' => 'LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xlf:tx_irretutorial_offer.hotels',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_irretutorial_mnattr_hotel_offer_rel',
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
            '--div--;LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xlf:tabs.general, title, hotels,' .
            '--div--;LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xlf:tabs.visibility, sys_language_uid, l18n_parent, l18n_diffsource, hidden',
        ],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];

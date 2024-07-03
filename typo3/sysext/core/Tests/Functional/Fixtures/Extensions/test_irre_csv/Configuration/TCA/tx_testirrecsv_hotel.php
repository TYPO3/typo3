<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:test_irre_csv/Resources/Private/Language/locallang_db.xlf:tx_testirrecsv_hotel',
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
        'iconfile' => 'EXT:test_irre_csv/Resources/Public/Icons/icon_hotel.gif',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'title' => [
            'exclude' => true,
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'LLL:EXT:test_irre_csv/Resources/Private/Language/locallang_db.xlf:tx_irretutorial_hotel.title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'required' => true,
            ],
        ],
        'offers' => [
            'exclude' => true,
            'label' => 'LLL:EXT:test_irre_csv/Resources/Private/Language/locallang_db.xlf:tx_irretutorial_hotel.offers',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_testirrecsv_offer',
                'maxitems' => 10,
                'appearance' => [
                    'showSynchronizationLink' => 1,
                    'showAllLocalizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                ],
                'default' => '',
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' =>
            '--div--;LLL:EXT:test_irre_csv/Resources/Private/Language/locallang_db.xlf:tabs.general, title, offers, ' .
            '--div--;LLL:EXT:test_irre_csv/Resources/Private/Language/locallang_db.xlf:tabs.visibility, sys_language_uid, l18n_parent, l18n_diffsource, hidden',
        ],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];

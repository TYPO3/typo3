<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

ExtensionManagementUtility::addTCAcolumns(
    'tt_content',
    [
        'tx_testirrecsv_hotels' => [
            'exclude' => true,
            'label' => 'LLL:EXT:test_irre_csv/Resources/Private/Language/locallang_db.xlf:tt_content.hotels',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_testirrecsv_hotel',
                'maxitems' => 10,
                'appearance' => [
                    'showSynchronizationLink' => 1,
                    'showAllLocalizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                ],
                'default' => '',
            ],
        ],
    ]
);

ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    '--div--;LLL:EXT:test_irre_csv/Resources/Private/Language/locallang_db.xlf:tt_content.div.test_irre_csv, tx_testirrecsv_hotels'
);

<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

ExtensionManagementUtility::addTCAcolumns(
    'pages',
    [
        'tx_testirrecsv_hotels' => [
            'label' => 'LLL:EXT:test_irre_csv/Resources/Private/Language/locallang_db.xlf:pages.hotels',
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
    'pages',
    '--div--;LLL:EXT:test_irre_csv/Resources/Private/Language/locallang_db.xlf:pages.div.test_irre_csv, tx_testirrecsv_hotels'
);

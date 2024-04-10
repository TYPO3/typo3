<?php

declare(strict_types=1);

defined('TYPO3') or die();

// Have a second category field next to 'categories' in pages table
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'pages',
    [
        'categories_other' => [
            'label' => 'Second category field',
            'config' => [
                'type' => 'category',
            ],
        ],
    ]
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'pages',
    'categories_other',
    '',
    'after:categories'
);

// Have an inline relation 'hotels'
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'pages',
    [
        'tx_testrootlineutility_hotels' => [
            'label' => 'Hotels',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_testrootlineutility_hotel',
                'foreign_field' => 'parentid',
                'foreign_table_field' => 'parenttable',
                'appearance' => [
                    'showPossibleLocalizationRecords' => 1,
                ],
            ],
        ],
    ]
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'pages',
    '--div--;Hotels, tx_testrootlineutility_hotels'
);

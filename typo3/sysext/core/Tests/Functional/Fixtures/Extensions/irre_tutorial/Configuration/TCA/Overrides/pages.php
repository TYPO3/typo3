<?php

// Show copied pages records in frontend request
$GLOBALS['TCA']['pages']['ctrl']['hideAtCopy'] = false;

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'pages',
    [
        'tx_irretutorial_hotels' => [
            'exclude' => true,
            'label' => 'LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xlf:pages.tx_irretutorial_hotels',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_irretutorial_1nff_hotel',
                'foreign_field' => 'parentid',
                'foreign_table_field' => 'parenttable',
                'maxitems' => 10,
                'appearance' => [
                    'showSynchronizationLink' => 1,
                    'showAllLocalizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showRemovedLocalizationRecords' => 1,
                ],
            ]
        ],
        'tx_irretutorial_1ncsv_hotels' => [
            'exclude' => true,
            'label' => 'LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xlf:pages.tx_irretutorial_hotels',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_irretutorial_1ncsv_hotel',
                'maxitems' => 10,
                'appearance' => [
                    'showSynchronizationLink' => 1,
                    'showAllLocalizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showRemovedLocalizationRecords' => 1,
                ],
            ]
        ],
    ]
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'pages',
    '--div--;LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xlf:pages.doktype.div.irre, tx_irretutorial_hotels, tx_irretutorial_1ncsv_hotel'
);

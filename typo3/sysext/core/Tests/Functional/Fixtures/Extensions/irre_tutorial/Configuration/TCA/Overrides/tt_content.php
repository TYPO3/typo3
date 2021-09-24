<?php

declare(strict_types=1);

defined('TYPO3') or die();

// Show copied tt_content records in frontend request
$GLOBALS['TCA']['tt_content']['ctrl']['hideAtCopy'] = false;

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin('irre_tutorial', 'Irre', 'IRRE');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'tt_content',
    [
        'tx_irretutorial_1nff_hotels' => [
            'exclude' => true,
            'label' => 'LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xlf:tt_content.tx_irretutorial_1nff_hotels',
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
                ],
            ],
        ],
        'tx_irretutorial_1ncsv_hotels' => [
            'exclude' => true,
            'label' => 'LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xlf:tt_content.tx_irretutorial_1ncsv_hotels',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_irretutorial_1ncsv_hotel',
                'maxitems' => 10,
                'appearance' => [
                    'showSynchronizationLink' => 1,
                    'showAllLocalizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                ],
                'default' => '',
            ],
        ],
        'tx_irretutorial_flexform' => [
            'exclude' => true,
            'label' => 'LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xlf:tt_content.tx_irretutorial_flexform',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => 'FILE:EXT:irre_tutorial/Configuration/FlexForms/tt_content_flexform.xml',
                ],
                'default' => '',
            ],
        ],
    ]
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    '--div--;LLL:EXT:irre_tutorial/Resources/Private/Language/locallang_db.xlf:tt_content.div.irre, tx_irretutorial_1nff_hotels, tx_irretutorial_1ncsv_hotels, tx_irretutorial_flexform'
);

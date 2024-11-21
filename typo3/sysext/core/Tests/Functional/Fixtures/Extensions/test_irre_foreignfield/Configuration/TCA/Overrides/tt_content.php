<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

defined('TYPO3') or die();

// Show copied tt_content records in frontend request
$GLOBALS['TCA']['tt_content']['ctrl']['hideAtCopy'] = false;

ExtensionUtility::registerPlugin('test_irre_foreignfield', 'Test', 'IRRE Foreignfield');

ExtensionManagementUtility::addTCAcolumns(
    'tt_content',
    [
        'tx_testirreforeignfield_hotels' => [
            'exclude' => true,
            'label' => 'LLL:EXT:test_irre_foreignfield/Resources/Private/Language/locallang_db.xlf:tt_content.tx_testirreforeignfield_hotels',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_testirreforeignfield_hotel',
                'foreign_field' => 'parentid',
                'foreign_table_field' => 'parenttable',
                'foreign_match_fields' => [
                    // The flex from field below has an inline relation to hotels, too. The
                    // child table needs a unique string to distinguish the two relations.
                    'parentidentifier' => '1nff.hotels',
                ],
                'maxitems' => 10,
                'appearance' => [
                    'showSynchronizationLink' => 1,
                    'showAllLocalizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                ],
            ],
        ],
        'tx_testirreforeignfield_flexform' => [
            'exclude' => true,
            'label' => 'LLL:EXT:test_irre_foreignfield/Resources/Private/Language/locallang_db.xlf:tt_content.tx_testirreforeignfield_flexform',
            'config' => [
                'type' => 'flex',
                'ds' => 'FILE:EXT:test_irre_foreignfield/Configuration/FlexForms/tt_content_flexform.xml',
                'default' => '',
            ],
        ],
    ]
);

ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    '--div--;LLL:EXT:test_irre_foreignfield/Resources/Private/Language/locallang_db.xlf:tt_content.div.test_irre_foreignfield, tx_testirreforeignfield_hotels, tx_testirreforeignfield_flexform'
);

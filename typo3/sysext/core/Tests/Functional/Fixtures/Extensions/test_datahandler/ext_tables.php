<?php

defined('TYPO3_MODE') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_testdatahandler_element');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'tt_content',
    [
         'tx_testdatahandler_select' => [
            'exclude' => true,
            'label' => 'DataHandler Test Select',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_testdatahandler_element',
                'minitems' => 1,
                'maxitems' => 10,
                'autoSizeMax' => 10,
                'default' => '',
            ],
        ],
        'tx_testdatahandler_select_dynamic' => [
            'exclude' => true,
            'label' => 'DataHandler Test Select',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'items' => [
                    ['predefined label', 'predefined value']
                ],
                'itemsProcFunc' => 'TYPO3\TestDatahandler\Classes\Tca\SelectElementItems->getItems',
                'minitems' => 1,
                'maxitems' => 10,
                'autoSizeMax' => 10,
                'default' => '',
            ],
        ],
         'tx_testdatahandler_group' => [
            'exclude' => true,
            'label' => 'DataHandler Test Group',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_testdatahandler_element',
                'minitems' => 1,
                'maxitems' => 10,
                'autoSizeMax' => 10,
                'default' => '',
            ],
        ],
         'tx_testdatahandler_radio' => [
            'exclude' => true,
            'label' => 'DataHandler Test Radio',
            'config' => [
                'type' => 'radio',
                'items' => [
                    ['predefined label', 'predefined value']
                ],
                'itemsProcFunc' => 'TYPO3\TestDatahandler\Classes\Tca\RadioElementItems->getItems',
                'default' => '',
            ],
        ],
         'tx_testdatahandler_checkbox' => [
             'exclude' => true,
             'label' => 'DataHandler Test Checkbox',
             'config' => [
                 'type' => 'check',
                 'items' => [
                     ['predefined label', 'predefined value']
                 ],
                 'itemsProcFunc' => 'TYPO3\TestDatahandler\Classes\Tca\CheckboxElementItems->getItems',
                 'default' => '',
             ],
         ],
    ]
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    '--div--;DataHandler Test, tx_testdatahandler_select, tx_testdatahandler_group'
);

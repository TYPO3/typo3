<?php

declare(strict_types=1);

defined('TYPO3') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'tt_content',
    [
        'tx_testdatahandler_category' => [
            'exclude' => true,
            'label' => 'DataHandler Test Category',
            'config' => [
                'type' => 'category',
                'relationship' => 'oneToOne',
            ],
        ],
        'tx_testdatahandler_categories' => [
            'exclude' => true,
            'label' => 'DataHandler Test Categories',
            'config' => [
                'type' => 'category',
                'relationship' => 'oneToMany',
            ],
        ],
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
                    ['label' => 'predefined label', 'value' => 'predefined value'],
                ],
                'itemsProcFunc' => \TYPO3\TestDatahandler\Classes\Tca\SelectElementItems::class . '->getItems',
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
                    ['label' => 'predefined label', 'value' => 'predefined value'],
                ],
                'itemsProcFunc' => \TYPO3\TestDatahandler\Classes\Tca\RadioElementItems::class . '->getItems',
                'default' => '',
            ],
        ],
        'tx_testdatahandler_checkbox' => [
            'exclude' => true,
            'label' => 'DataHandler Test Checkbox',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['label' => 'predefined label'],
                ],
                'itemsProcFunc' => \TYPO3\TestDatahandler\Classes\Tca\CheckboxElementItems::class . '->getItems',
                'default' => '',
            ],
        ],
        'tx_testdatahandler_checkbox_with_eval' => [
            'exclude' => true,
            'label' => 'DataHandler Test Checkbox with eval and validation',
            'config' => [
                'type' => 'check',
                'eval' => 'maximumRecordsChecked,maximumRecordsCheckedInPid',
                'validation' => [
                    'maximumRecordsChecked' => 3,
                    'maximumRecordsCheckedInPid' => 2,
                ],
            ],
        ],
        'tx_testdatahandler_input_minvalue' => [
            'exclude' => true,
            'label' => 'Normal input field with min value set to 10',
            'config' => [
                'type' => 'input',
                'min' => 10,
            ],
        ],
        'tx_testdatahandler_input_minvalue_zero' => [
            'exclude' => true,
            'label' => 'Normal input field with min value set to 0',
            'config' => [
                'type' => 'input',
                'min' => 0,
            ],
        ],
        'tx_testdatahandler_text_minvalue' => [
            'exclude' => true,
            'label' => 'Text field with min value set to 10',
            'config' => [
                'type' => 'text',
                'min' => 10,
            ],
        ],
        'tx_testdatahandler_richttext_minvalue' => [
            'exclude' => true,
            'label' => 'Richtext with min value set to 15 (invalid)',
            'config' => [
                'type' => 'text',
                'min' => 15,
                'enableRichtext' => true,
            ],
        ],
    ],
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    '--div--;DataHandler Test,' .
    'tx_testdatahandler_category,tx_testdatahandler_categories, tx_testdatahandler_select,tx_testdatahandler_select_dynamic, tx_testdatahandler_group,' .
    'tx_testdatahandler_radio,tx_testdatahandler_checkbox, tx_testdatahandler_checkbox_with_eval,' .
    'tx_testdatahandler_input_minvalue,tx_testdatahandler_input_minvalue_zero, tx_testdatahandler_text_minvalue,tx_testdatahandler_richttext_minvalue '
);

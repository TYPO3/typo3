<?php
defined('TYPO3_MODE') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_testdatahandler_element');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'tt_content',
     array(
         'tx_testdatahandler_select' => array(
            'exclude' => 1,
            'label' => 'DataHandler Test Select',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_testdatahandler_element',
                'minitems' => 1,
                'maxitems' => 10,
                'autoSizeMax' => '10',
                'default' => '',
            ),
        ),
         'tx_testdatahandler_group' => array(
            'exclude' => 1,
            'label' => 'DataHandler Test Group',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_testdatahandler_element',
                'minitems' => 1,
                'maxitems' => 10,
                'autoSizeMax' => '10',
                'default' => '',
            ),
        ),
         'tx_testdatahandler_radio' => array(
            'exclude' => 1,
            'label' => 'DataHandler Test Radio',
            'config' => array(
                'type' => 'radio',
                'items' => array(
                    array('predefined label', 'predefined value')
                ),
                'itemsProcFunc' => 'TYPO3\TestDatahandler\Classes\Tca\RadioElementItems->getItems',
                'default' => '',
            ),
        ),
    )
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'tt_content',
    '--div--;DataHandler Test, tx_testdatahandler_select, tx_testdatahandler_group'
);

<?php

defined('TYPO3') or die();

if (!isset($GLOBALS['TCA']['fe_users']['ctrl']['type'])) {
    $tca = [
        'ctrl' => [
            'type' => 'tx_extbase_type',
        ],
        'columns' => [
            'tx_extbase_type' => [
                'exclude' => true,
                'label' => 'LLL:EXT:extbase/Resources/Private/Language/locallang_db.xlf:fe_users.tx_extbase_type',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => [
                        ['LLL:EXT:extbase/Resources/Private/Language/locallang_db.xlf:fe_users.tx_extbase_type.0', '0'],
                        ['LLL:EXT:extbase/Resources/Private/Language/locallang_db.xlf:fe_users.tx_extbase_type.Tx_Extbase_Domain_Model_FrontendUser', 'Tx_Extbase_Domain_Model_FrontendUser'],
                    ],
                    'maxitems' => 1,
                    'default' => 0,
                ],
            ],
        ],
        'types' => [
            'Tx_Extbase_Domain_Model_FrontendUser' => $GLOBALS['TCA']['fe_users']['types']['0'],
        ],
    ];
    $GLOBALS['TCA']['fe_users'] = array_replace_recursive($GLOBALS['TCA']['fe_users'], $tca);
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_users', 'tx_extbase_type');
} else {
    $GLOBALS['TCA']['fe_users']['types']['Tx_Extbase_Domain_Model_FrontendUser'] = $GLOBALS['TCA']['fe_users']['types']['0'];
}

<?php

defined('TYPO3') or die();

if (!isset($GLOBALS['TCA']['fe_groups']['ctrl']['type'])) {
    $tca = [
        'ctrl' => [
            'type' => 'tx_extbase_type',
        ],
        'columns' => [
            'tx_extbase_type' => [
                'exclude' => true,
                'label' => 'LLL:EXT:extbase/Resources/Private/Language/locallang_db.xlf:fe_groups.tx_extbase_type',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'items' => [
                        ['LLL:EXT:extbase/Resources/Private/Language/locallang_db.xlf:fe_groups.tx_extbase_type.0', '0'],
                        ['LLL:EXT:extbase/Resources/Private/Language/locallang_db.xlf:fe_groups.tx_extbase_type.Tx_Extbase_Domain_Model_FrontendUserGroup', 'Tx_Extbase_Domain_Model_FrontendUserGroup'],
                    ],
                    'maxitems' => 1,
                    'default' => 0,
                ],
            ],
        ],
        'types' => [
            'Tx_Extbase_Domain_Model_FrontendUserGroup' => $GLOBALS['TCA']['fe_groups']['types']['0'],
        ],
    ];
    $GLOBALS['TCA']['fe_groups'] = array_replace_recursive($GLOBALS['TCA']['fe_groups'], $tca);
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_groups', 'tx_extbase_type');
} else {
    $GLOBALS['TCA']['fe_groups']['types']['Tx_Extbase_Domain_Model_FrontendUserGroup'] = $GLOBALS['TCA']['fe_groups']['types']['0'];
}

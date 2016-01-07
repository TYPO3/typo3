<?php
defined('TYPO3_MODE') or die();

if (is_array($GLOBALS['TCA']['fe_users']['columns']['tx_extbase_type'])) {
    $GLOBALS['TCA']['fe_users']['types']['Tx_BlogExample_Domain_Model_Administrator'] = $GLOBALS['TCA']['fe_users']['types']['0'];
    array_push($GLOBALS['TCA']['fe_users']['columns']['tx_extbase_type']['config']['items'], array('LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:fe_users.tx_extbase_type.Tx_BlogExample_Domain_Model_Administrator', 'Tx_BlogExample_Domain_Model_Administrator'));
}

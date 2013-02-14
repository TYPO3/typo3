<?php
// Make sure that we are executed only from the inside of TYPO3
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
// Prepare new columns for be_users table
$tempColumns = array(
	'tx_openid_openid' => array(
		'exclude' => 0,
		'label' => 'LLL:EXT:openid/locallang_db.xml:be_users.tx_openid_openid',
		'config' => array(
			'type' => 'input',
			'size' => '30',
			// Requirement: unique (BE users are unique in the whole system)
			'eval' => 'trim,nospace,unique'
		)
	)
);
// Add new columns to be_users table
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_users', $tempColumns, FALSE);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('be_users', 'tx_openid_openid;;;;1-1-1', '', 'after:username');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('be_users', 'EXT:' . $_EXTKEY . '/locallang_csh.xml');
// Prepare new columns for fe_users table
$tempColumns['tx_openid_openid']['config']['eval'] = 'trim,nospace,uniqueInPid';
// Add new columns to fe_users table
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_users', $tempColumns, FALSE);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToAllPalettesOfField('fe_users', 'username', 'tx_openid_openid');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('fe_users', 'EXT:' . $_EXTKEY . '/locallang_csh.xml');
// Add field to setup module
$GLOBALS['TYPO3_USER_SETTINGS']['columns']['tx_openid_openid'] = array(
	'type' => 'user',
	'table' => 'be_users',
	'label' => 'LLL:EXT:openid/locallang_db.xml:_MOD_user_setup.tx_openid_openid',
	'csh' => 'tx_openid_openid',
	'userFunc' => 'EXT:openid/class.tx_openid_mod_setup.php:TYPO3\\CMS\\Openid\\OpenidModuleSetup->renderOpenID',
	'access' => 'TYPO3\\CMS\\Openid\\OpenidModuleSetup'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToUserSettings('tx_openid_openid', 'after:password2');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_user_setup', 'EXT:openid/locallang_csh_mod.xml');
?>
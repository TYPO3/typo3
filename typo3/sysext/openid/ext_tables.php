<?php
// Make sure that we are executed only from the inside of TYPO3
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

// Prepare new columns for be_users table
$tempColumns = array (
	'tx_openid_openid' => array (
		'exclude' => 0,
		'label' => 'LLL:EXT:openid/locallang_db.xml:be_users.tx_openid_openid',
		'config' => array (
			'type' => 'input',
			'size' => '30',
			// Requirement: unique (BE users are unique in the whole system)
			'eval' => 'trim,nospace,unique',
		)
	),
);

// Add new columns to be_users table
t3lib_div::loadTCA('be_users');
t3lib_extMgm::addTCAcolumns('be_users', $tempColumns, false);
t3lib_extMgm::addToAllTCAtypes('be_users','tx_openid_openid;;;;1-1-1', '', 'after:username');
t3lib_extMgm::addLLrefForTCAdescr('be_users', 'EXT:' . $_EXTKEY . '/locallang_csh.xml');

// Prepare new columns for fe_users table
$tempColumns['tx_openid_openid']['config']['eval'] = 'trim,nospace,uniqueInPid';

// Add new columns to fe_users table
t3lib_div::loadTCA('fe_users');
t3lib_extMgm::addTCAcolumns('fe_users', $tempColumns, false);
t3lib_extMgm::addFieldsToAllPalettesOfField('fe_users', 'username', 'tx_openid_openid');
t3lib_extMgm::addLLrefForTCAdescr('fe_users', 'EXT:' . $_EXTKEY . '/locallang_csh.xml');

// Add field to setup module
$GLOBALS['TYPO3_USER_SETTINGS']['columns']['tx_openid_openid'] = array(
	'type' => 'user',
	'table' => 'be_users',
	'label' => 'LLL:EXT:openid/locallang_db.xml:_MOD_user_setup.tx_openid_openid',
	'csh' => 'tx_openid_openid',
	'userFunc' => 'EXT:openid/class.tx_openid_mod_setup.php:tx_openid_mod_setup->renderOpenID',
	'access' => 'tx_openid_mod_setup'
);
t3lib_extMgm::addFieldsToUserSettings('tx_openid_openid', 'after:password2');
t3lib_extMgm::addLLrefForTCAdescr('_MOD_user_setup','EXT:openid/locallang_csh_mod.xml');
?>
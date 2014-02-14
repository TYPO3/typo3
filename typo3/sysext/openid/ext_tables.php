<?php
// Make sure that we are executed only from the inside of TYPO3
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
// Prepare new columns for be_users table
$tempColumns = array(
	'tx_openid_openid' => array(
		'exclude' => 0,
		'label' => 'LLL:EXT:openid/locallang_db.xlf:be_users.tx_openid_openid',
		'config' => array(
			'type' => 'input',
			'size' => '30',
			// Requirement: unique (BE users are unique in the whole system)
			'eval' => 'trim,nospace,unique',
			'wizards' => Array(
				'_PADDING' => 2,
				'0' => Array(
					'type' => 'popup',
					'title' => 'Add OpenID',
					'module' => array(
						'name' => 'wizard_openid'
					),
					'icon' => 'EXT:openid/ext_icon.gif',
					'JSopenParams' => ',width=600,height=400,status=0,menubar=0,scrollbars=0',
				)
			),
		)
	)
);
// Add new columns to be_users table
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_users', $tempColumns);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('be_users', 'tx_openid_openid;;;;1-1-1', '', 'after:username');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('be_users', 'EXT:' . $_EXTKEY . '/locallang_csh.xlf');
// Prepare new columns for fe_users table
$tempColumns['tx_openid_openid']['config']['eval'] = 'trim,nospace,uniqueInPid';
// Add new columns to fe_users table
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_users', $tempColumns);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToAllPalettesOfField('fe_users', 'username', 'tx_openid_openid');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('fe_users', 'EXT:' . $_EXTKEY . '/locallang_csh.xlf');
// Add field to setup module
$GLOBALS['TYPO3_USER_SETTINGS']['columns']['tx_openid_openid'] = array(
	'type' => 'user',
	'table' => 'be_users',
	'label' => 'LLL:EXT:openid/locallang_db.xlf:_MOD_user_setup.tx_openid_openid',
	'csh' => 'tx_openid_openid',
	'userFunc' => 'TYPO3\\CMS\\Openid\\OpenidModuleSetup->renderOpenID',
	'access' => 'TYPO3\\CMS\\Openid\\OpenidModuleSetup'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToUserSettings('tx_openid_openid', 'after:password2');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_user_setup', 'EXT:openid/locallang_csh_mod.xlf');

if (TYPO3_MODE === 'BE') {
	// Register wizard
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'wizard_openid',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'wizard/'
	);
}

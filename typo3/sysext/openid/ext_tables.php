<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {

	// Add field to setup module
	$GLOBALS['TYPO3_USER_SETTINGS']['columns']['tx_openid_openid'] = array(
		'type' => 'user',
		'table' => 'be_users',
		'label' => 'LLL:EXT:openid/locallang_db.xlf:_MOD_user_setup.tx_openid_openid',
		'csh' => 'tx_openid_openid',
		'userFunc' => \TYPO3\CMS\Openid\OpenidModuleSetup::class . '->renderOpenID',
		'access' => \TYPO3\CMS\Openid\OpenidModuleSetup::class
	);
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToUserSettings('tx_openid_openid', 'after:password2');
}

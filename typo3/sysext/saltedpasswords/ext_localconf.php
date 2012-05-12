<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
// Form evaluation function for fe_users
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals']['tx_saltedpasswords_eval_fe'] = 'EXT:saltedpasswords/Classes/Evaluation/FrontendEvaluator.php';
// Form evaluation function for be_users
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals']['tx_saltedpasswords_eval_be'] = 'EXT:saltedpasswords/Classes/Evaluation/BackendEvaluator.php';
// Hook for processing "forgotPassword" in felogin
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['password_changed'][] = 'EXT:saltedpasswords/Classes/class.tx_saltedpasswords_div.php:TYPO3\\CMS\\Saltedpasswords\\Utility\\SaltedPasswordsUtility->feloginForgotPasswordHook';
// Registering all available hashes to factory
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/saltedpasswords']['saltMethods'] = array(
	'TYPO3\\CMS\\Saltedpasswords\\Salt\\Md5Salt' => 'EXT:saltedpasswords/Classes/salts/class.tx_saltedpasswords_salts_md5.php:TYPO3\\CMS\\Saltedpasswords\\Salt\\Md5Salt',
	'TYPO3\\CMS\\Saltedpasswords\\Salt\\BlowfishSalt' => 'EXT:saltedpasswords/Classes/salts/class.tx_saltedpasswords_salts_blowfish.php:TYPO3\\CMS\\Saltedpasswords\\Salt\\BlowfishSalt',
	'TYPO3\\CMS\\Saltedpasswords\\Salt\\PhpassSalt' => 'EXT:saltedpasswords/Classes/salts/class.tx_saltedpasswords_salts_phpass.php:TYPO3\\CMS\\Saltedpasswords\\Salt\\PhpassSalt'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService('saltedpasswords', 'auth', 'TYPO3\\CMS\\Saltedpasswords\\SaltedPasswordService', array(
	'title' => 'FE/BE Authentification salted',
	'description' => 'Salting of passwords for Frontend and Backend',
	'subtype' => 'authUserFE,authUserBE',
	'available' => TRUE,
	'priority' => 70,
	// must be higher than tx_sv_auth (50) and rsaauth (60) but lower than OpenID (75)
	'quality' => 70,
	'os' => '',
	'exec' => '',
	'className' => 'TYPO3\\CMS\\Saltedpasswords\\SaltedPasswordService'
));

// Use popup window to refresh login instead of the AJAX relogin:
$TYPO3_CONF_VARS['BE']['showRefreshLoginPopup'] = 1;
// Register bulk update task
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['TYPO3\\CMS\\Saltedpasswords\\Task\\BulkUpdateTask'] = array(
	'extension' => $_EXTKEY,
	'title' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:ext.saltedpasswords.tasks.bulkupdate.name',
	'description' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:ext.saltedpasswords.tasks.bulkupdate.description',
	'additionalFields' => 'TYPO3\\CMS\\Saltedpasswords\\Task\\BulkUpdateFieldProvider'
);
?>
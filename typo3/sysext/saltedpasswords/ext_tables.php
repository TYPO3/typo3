<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
$GLOBALS['TCA']['fe_users']['columns']['password']['config']['max'] = 100;
if (\TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility::isUsageEnabled('FE')) {
	// Get eval field operations methods as array keys
	$operations = array_flip(\TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TCA']['fe_users']['columns']['password']['config']['eval'], TRUE));
	// Remove md5 and temporary password from the list of evaluated methods
	unset($operations['md5'], $operations['password']);
	// Append new methods to have "password" as last operation.
	$operations['tx_saltedpasswords_eval_fe'] = 1;
	$operations['password'] = 1;
	$GLOBALS['TCA']['fe_users']['columns']['password']['config']['eval'] = implode(',', array_keys($operations));
	unset($operations);
}
$GLOBALS['TCA']['be_users']['columns']['password']['config']['max'] = 100;
if (\TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility::isUsageEnabled('BE')) {
	// Get eval field operations methods as array keys
	$operations = array_flip(\TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['TCA']['be_users']['columns']['password']['config']['eval'], TRUE));
	// Remove md5 and temporary password from the list of evaluated methods
	unset($operations['md5'], $operations['password']);
	// Append new methods to have "password" as last operation.
	$operations['tx_saltedpasswords_eval_be'] = 1;
	$operations['password'] = 1;
	$GLOBALS['TCA']['be_users']['columns']['password']['config']['eval'] = implode(',', array_keys($operations));
	unset($operations);
	// Prevent md5 hashing on client side via JS
	$GLOBALS['TYPO3_USER_SETTINGS']['columns']['password']['eval'] = '';
	$GLOBALS['TYPO3_USER_SETTINGS']['columns']['password2']['eval'] = '';
}
// Add context sensitive help (csh) for scheduler task
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_txsaltedpasswords', 'EXT:' . $_EXTKEY . '/locallang_csh_saltedpasswords.xml');
?>
<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}


t3lib_div::loadTCA('fe_users');
$GLOBALS['TCA']['fe_users']['columns']['password']['config']['max'] = 100;

if (tx_saltedpasswords_div::isUsageEnabled('FE')) {

		// Get eval field operations methods as array keys
	$operations = array_flip(t3lib_div::trimExplode(',', $GLOBALS['TCA']['fe_users']['columns']['password']['config']['eval'], TRUE));

		// Remove md5 and temporary password from the list of evaluated methods
	unset($operations['md5'], $operations['password']);

		// Append new methods to have "password" as last operation.
	$operations['tx_saltedpasswords_eval_fe'] = 1;
	$operations['password'] = 1;

	$GLOBALS['TCA']['fe_users']['columns']['password']['config']['eval'] = implode(',', array_keys($operations));
	unset($operations);
}


t3lib_div::loadTCA('be_users');
$GLOBALS['TCA']['be_users']['columns']['password']['config']['max'] = 100;

if (tx_saltedpasswords_div::isUsageEnabled('BE')) {

		// Get eval field operations methods as array keys
	$operations = array_flip(t3lib_div::trimExplode(',', $GLOBALS['TCA']['be_users']['columns']['password']['config']['eval'], TRUE));

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


?>
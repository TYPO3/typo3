<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}


t3lib_div::loadTCA('fe_users');
$GLOBALS['TCA']['fe_users']['columns']['password']['config']['max'] = 60;

if (tx_saltedpasswords_div::isUsageEnabled('FE')) {

		// Get eval field operations methods as array
	$operations = t3lib_div::trimExplode(',', $GLOBALS['TCA']['fe_users']['columns']['password']['config']['eval'], TRUE);

		// Remove md5 from the list of evaluated methods
	$index = array_search('md5', $operations, TRUE);
	unset($operations[$index]);

		// Get position index of password string value
	$index = array_search('password', $operations, TRUE);
	$replacement = array('tx_saltedpasswords_eval_fe', 'password');

		// Remove a portion of the array from index and replace it. To have "password" as last operation.
	array_splice($operations, $index, count($replacement), $replacement);

	$GLOBALS['TCA']['fe_users']['columns']['password']['config']['eval'] = implode($operations, ',');
	unset($operations, $index, $replacement);
}


t3lib_div::loadTCA('be_users');
$GLOBALS['TCA']['be_users']['columns']['password']['config']['max'] = 60;

if (tx_saltedpasswords_div::isUsageEnabled('BE')) {

		// Get eval field operations methods as array
	$operations = t3lib_div::trimExplode(',', $GLOBALS['TCA']['be_users']['columns']['password']['config']['eval'], TRUE);

		// Remove md5 from the list of evaluated methods
	$index = array_search('md5', $operations, TRUE);
	unset($operations[$index]);

		// Get position index of password string value
	$index = array_search('password', $operations, TRUE);
	$replacement = array('tx_saltedpasswords_eval_be', 'password');

		// Remove a portion of the array from index and replace it. To have "password" as last operation.
	array_splice($operations, $index, count($replacement), $replacement);

	$GLOBALS['TCA']['be_users']['columns']['password']['config']['eval'] = implode($operations, ',');
	unset($operations, $index, $replacement);

		// Prevent md5 hashing on client side via JS
	$GLOBALS['TYPO3_USER_SETTINGS']['columns']['password']['eval'] = '';
	$GLOBALS['TYPO3_USER_SETTINGS']['columns']['password2']['eval'] = '';
}


?>
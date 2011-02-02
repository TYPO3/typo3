<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

t3lib_div::loadTCA('fe_users');
$TCA['fe_users']['columns']['password']['config']['max'] = 60;

if (tx_saltedpasswords_div::isUsageEnabled('FE')) {
	$TCA['fe_users']['columns']['password']['config']['eval'] = 'trim,required,tx_saltedpasswords_eval_fe,password';
}

t3lib_div::loadTCA('be_users');
$TCA['be_users']['columns']['password']['config']['max'] = 60;

if (tx_saltedpasswords_div::isUsageEnabled('BE')) {
	$TCA['be_users']['columns']['password']['config']['eval'] = 'trim,required,tx_saltedpasswords_eval_be,password';

		// Prevent md5 hashing on client side via JS
	$GLOBALS['TYPO3_USER_SETTINGS']['columns']['password']['eval'] = '';
	$GLOBALS['TYPO3_USER_SETTINGS']['columns']['password2']['eval'] = '';
}


?>
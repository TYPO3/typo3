<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

	// form evaluation function for fe_users
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals']['tx_saltedpasswords_eval_fe'] = 'EXT:saltedpasswords/classes/eval/class.tx_saltedpasswords_eval_fe.php';

	// form evaluation function for be_users
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals']['tx_saltedpasswords_eval_be'] = 'EXT:saltedpasswords/classes/eval/class.tx_saltedpasswords_eval_be.php';

	// hook for processing "forgotPassword" in felogin
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['felogin']['password_changed'][] = 'EXT:saltedpasswords/classes/class.tx_saltedpasswords_div.php:tx_saltedpasswords_div->feloginForgotPasswordHook';

	// registering all available hashes to factory
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/saltedpasswords']['saltMethods'] = array(
	'tx_saltedpasswords_salts_md5'		=> 'EXT:saltedpasswords/classes/salts/class.tx_saltedpasswords_salts_md5.php:tx_saltedpasswords_salts_md5',
	'tx_saltedpasswords_salts_blowfish'	=> 'EXT:saltedpasswords/classes/salts/class.tx_saltedpasswords_salts_blowfish.php:tx_saltedpasswords_salts_blowfish',
	'tx_saltedpasswords_salts_phpass'	=> 'EXT:saltedpasswords/classes/salts/class.tx_saltedpasswords_salts_phpass.php:tx_saltedpasswords_salts_phpass'
);

t3lib_extMgm::addService(
	'saltedpasswords',
	'auth',
	'tx_saltedpasswords_sv1',
	array(
		'title' => 'FE/BE Authentification salted',
		'description' => 'Salting of passwords for Frontend and Backend',
		'subtype' => 'authUserFE,authUserBE',
		'available' => TRUE,
		'priority' => 70, // must be higher than tx_sv_auth (50) and rsaauth (60) but lower than OpenID (75)
		'quality' => 70,
		'os' => '',
		'exec' => '',
		'classFile' => t3lib_extMgm::extPath('saltedpasswords').'sv1/class.tx_saltedpasswords_sv1.php',
		'className' => 'tx_saltedpasswords_sv1',
	)
);

// Use popup window to refresh login instead of the AJAX relogin:
$TYPO3_CONF_VARS['BE']['showRefreshLoginPopup'] = 1;
?>
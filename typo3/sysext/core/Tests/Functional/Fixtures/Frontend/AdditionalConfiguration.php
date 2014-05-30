<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
// You may add PHP code here, wich is executed on every request after the configuration is loaded.
// The code here should only manipulate TYPO3_CONF_VARS for example to set the database configuration
// dependent to the requested environment.

// $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitConfirmationOfTranslation'] = TRUE;

$GLOBALS['TYPO3_CONF_VARS']['FE']['debug'] = FALSE;

// Register hooks for frontend test
if (TYPO3_MODE === 'FE') {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['postBeUser']['FunctionalTest'] =
		'TYPO3\\CMS\\Core\\Tests\\Functional\\Framework\\Frontend\\Hook\\BackendUserHandler->initialize';
}
?>
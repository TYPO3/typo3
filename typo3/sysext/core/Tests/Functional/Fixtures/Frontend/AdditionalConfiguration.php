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
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_db.php']['queryProcessors']['FunctionalTest'] =
		'TYPO3\\CMS\\Core\\Tests\\Functional\\Framework\\Frontend\\Hook\\DatabaseConnectionWatcher';
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['cObjTypeAndClass']['FunctionalTest'] =
		array('CONTENT', 'TYPO3\\CMS\\Core\\Tests\\Functional\\Framework\\Frontend\\Hook\\ContentObjectRendererWatcher');
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_content.php']['postInit']['FunctionalTest'] =
		'TYPO3\\CMS\\Core\\Tests\\Functional\\Framework\\Frontend\\Hook\\ContentObjectRendererWatcher';
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['isOutputting']['FunctionalTest'] =
		'TYPO3\\CMS\\Core\\Tests\\Functional\\Framework\\Frontend\\Hook\\ContentObjectRendererWatcher->show';
}
?>
<?php
if (!defined('TYPO3_MODE')) 	die('Access denied.');


if (TYPO3_MODE == 'BE') {

		// register top module
	$GLOBALS['TYPO3_CONF_VARS']['typo3/backend.php']['additionalBackendItems'][] = t3lib_extMgm::extPath('opendocs').'registerToolbarItem.php';


		// register AJAX call
	$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['tx_opendocs::backendmenu'] = t3lib_extMgm::extPath('opendocs').'class.tx_opendocs.php:tx_opendocs->renderBackendMenuContents';


		// register menu module if option is wanted
	$_EXTCONF = unserialize($_EXTCONF);
	if ($_EXTCONF['enableModule']) {
		t3lib_extMgm::addModule('user', 'doc', 'after:ws', t3lib_extMgm::extPath($_EXTKEY).'mod/');
	}
}
?>

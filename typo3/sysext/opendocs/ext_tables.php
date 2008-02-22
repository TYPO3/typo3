<?php
if (!defined('TYPO3_MODE')) 	die('Access denied.');


if (TYPO3_MODE == 'BE') {

		// register top module
	$GLOBALS['TYPO3_CONF_VARS']['typo3/backend.php']['additionalBackendItems'][] = t3lib_extMgm::extPath('opendocs').'registerToolbarItem.php';


		// register AJAX calls
	$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['tx_opendocs::backendMenu']   = t3lib_extMgm::extPath('opendocs').'class.tx_opendocs.php:tx_opendocs->renderBackendMenuContents';
	$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['tx_opendocs::closeDocument'] = t3lib_extMgm::extPath('opendocs').'class.tx_opendocs.php:tx_opendocs->closeDocument';

		// register update signal to update the number of open documents
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['updateSignalHook']['tx_opendocs::updateNumber'] = t3lib_extMgm::extPath('opendocs').'class.tx_opendocs.php:tx_opendocs->updateNumberOfOpenDocsHook';


		// register menu module if option is wanted
	$_EXTCONF = unserialize($_EXTCONF);
	if ($_EXTCONF['enableModule']) {
		t3lib_extMgm::addModule('user', 'doc', 'after:ws', t3lib_extMgm::extPath($_EXTKEY).'mod/');
	}
}

?>

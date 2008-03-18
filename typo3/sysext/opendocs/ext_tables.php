<?php

if (!defined('TYPO3_MODE')) 	die('Access denied.');


if(TYPO3_MODE == 'BE') {

	$opendocsPath = t3lib_extMgm::extPath('opendocs');

		// register toolbar item
	$GLOBALS['TYPO3_CONF_VARS']['typo3/backend.php']['additionalBackendItems'][] = $opendocsPath.'registerToolbarItem.php';


		// register AJAX calls
	$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['tx_opendocs::renderMenu']   = $opendocsPath.'class.tx_opendocs.php:tx_opendocs->renderAjax';
	$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['tx_opendocs::closeDocument'] = $opendocsPath.'class.tx_opendocs.php:tx_opendocs->closeDocument';

		// register update signal to update the number of open documents
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['updateSignalHook']['tx_opendocs::updateNumber'] = $opendocsPath.'class.tx_opendocs.php:tx_opendocs->updateNumberOfOpenDocsHook';


		// register menu module if option is wanted
	$_EXTCONF = unserialize($_EXTCONF);
	if($_EXTCONF['enableModule']) {
		t3lib_extMgm::addModule('user', 'doc', 'after:ws', $opendocsPath.'mod/');
	}
}

?>
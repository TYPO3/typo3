<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE == 'BE') {
	$opendocsPath = \TYPO3\CMS\Core\Extension\ExtensionManager::extPath('opendocs');
	// Register toolbar item
	$GLOBALS['TYPO3_CONF_VARS']['typo3/backend.php']['additionalBackendItems'][] = $opendocsPath . 'registerToolbarItem.php';
	// Register AJAX calls
	$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['TYPO3\\CMS\\Opendocs\\Controller\\OpendocsController::renderMenu'] = $opendocsPath . 'class.tx_opendocs.php:TYPO3\\CMS\\Opendocs\\Controller\\OpendocsController->renderAjax';
	$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['TYPO3\\CMS\\Opendocs\\Controller\\OpendocsController::closeDocument'] = $opendocsPath . 'class.tx_opendocs.php:TYPO3\\CMS\\Opendocs\\Controller\\OpendocsController->closeDocument';
	// Register update signal to update the number of open documents
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['updateSignalHook']['TYPO3\\CMS\\Opendocs\\Controller\\OpendocsController::updateNumber'] = $opendocsPath . 'class.tx_opendocs.php:TYPO3\\CMS\\Opendocs\\Controller\\OpendocsController->updateNumberOfOpenDocsHook';
	// Register menu module if option is wanted
	$_EXTCONF = unserialize($_EXTCONF);
	if ($_EXTCONF['enableModule']) {
		\TYPO3\CMS\Core\Extension\ExtensionManager::addModule('user', 'doc', 'after:ws', $opendocsPath . 'mod/');
	}
}
?>
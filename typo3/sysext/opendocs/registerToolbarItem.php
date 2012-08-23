<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE == 'BE') {
	// First include the class file
	include_once \TYPO3\CMS\Core\Extension\ExtensionManager::extPath('opendocs') . 'class.tx_opendocs.php';
	// Now register the class as toolbar item
	$GLOBALS['TYPO3\\CMS\\Backend\\Controller\\BackendController']->addToolbarItem('opendocs', 'TYPO3\\CMS\\Opendocs\\Controller\\OpendocsController');
}
?>
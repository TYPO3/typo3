<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Core\Extension\ExtensionManager::addModulePath('tools_install', \TYPO3\CMS\Core\Extension\ExtensionManager::extPath($_EXTKEY) . 'mod/');
	\TYPO3\CMS\Core\Extension\ExtensionManager::addModule('tools', 'install', '', \TYPO3\CMS\Core\Extension\ExtensionManager::extPath($_EXTKEY) . 'mod/');
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['typo3'][] = 'TYPO3\\CMS\\Install\\Report\\InstallStatusReport';
}
?>
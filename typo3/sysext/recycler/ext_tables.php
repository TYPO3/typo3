<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE == 'BE') {
	// Add module
	\TYPO3\CMS\Core\Extension\ExtensionManager::addModulePath('web_txrecyclerM1', \TYPO3\CMS\Core\Extension\ExtensionManager::extPath($_EXTKEY) . 'mod1/');
	\TYPO3\CMS\Core\Extension\ExtensionManager::addModule('web', 'txrecyclerM1', '', \TYPO3\CMS\Core\Extension\ExtensionManager::extPath($_EXTKEY) . 'mod1/');
}
?>
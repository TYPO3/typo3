<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE == 'BE') {
	\TYPO3\CMS\Core\Extension\ExtensionManager::addModule('tools', 'dbint', '', \TYPO3\CMS\Core\Extension\ExtensionManager::extPath($_EXTKEY) . 'dbint/');
	\TYPO3\CMS\Core\Extension\ExtensionManager::addModule('tools', 'config', '', \TYPO3\CMS\Core\Extension\ExtensionManager::extPath($_EXTKEY) . 'config/');
}
?>
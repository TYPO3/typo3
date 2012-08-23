<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE == 'BE') {
	// Add module
	\TYPO3\CMS\Core\Extension\ExtensionManager::addModule('tools', 'txschedulerM1', '', \TYPO3\CMS\Core\Extension\ExtensionManager::extPath($_EXTKEY) . 'mod1/');
	// Add context sensitive help (csh) to the backend module
	\TYPO3\CMS\Core\Extension\ExtensionManager::addLLrefForTCAdescr('_MOD_tools_txschedulerM1', ('EXT:' . $_EXTKEY) . '/mod1/locallang_csh_scheduler.xml');
}
?>
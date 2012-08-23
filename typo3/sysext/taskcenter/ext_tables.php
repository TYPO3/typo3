<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE == 'BE') {
	\TYPO3\CMS\Core\Extension\ExtensionManager::addModulePath('tools_txtaskcenterM1', \TYPO3\CMS\Core\Extension\ExtensionManager::extPath($_EXTKEY) . 'task/');
	\TYPO3\CMS\Core\Extension\ExtensionManager::addModule('user', 'task', 'top', \TYPO3\CMS\Core\Extension\ExtensionManager::extPath($_EXTKEY) . 'task/');
	$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['Taskcenter::saveCollapseState'] = 'EXT:taskcenter/classes/class.tx_taskcenter_status.php:TYPO3\\CMS\\Taskcenter\\TaskStatus->saveCollapseState';
	$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['Taskcenter::saveSortingState'] = 'EXT:taskcenter/classes/class.tx_taskcenter_status.php:TYPO3\\CMS\\Taskcenter\\TaskStatus->saveSortingState';
}
?>
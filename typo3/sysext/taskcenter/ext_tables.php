<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'tools_txtaskcenterM1',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'task/'
	);
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
		'user',
		'task',
		'top',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'task/'
	);
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler('Taskcenter::saveCollapseState', 'TYPO3\\CMS\\Taskcenter\\TaskStatus->saveCollapseState');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler('Taskcenter::saveSortingState', 'TYPO3\\CMS\\Taskcenter\\TaskStatus->saveSortingState');
}

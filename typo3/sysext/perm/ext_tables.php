<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
		'web',
		'perm',
		'',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'mod1/'
	);
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler('PermissionAjaxController::dispatch', 'TYPO3\\CMS\\Perm\\Controller\\PermissionAjaxController->dispatch');
}

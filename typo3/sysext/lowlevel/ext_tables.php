<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
		'system',
		'dbint',
		'',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'dbint/'
	);
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
		'system',
		'config',
		'',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'config/'
	);
}

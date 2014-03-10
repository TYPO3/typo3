<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE == 'BE') {
	if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('workspaces')) {
		// Register version_click_module
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
			'web_txversionM1',
			\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'cm1/'
		);

		$GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'][] = array(
			'name' => 'TYPO3\\CMS\\Version\\ClickMenu\\VersionClickMenu',
		);
	}
}

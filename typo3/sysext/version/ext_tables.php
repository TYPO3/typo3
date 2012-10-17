<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE == 'BE') {
	if (!\TYPO3\CMS\Core\Extension\ExtensionManager::isLoaded('workspaces')) {
		$GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'][] = array(
			'name' => 'TYPO3\\CMS\\Version\\ClickMenu\\VersionClickMenu',
			'path' => \TYPO3\CMS\Core\Extension\ExtensionManager::extPath($_EXTKEY) . 'class.tx_version_cm1.php'
		);
	}
}
?>
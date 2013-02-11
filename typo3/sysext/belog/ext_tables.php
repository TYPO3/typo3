<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

// Register backend modules, but not in frontend or within upgrade wizards
if (TYPO3_MODE === 'BE' && !(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_INSTALL)) {
	// Module Web->Info->Log
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction(
		'web_info',
		'TYPO3\\CMS\\Belog\\Module\\BackendLogModuleBootstrap',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Classes/Module/BackendLogModuleBootstrap.php',
		'Log'
	);

	// Module Tools->Log
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
		'TYPO3.CMS.' . $_EXTKEY,
		'tools',
		'log',
		'',
		array(
			'Tools' => 'index',
			'WebInfo' => 'index',
		),
		array(
			'access' => 'admin',
			'icon' => 'EXT:belog/ext_icon.gif',
			'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod.xlf',
		)
	);
}
?>
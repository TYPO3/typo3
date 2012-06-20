<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

	// Register backend modules, but not in frontend or within upgrade wizards
if (TYPO3_MODE === 'BE' && !(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_INSTALL)) {
		// Module Web->Info->Log
	t3lib_extMgm::insertModuleFunction(
		'web_info',
		'tx_belog_WebInfo_Bootstrap',
		t3lib_extMgm::extPath($_EXTKEY) . 'Classes/class.tx_belog_webinfo_bootstrap.php',
		'Log'
	);

		// Module Tools->Log
	Tx_Extbase_Utility_Extension::registerModule(
		$_EXTKEY,
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
<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE=='BE')	{
	t3lib_extMgm::addModule('tools','log','',t3lib_extMgm::extPath($_EXTKEY).'mod/');
	t3lib_extMgm::insertModuleFunction(
		'web_info',
		'tx_belog_webinfo',
		t3lib_extMgm::extPath($_EXTKEY).'class.tx_belog_webinfo.php',
		'Log'
	);
}
?>
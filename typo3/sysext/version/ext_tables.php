<?php
if (!defined ("TYPO3_MODE")) 	die ("Access denied.");

/*
if (TYPO3_MODE=="BE")	{
	t3lib_extMgm::insertModuleFunction(
		"web_info",
		"tx_version_modfunc1",
		t3lib_extMgm::extPath($_EXTKEY)."modfunc1/class.tx_version_modfunc1.php",
		"LLL:EXT:version/locallang_db.php:moduleFunction.tx_version_modfunc1"
	);
}
*/

if (TYPO3_MODE=="BE")	{
	$GLOBALS["TBE_MODULES_EXT"]["xMOD_alt_clickmenu"]["extendCMclasses"][]=array(
		"name" => "tx_version_cm1",
		"path" => t3lib_extMgm::extPath($_EXTKEY)."class.tx_version_cm1.php"
	);
}
?>
<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE=='BE')	{
	$GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'][]=array(
		'name' => 'tx_impexp_clickmenu',
		'path' => t3lib_extMgm::extPath($_EXTKEY).'class.tx_impexp_clickmenu.php'
	);
}
?>
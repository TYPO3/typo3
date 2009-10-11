<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE=='BE')	{
	$GLOBALS['TBE_MODULES_EXT']['xMOD_alt_clickmenu']['extendCMclasses'][]=array(
		'name' => 'tx_impexp_clickmenu',
		'path' => t3lib_extMgm::extPath($_EXTKEY).'class.tx_impexp_clickmenu.php'
	);

	t3lib_extMgm::insertModuleFunction(
		'user_task',
		'tx_impexp_modfunc1',
		t3lib_extMgm::extPath($_EXTKEY).'modfunc1/class.tx_impexp_modfunc1.php',
		'LLL:EXT:impexp/app/locallang.xml:moduleFunction.tx_impexp_modfunc1'
	);

	t3lib_extMgm::addLLrefForTCAdescr('xMOD_tx_impexp','EXT:impexp/locallang_csh.xml');
}
?>
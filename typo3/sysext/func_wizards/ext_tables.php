<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE=='BE')	{
	t3lib_extMgm::insertModuleFunction(
		'web_func',
		'tx_funcwizards_webfunc',
		t3lib_extMgm::extPath($_EXTKEY).'class.tx_funcwizards_webfunc.php',
		'LLL:EXT:func_wizards/locallang.php:mod_wizards'
	);
	t3lib_extMgm::addLLrefForTCAdescr('_MOD_web_func','EXT:func_wizards/locallang_csh.xml');
}
?>
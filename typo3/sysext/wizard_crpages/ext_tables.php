<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE=='BE')	{
	t3lib_extMgm::insertModuleFunction(
		'web_func',
		'tx_wizardcrpages_webfunc_2',
		t3lib_extMgm::extPath($_EXTKEY).'class.tx_wizardcrpages_webfunc_2.php',
		'LLL:EXT:wizard_crpages/locallang.php:wiz_crMany',
		'wiz'
	);
	t3lib_extMgm::addLLrefForTCAdescr('_MOD_web_func','EXT:wizard_crpages/locallang_csh.xml');
}
?>
<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE=='BE')	{
	t3lib_extMgm::insertModuleFunction(
		'web_func',
		'tx_wizardsortpages_webfunc_2',
		t3lib_extMgm::extPath($_EXTKEY).'class.tx_wizardsortpages_webfunc_2.php',
		'LLL:EXT:wizard_sortpages/locallang.php:wiz_sort',
		'wiz'
	);
	t3lib_extMgm::addLLrefForTCAdescr('_MOD_web_func','EXT:wizard_sortpages/locallang_csh.xml');
}
?>
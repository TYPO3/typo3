<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE=='BE')	{
	t3lib_extMgm::insertModuleFunction(
		'web_ts',
		'tx_tstemplateobjbrowser',
		t3lib_extMgm::extPath($_EXTKEY).'class.tx_tstemplateobjbrowser.php',
		'LLL:EXT:tstemplate/ts/locallang.xml:objectBrowser'
	);
}
?>
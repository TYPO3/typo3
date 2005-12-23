<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE=='BE')	{
	t3lib_extMgm::insertModuleFunction(
		'web_ts',
		'tx_tstemplateceditor',
		t3lib_extMgm::extPath($_EXTKEY).'class.tx_tstemplateceditor.php',
		'Constant Editor'
	);
}
?>
<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

if (TYPO3_MODE == 'BE') {
		// add module
	t3lib_extMgm::insertModuleFunction(
		'web_info',
		'tx_linkvalidator_modfunc1',
		t3lib_extMgm::extPath('linkvalidator') . 'modfunc1/class.tx_linkvalidator_modfunc1.php',
		'LLL:EXT:linkvalidator/locallang.xml:mod_linkvalidator'
	);
}
?>
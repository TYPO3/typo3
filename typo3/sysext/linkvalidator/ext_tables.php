<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

if (TYPO3_MODE == 'BE') {
		// add module
	t3lib_extMgm::insertModuleFunction(
		'web_info',
		'tx_linkvalidator_modfunc1',
		t3lib_extMgm::extPath('linkvalidator') . 'modreport/class.tx_linkvalidator_modfunc1.php',
		'LLL:EXT:linkvalidator/locallang.xml:mod_linkvalidator'
	);
}

	// Initialize Context Sensitive Help (CSH)
t3lib_extMgm::addLLrefForTCAdescr('linkvalidator', 'EXT:linkvalidator/modreport/locallang_csh.xml');

?>
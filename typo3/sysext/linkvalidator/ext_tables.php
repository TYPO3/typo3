<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

if (TYPO3_MODE == 'BE') {
		// add module
	t3lib_extMgm::insertModuleFunction(
		'web_info',
		'tx_linkvalidator_ModFuncReport',
		t3lib_extMgm::extPath('linkvalidator') . 'modfuncreport/class.tx_linkvalidator_modfuncreport.php',
		'LLL:EXT:linkvalidator/locallang.xml:mod_linkvalidator'
	);
}

	// Initialize Context Sensitive Help (CSH)
t3lib_extMgm::addLLrefForTCAdescr('linkvalidator', 'EXT:linkvalidator/modfuncreport/locallang_csh.xml');

?>
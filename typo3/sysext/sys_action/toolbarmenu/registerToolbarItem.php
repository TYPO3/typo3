<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

if (TYPO3_MODE == 'BE') {
		// first include the class file
	include_once(t3lib_extMgm::extPath('sys_action')
		. 'toolbarmenu/class.tx_sysaction_toolbarmenu.php');

		// now register the class as toolbar item
	$GLOBALS['TYPO3backend']->addToolbarItem(
		'sys_action',
		'tx_sysactionToolbarMenu'
	);
}

?>
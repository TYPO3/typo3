<?php

if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

if (TYPO3_MODE == 'BE') {

		// First include the class file
	include_once(t3lib_extMgm::extPath('opendocs').'class.tx_opendocs.php');

		// Now register the class as toolbar item
	$GLOBALS['TYPO3backend']->addToolbarItem('opendocs', 'tx_opendocs');
}

?>
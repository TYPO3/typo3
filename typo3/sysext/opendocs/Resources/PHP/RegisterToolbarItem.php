<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
	// Now register the class as toolbar item
	$GLOBALS['TYPO3backend']->addToolbarItem('opendocs', 'TYPO3\\CMS\\Opendocs\\Controller\\OpendocsController');
}

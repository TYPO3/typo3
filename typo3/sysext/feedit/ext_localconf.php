<?php

if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

// Register the edit panel view.
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/classes/class.frontendedit.php']['edit'] = 'TYPO3\\CMS\\Feedit\\FrontendEditPanel';

<?php
if (!defined('TYPO3_cliMode')) {
	die('You cannot run this script directly!');
}

// Call the functionality
$adminObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Lowlevel\\AdminCommand');
$adminObj->cli_main($_SERVER['argv']);
?>
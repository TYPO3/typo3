<?php
if (!defined('TYPO3_cliMode')) {
	die('You cannot run this script directly!');
}

// Call the functionality
$cleanerObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Lowlevel\\CleanerCommand');
$cleanerObj->cli_main($_SERVER['argv']);
?>
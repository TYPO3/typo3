<?php
if (!defined('TYPO3_cliMode')) {
	die('You cannot run this script directly!');
}
require_once \TYPO3\CMS\Core\Extension\ExtensionManager::extPath('lowlevel') . 'class.tx_lowlevel_cleaner_core.php';
// Call the functionality
$cleanerObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Integrity\\CleanerCommand');
$cleanerObj->cli_main($_SERVER['argv']);
?>
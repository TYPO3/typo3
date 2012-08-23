<?php
if (!defined('TYPO3_cliMode')) {
	die('You cannot run this script directly!');
}
require_once \TYPO3\CMS\Core\Extension\ExtensionManager::extPath('lowlevel') . 'class.tx_lowlevel_admin_core.php';
// Call the functionality
$adminObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Integrity\\AdminCommand');
$adminObj->cli_main($_SERVER['argv']);
?>
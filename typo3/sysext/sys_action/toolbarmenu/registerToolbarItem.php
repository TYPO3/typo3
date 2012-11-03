<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE == 'BE') {
	// First include the class file
	include_once \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('sys_action') . 'toolbarmenu/class.tx_sysaction_toolbarmenu.php';
	// Now register the class as toolbar item
	$GLOBALS['TYPO3backend']->addToolbarItem('sys_action', 'TYPO3\\CMS\\SysAction\\ActionToolbarMenu');
}
?>
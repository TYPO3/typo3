<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE == 'BE') {
	// First include the class file
	include_once \TYPO3\CMS\Core\Extension\ExtensionManager::extPath('sys_action') . 'toolbarmenu/class.tx_sysaction_toolbarmenu.php';
	// Now register the class as toolbar item
	$GLOBALS['TYPO3\\CMS\\Backend\\Controller\\BackendController']->addToolbarItem('sys_action', 'TYPO3\\CMS\\SysAction\\ActionToolbarMenu');
}
?>
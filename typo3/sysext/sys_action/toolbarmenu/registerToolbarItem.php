<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE === 'BE') {
	// Now register the class as toolbar item
	$GLOBALS['TYPO3backend']->addToolbarItem('sys_action', 'TYPO3\\CMS\\SysAction\\ActionToolbarMenu');
}

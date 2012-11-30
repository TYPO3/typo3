<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

// Register "switchableControllerActions" manually because it exists no plugin or module for sys_note
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions']['SysNote']['modules']['Note']['controllers'] = array(
	'Note' => array(
		'actions' => array('list')
	)
);

// Hook into the list module
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['recordlist/mod1/index.php']['drawFooterHook'][$_EXTKEY] = 'EXT:' . $_EXTKEY . '/Classes/Hook/RecordListHook.php:TYPO3\\CMS\\SysNote\\Hook\\RecordListHook->render';
// Hook into the page module
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/db_layout.php']['drawFooterHook'][$_EXTKEY] = 'EXT:' . $_EXTKEY . '/Classes/Hook/PageHook.php:TYPO3\\CMS\\SysNote\\Hook\\PageHook->render';
// Hook into the info module
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/web_info/class.tx_cms_webinfo.php']['drawFooterHook'][$_EXTKEY] = 'EXT:' . $_EXTKEY . '/Classes/Hook/InfoModuleHook.php:TYPO3\\CMS\\SysNote\\Hook\\InfoModuleHook->render';
?>
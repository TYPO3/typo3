<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
// Hook into the list module
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['recordlist/mod1/index.php']['drawFooterHook'][$_EXTKEY] = ('EXT:' . $_EXTKEY) . '/Classes/Hooks/RecordList.php:Tx_Sysnote_Hooks_RecordList->render';
// Hook into the page module
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/db_layout.php']['drawFooterHook'][$_EXTKEY] = ('EXT:' . $_EXTKEY) . '/Classes/Hooks/Page.php:Tx_Sysnote_Hooks_Page->render';
// Hook into the info module
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/web_info/class.tx_cms_webinfo.php']['drawFooterHook'][$_EXTKEY] = ('EXT:' . $_EXTKEY) . '/Classes/Hooks/InfoModule.php:Tx_Sysnote_Hooks_InfoModule->render';
?>
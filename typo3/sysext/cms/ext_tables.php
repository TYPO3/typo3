<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE == 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule('web', 'layout', 'top', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'layout/');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_web_layout', 'EXT:cms/locallang_csh_weblayout.xlf');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_web_info', 'EXT:cms/locallang_csh_webinfo.xlf');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction('web_info', 'tx_cms_webinfo_page', NULL, 'LLL:EXT:cms/locallang_tca.xlf:mod_tx_cms_webinfo_page');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction('web_info', 'tx_cms_webinfo_lang', NULL, 'LLL:EXT:cms/locallang_tca.xlf:mod_tx_cms_webinfo_lang');
}
// Add allowed records to pages:
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('pages_language_overlay,tt_content,sys_template,sys_domain,backend_layout');

if (!function_exists('user_sortPluginList')) {
	function user_sortPluginList(array &$parameters) {
		usort(
			$parameters['items'],
			function ($item1, $item2) {
				return strcasecmp($GLOBALS['LANG']->sL($item1[0]), $GLOBALS['LANG']->sL($item2[0]));
			}
		);
	}
}
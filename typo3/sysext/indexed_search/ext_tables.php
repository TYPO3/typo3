<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(array('LLL:EXT:indexed_search/locallang.php:mod_indexed_search', $_EXTKEY));
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY] = 'layout,select_key,pages';
// Registers the Extbase plugin to be listed in the Backend.
if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('extbase')) {
	$extensionName = \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($_EXTKEY);
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin($_EXTKEY, 'Pi2', 'Indexed Search (experimental)');
	$pluginSignature = strtolower($extensionName) . '_pi2';
	$TCA['tt_content']['types']['list']['subtypes_excludelist'][$pluginSignature] = 'layout,select_key,pages,recursive';
}
if (TYPO3_MODE == 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule('tools', 'isearch', 'after:log', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'mod/');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction('web_info', 'TYPO3\\CMS\\IndexedSearch\\Controller\\IndexedPagesController', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'modfunc1/class.tx_indexedsearch_modfunc1.php', 'LLL:EXT:indexed_search/locallang.php:mod_indexed_search');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction('web_info', 'TYPO3\\CMS\\IndexedSearch\\Controller\\IndexingStatisticsController', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'modfunc2/class.tx_indexedsearch_modfunc2.php', 'LLL:EXT:indexed_search/locallang.php:mod2_indexed_search');
}
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('index_config');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('index_config', 'EXT:indexed_search/locallang_csh_indexcfg.xml');
?>
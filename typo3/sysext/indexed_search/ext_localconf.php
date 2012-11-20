<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPItoST43($_EXTKEY);
if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('extbase')) {
	// Configure the Extbase Plugin
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin($_EXTKEY, 'Pi2', array('Search' => 'form,search'), array('Search' => 'form,search'));
}
// Attach to hooks:
$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageIndexing'][] = 'TYPO3\\CMS\\IndexedSearch\\Indexer';
$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['headerNoCache']['tx_indexedsearch'] = '&TYPO3\\CMS\\IndexedSearch\\Hook\\TypoScriptFrontendHook->headerNoCache';
// Register with "crawler" extension:
$TYPO3_CONF_VARS['EXTCONF']['crawler']['procInstructions']['tx_indexedsearch_reindex'] = 'Re-indexing';
$TYPO3_CONF_VARS['EXTCONF']['crawler']['cli_hooks']['tx_indexedsearch_crawl'] = '&TYPO3\\CMS\\IndexedSearch\\Hook\\CrawlerHook';
// Register with TCEmain:
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['tx_indexedsearch'] = '&TYPO3\\CMS\\IndexedSearch\\Hook\\CrawlerHook';
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['tx_indexedsearch'] = '&TYPO3\\CMS\\IndexedSearch\\Hook\\CrawlerHook';
// Configure default document parsers:
$TYPO3_CONF_VARS['EXTCONF']['indexed_search']['external_parsers'] = array(
	'pdf' => '&TYPO3\\CMS\\IndexedSearch\\FileContentParser',
	'doc' => '&TYPO3\\CMS\\IndexedSearch\\FileContentParser',
	'pps' => '&TYPO3\\CMS\\IndexedSearch\\FileContentParser',
	'ppt' => '&TYPO3\\CMS\\IndexedSearch\\FileContentParser',
	'xls' => '&TYPO3\\CMS\\IndexedSearch\\FileContentParser',
	'sxc' => '&TYPO3\\CMS\\IndexedSearch\\FileContentParser',
	'sxi' => '&TYPO3\\CMS\\IndexedSearch\\FileContentParser',
	'sxw' => '&TYPO3\\CMS\\IndexedSearch\\FileContentParser',
	'ods' => '&TYPO3\\CMS\\IndexedSearch\\FileContentParser',
	'odp' => '&TYPO3\\CMS\\IndexedSearch\\FileContentParser',
	'odt' => '&TYPO3\\CMS\\IndexedSearch\\FileContentParser',
	'rtf' => '&TYPO3\\CMS\\IndexedSearch\\FileContentParser',
	'txt' => '&TYPO3\\CMS\\IndexedSearch\\FileContentParser',
	'html' => '&TYPO3\\CMS\\IndexedSearch\\FileContentParser',
	'htm' => '&TYPO3\\CMS\\IndexedSearch\\FileContentParser',
	'csv' => '&TYPO3\\CMS\\IndexedSearch\\FileContentParser',
	'xml' => '&TYPO3\\CMS\\IndexedSearch\\FileContentParser',
	'jpg' => '&TYPO3\\CMS\\IndexedSearch\\FileContentParser',
	'jpeg' => '&TYPO3\\CMS\\IndexedSearch\\FileContentParser',
	'tif' => '&TYPO3\\CMS\\IndexedSearch\\FileContentParser'
);
$TYPO3_CONF_VARS['EXTCONF']['indexed_search']['use_tables'] = 'index_phash,index_fulltext,index_rel,index_words,index_section,index_grlist,index_stat_search,index_stat_word,index_debug,index_config';
// unserializing the configuration so we can use it here:
$_EXTCONF = unserialize($_EXTCONF);
// Use the advanced doubleMetaphone parser instead of the internal one (usage of metaphone parsers is generally disabled by default)
if (isset($_EXTCONF['enableMetaphoneSearch']) && intval($_EXTCONF['enableMetaphoneSearch']) == 2) {
	$TYPO3_CONF_VARS['EXTCONF']['indexed_search']['metaphone'] = '&TYPO3\\CMS\\IndexedSearch\\Utility\\DoubleMetaPhoneUtility';
}
?>
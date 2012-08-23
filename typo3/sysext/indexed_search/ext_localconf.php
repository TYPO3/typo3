<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
\TYPO3\CMS\Core\Extension\ExtensionManager::addPItoST43($_EXTKEY);
if (\TYPO3\CMS\Core\Extension\ExtensionManager::isLoaded('extbase')) {
	// Configure the Extbase Plugin
	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin($_EXTKEY, 'Pi2', array('Search' => 'form,search'), array('Search' => 'form,search'));
}
// Attach to hooks:
$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageIndexing'][] = 'EXT:indexed_search/class.indexer.php:tx_indexedsearch_indexer';
$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['headerNoCache']['tx_indexedsearch'] = 'EXT:indexed_search/hooks/class.tx_indexedsearch_tslib_fe_hook.php:&tx_indexedsearch_tslib_fe_hook->headerNoCache';
// Register with "crawler" extension:
$TYPO3_CONF_VARS['EXTCONF']['crawler']['procInstructions']['tx_indexedsearch_reindex'] = 'Re-indexing';
$TYPO3_CONF_VARS['EXTCONF']['crawler']['cli_hooks']['tx_indexedsearch_crawl'] = 'EXT:indexed_search/class.crawler.php:&tx_indexedsearch_crawler';
// Register with TCEmain:
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['tx_indexedsearch'] = 'EXT:indexed_search/class.crawler.php:&tx_indexedsearch_crawler';
$TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['tx_indexedsearch'] = 'EXT:indexed_search/class.crawler.php:&tx_indexedsearch_crawler';
// Configure default document parsers:
$TYPO3_CONF_VARS['EXTCONF']['indexed_search']['external_parsers'] = array(
	'pdf' => 'EXT:indexed_search/class.external_parser.php:&TYPO3\\CMS\\IndexedSearch\\FileContentParser',
	'doc' => 'EXT:indexed_search/class.external_parser.php:&TYPO3\\CMS\\IndexedSearch\\FileContentParser',
	'pps' => 'EXT:indexed_search/class.external_parser.php:&TYPO3\\CMS\\IndexedSearch\\FileContentParser',
	'ppt' => 'EXT:indexed_search/class.external_parser.php:&TYPO3\\CMS\\IndexedSearch\\FileContentParser',
	'xls' => 'EXT:indexed_search/class.external_parser.php:&TYPO3\\CMS\\IndexedSearch\\FileContentParser',
	'sxc' => 'EXT:indexed_search/class.external_parser.php:&TYPO3\\CMS\\IndexedSearch\\FileContentParser',
	'sxi' => 'EXT:indexed_search/class.external_parser.php:&TYPO3\\CMS\\IndexedSearch\\FileContentParser',
	'sxw' => 'EXT:indexed_search/class.external_parser.php:&TYPO3\\CMS\\IndexedSearch\\FileContentParser',
	'ods' => 'EXT:indexed_search/class.external_parser.php:&TYPO3\\CMS\\IndexedSearch\\FileContentParser',
	'odp' => 'EXT:indexed_search/class.external_parser.php:&TYPO3\\CMS\\IndexedSearch\\FileContentParser',
	'odt' => 'EXT:indexed_search/class.external_parser.php:&TYPO3\\CMS\\IndexedSearch\\FileContentParser',
	'rtf' => 'EXT:indexed_search/class.external_parser.php:&TYPO3\\CMS\\IndexedSearch\\FileContentParser',
	'txt' => 'EXT:indexed_search/class.external_parser.php:&TYPO3\\CMS\\IndexedSearch\\FileContentParser',
	'html' => 'EXT:indexed_search/class.external_parser.php:&TYPO3\\CMS\\IndexedSearch\\FileContentParser',
	'htm' => 'EXT:indexed_search/class.external_parser.php:&TYPO3\\CMS\\IndexedSearch\\FileContentParser',
	'csv' => 'EXT:indexed_search/class.external_parser.php:&TYPO3\\CMS\\IndexedSearch\\FileContentParser',
	'xml' => 'EXT:indexed_search/class.external_parser.php:&TYPO3\\CMS\\IndexedSearch\\FileContentParser',
	'jpg' => 'EXT:indexed_search/class.external_parser.php:&TYPO3\\CMS\\IndexedSearch\\FileContentParser',
	'jpeg' => 'EXT:indexed_search/class.external_parser.php:&TYPO3\\CMS\\IndexedSearch\\FileContentParser',
	'tif' => 'EXT:indexed_search/class.external_parser.php:&TYPO3\\CMS\\IndexedSearch\\FileContentParser'
);
$TYPO3_CONF_VARS['EXTCONF']['indexed_search']['use_tables'] = 'index_phash,index_fulltext,index_rel,index_words,index_section,index_grlist,index_stat_search,index_stat_word,index_debug,index_config';
// unserializing the configuration so we can use it here:
$_EXTCONF = unserialize($_EXTCONF);
// Use the advanced doubleMetaphone parser instead of the internal one (usage of metaphone parsers is generally disabled by default)
if (isset($_EXTCONF['enableMetaphoneSearch']) && intval($_EXTCONF['enableMetaphoneSearch']) == 2) {
	$TYPO3_CONF_VARS['EXTCONF']['indexed_search']['metaphone'] = 'EXT:indexed_search/class.doublemetaphone.php:&TYPO3\\CMS\\IndexedSearch\\Utility\\DoubleMetaPhoneUtility';
}
?>
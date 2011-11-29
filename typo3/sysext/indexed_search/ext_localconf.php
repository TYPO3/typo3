<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

t3lib_extMgm::addPItoST43($_EXTKEY);

if (t3lib_extMgm::isLoaded('extbase')) {
	// Configure the Extbase Plugin
	Tx_Extbase_Utility_Extension::configurePlugin(
		$_EXTKEY, 'Pi2',
			// array holding the controller-action-combinations that are accessible
		array('Search' => 'form,search'),
			// array of non-cachable controller-action-combinations (they must already be enabled above)
		array('Search' => 'form,search')
	);
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
	'pdf' => 'EXT:indexed_search/class.external_parser.php:&tx_indexed_search_extparse',
	'doc' => 'EXT:indexed_search/class.external_parser.php:&tx_indexed_search_extparse',
	'pps' => 'EXT:indexed_search/class.external_parser.php:&tx_indexed_search_extparse',
	'ppt' => 'EXT:indexed_search/class.external_parser.php:&tx_indexed_search_extparse',
	'xls' => 'EXT:indexed_search/class.external_parser.php:&tx_indexed_search_extparse',
	'sxc' => 'EXT:indexed_search/class.external_parser.php:&tx_indexed_search_extparse',
	'sxi' => 'EXT:indexed_search/class.external_parser.php:&tx_indexed_search_extparse',
	'sxw' => 'EXT:indexed_search/class.external_parser.php:&tx_indexed_search_extparse',
	'ods' => 'EXT:indexed_search/class.external_parser.php:&tx_indexed_search_extparse',
	'odp' => 'EXT:indexed_search/class.external_parser.php:&tx_indexed_search_extparse',
	'odt' => 'EXT:indexed_search/class.external_parser.php:&tx_indexed_search_extparse',
	'rtf' => 'EXT:indexed_search/class.external_parser.php:&tx_indexed_search_extparse',
	'txt' => 'EXT:indexed_search/class.external_parser.php:&tx_indexed_search_extparse',
	'html' => 'EXT:indexed_search/class.external_parser.php:&tx_indexed_search_extparse',
	'htm' => 'EXT:indexed_search/class.external_parser.php:&tx_indexed_search_extparse',
	'csv' => 'EXT:indexed_search/class.external_parser.php:&tx_indexed_search_extparse',
	'xml' => 'EXT:indexed_search/class.external_parser.php:&tx_indexed_search_extparse',
	'jpg' => 'EXT:indexed_search/class.external_parser.php:&tx_indexed_search_extparse',
	'jpeg' => 'EXT:indexed_search/class.external_parser.php:&tx_indexed_search_extparse',
	'tif' => 'EXT:indexed_search/class.external_parser.php:&tx_indexed_search_extparse',
);

$TYPO3_CONF_VARS['EXTCONF']['indexed_search']['use_tables'] = 'index_phash,index_fulltext,index_rel,index_words,index_section,index_grlist,index_stat_search,index_stat_word,index_debug,index_config';

  // unserializing the configuration so we can use it here:
$_EXTCONF = unserialize($_EXTCONF);

	// Use the advanced doubleMetaphone parser instead of the internal one (usage of metaphone parsers is generally disabled by default)
if (isset($_EXTCONF['enableMetaphoneSearch']) && intval($_EXTCONF['enableMetaphoneSearch'])==2) {
	$TYPO3_CONF_VARS['EXTCONF']['indexed_search']['metaphone'] = 'EXT:indexed_search/class.doublemetaphone.php:&user_DoubleMetaPhone';
}





	// EXAMPLE configuration of hooks:
/*
$TYPO3_CONF_VARS['EXTCONF']['indexed_search']['pi1_hooks'] = array (
	'initialize_postProc' => 'EXT:indexed_search/example/class.pihook.php:&tx_indexedsearch_pihook',
	'getResultRows' => 'EXT:indexed_search/example/class.pihook.php:&tx_indexedsearch_pihook',
	'printResultRow' => 'EXT:indexed_search/example/class.pihook.php:&tx_indexedsearch_pihook',
	'prepareResultRowTemplateData_postProc' => 'EXT:indexed_search/example/class.pihook.php:&tx_indexedsearch_pihook',
);
*/

	// EXAMPLE of adding fields to root line:
#$TYPO3_CONF_VARS['EXTCONF']['indexed_search']['addRootLineFields']['level3'] = 3;


	// Example of crawlerhook (see also ext_tables.php!)
/*
	$TYPO3_CONF_VARS['EXTCONF']['indexed_search']['crawler']['tx_myext_example1'] = 'EXT:indexed_search/example/class.crawlerhook.php:&tx_indexedsearch_crawlerhook';
*/
?>

<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
// Configure hook to query the fulltext index
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['pi1_hooks']['getResultRows_SQLpointer'] = '&TYPO3\CMS\IndexedSearch\Hook\MysqlFulltextIndexHook';
// Use all index_* tables except "index_rel" and "index_words"
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['indexed_search']['use_tables'] = 'index_phash,index_fulltext,index_section,index_grlist,index_stat_search,index_stat_word,index_debug,index_config';

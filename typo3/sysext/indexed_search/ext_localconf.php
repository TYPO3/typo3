<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

t3lib_extMgm::addPItoST43($_EXTKEY);

t3lib_extMgm::addTypoScript($_EXTKEY,'editorcfg','
	tt_content.CSS_editor.ch.tx_indexedsearch = < plugin.tx_indexedsearch.CSS_editor
',43);


$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['pageIndexing'][] = 'EXT:indexed_search/class.indexer.php:tx_indexedsearch_indexer';
?>
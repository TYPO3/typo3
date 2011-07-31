<?php
/*
 * Register necessary class names with autoloader
 */
$extensionPath = t3lib_extMgm::extPath('indexed_search');
return array(
	'tx_indexedsearch_indexer' => $extensionPath . 'class.indexer.php'
);
?>
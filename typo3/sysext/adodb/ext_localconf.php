<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

t3lib_extMgm::addPItoST43($_EXTKEY,'pi1/class.tx_adodb_pi1.php','_pi1','',1);

require_once (t3lib_extMgm::extPath('adodb').'class.tx_adodb_tceforms.php');

	// Register as a data source application if the extension datasources is loaded:
if (t3lib_extMgm::isLoaded ('datasources')) {
	require_once (t3lib_extMgm::extPath('datasources').'class.tx_datasources_main.php');
	$dataSourcesMainObj = &t3lib_div::getUserObj('EXT:datasources/class.tx_datasources_main.php:&tx_datasources_main');
	$dataSourcesMainObj->registerApplication ('ADO DB', 'adodb');
}


?>
<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

require_once (t3lib_extMgm::extPath('adodb').'class.tx_adodb_tceforms.php');

	// Register as a data source application if the extension datasources is loaded:
if (t3lib_extMgm::isLoaded ('datasources')) {
	require_once (t3lib_extMgm::extPath('datasources').'class.tx_datasources_main.php');
	$dataSourcesMainObj = t3lib_div::getUserObj('EXT:datasources/class.tx_datasources_main.php:&tx_datasources_main');
	$dataSourcesMainObj->registerApplication ('ADOdb', 'adodb');
}

?>
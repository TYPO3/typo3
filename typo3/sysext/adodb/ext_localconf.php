<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
require_once \TYPO3\CMS\Core\Extension\ExtensionManager::extPath('adodb') . 'class.tx_adodb_tceforms.php';
// Register as a data source application if the extension datasources is loaded:
if (\TYPO3\CMS\Core\Extension\ExtensionManager::isLoaded('datasources')) {
	require_once \TYPO3\CMS\Core\Extension\ExtensionManager::extPath('datasources') . 'class.tx_datasources_main.php';
	$dataSourcesMainObj = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj('EXT:datasources/class.tx_datasources_main.php:&tx_datasources_main');
	$dataSourcesMainObj->registerApplication('ADOdb', 'adodb');
}
?>
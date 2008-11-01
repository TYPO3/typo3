<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['configArrayPostProc'][$_EXTKEY] = 'EXT:simulatestatic/class.tx_simulatestatic.php:&tx_simulatestatic->hookInitConfig';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tstemplate.php']['linkData-PostProc'][$_EXTKEY] = 'EXT:simulatestatic/class.tx_simulatestatic.php:&tx_simulatestatic->hookLinkDataPostProc';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkAlternativeIdMethods-PostProc'][$_EXTKEY] = 'EXT:simulatestatic/class.tx_simulatestatic.php:&tx_simulatestatic->hookCheckAlternativeIDMethods';
?>
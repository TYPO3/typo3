<?php
/* $Id: ext_localconf.php 7251 2010-04-06 18:57:45Z francois $ */

if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

	// register the hook to actually do the work within TCEmain
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][] = t3lib_extMgm::extPath('version', 'class.tx_version_tcemain.php:&tx_version_tcemain');


	// Register the autopublishing task
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_version_tasks_AutoPublish'] = array(
	'extension'        => $_EXTKEY,
	'title'            => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:autopublishTask.name',
	'description'      => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:autopublishTask.description'
);
?>

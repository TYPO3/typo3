<?php

if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

	// Register the Scheduler as a possible key for CLI calls
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys'][$_EXTKEY] = array(
	'EXT:' . $_EXTKEY . '/cli/scheduler_cli_dispatch.php', '_CLI_scheduler'
);

	// Get the extensions's configuration
$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['scheduler']);

	// If sample tasks should be shown,
	// register information for the test and sleep tasks
if (!empty($extConf['showSampleTasks'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_scheduler_TestTask'] = array(
		'extension'        => $_EXTKEY,
		'title'            => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:testTask.name',
		'description'      => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:testTask.description',
		'additionalFields' => 'tx_scheduler_TestTask_AdditionalFieldProvider'
	);
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_scheduler_SleepTask'] = array(
		'extension'        => $_EXTKEY,
		'title'            => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:sleepTask.name',
		'description'      => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:sleepTask.description',
		'additionalFields' => 'tx_scheduler_SleepTask_AdditionalFieldProvider'
	);
}

	// Add caching framework garbage collection task
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_scheduler_CachingFrameworkGarbageCollection'] = array(
	'extension'        => $_EXTKEY,
	'title'            => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:cachingFrameworkGarbageCollection.name',
	'description'      => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:cachingFrameworkGarbageCollection.description',
	'additionalFields' => 'tx_scheduler_CachingFrameworkGarbageCollection_AdditionalFieldProvider',
);

	// Add recycler directory cleanup task. Windows is not supported
	// because "filectime" does not change after moving a file
if (TYPO3_OS != 'WIN') {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_scheduler_RecyclerGarbageCollection'] = array(
		'extension'        => $_EXTKEY,
		'title'            => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:recyclerGarbageCollection.name',
		'description'      => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:recyclerGarbageCollection.description',
		'additionalFields' => 'tx_scheduler_RecyclerGarbageCollection_AdditionalFieldProvider',
	);
}

	// Add table garbage collection task
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_scheduler_TableGarbageCollection'] = array(
	'extension'        => $_EXTKEY,
	'title'            => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:tableGarbageCollection.name',
	'description'      => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:tableGarbageCollection.description',
	'additionalFields' => 'tx_scheduler_TableGarbageCollection_AdditionalFieldProvider',
);
	// Initialize option array of table garbage collection task if not already done by some other extension or localconf.php
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_scheduler_TableGarbageCollection']['options'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_scheduler_TableGarbageCollection']['options'] = array();
}
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_scheduler_TableGarbageCollection']['options']['tables'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_scheduler_TableGarbageCollection']['options']['tables'] = array();
}
	// Register sys_log table in table garbage collection task
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_scheduler_TableGarbageCollection']['options']['tables']['sys_log'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_scheduler_TableGarbageCollection']['options']['tables']['sys_log'] = array(
		'dateField' => 'tstamp',
		'expirePeriod' => 180,
	);
}
	// Register cache_pages table in table garbage collection task
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_scheduler_TableGarbageCollection']['options']['tables']['cache_pages'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_scheduler_TableGarbageCollection']['options']['tables']['cache_pages'] = array(
		'expireField' => 'expires',
	);
}
?>
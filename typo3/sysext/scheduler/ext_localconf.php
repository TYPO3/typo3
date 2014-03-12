<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
// Register the Scheduler as a possible key for CLI calls
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys'][$_EXTKEY] = array(
	'EXT:' . $_EXTKEY . '/cli/scheduler_cli_dispatch.php',
	'_CLI_scheduler'
);
// Get the extensions's configuration
$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['scheduler']);
// If sample tasks should be shown,
// register information for the test and sleep tasks
if (!empty($extConf['showSampleTasks'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['TYPO3\\CMS\\Scheduler\\Example\\TestTask'] = array(
		'extension' => $_EXTKEY,
		'title' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xlf:testTask.name',
		'description' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xlf:testTask.description',
		'additionalFields' => 'TYPO3\\CMS\\Scheduler\\Example\\TestTaskAdditionalFieldProvider'
	);
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['TYPO3\\CMS\\Scheduler\\Example\\SleepTask'] = array(
		'extension' => $_EXTKEY,
		'title' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xlf:sleepTask.name',
		'description' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xlf:sleepTask.description',
		'additionalFields' => 'TYPO3\\CMS\\Scheduler\\Example\\SleepTaskAdditionalFieldProvider'
	);
}
// Add caching framework garbage collection task
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['TYPO3\\CMS\\Scheduler\\Task\\CachingFrameworkGarbageCollectionTask'] = array(
	'extension' => $_EXTKEY,
	'title' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xlf:cachingFrameworkGarbageCollection.name',
	'description' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xlf:cachingFrameworkGarbageCollection.description',
	'additionalFields' => 'TYPO3\\CMS\\Scheduler\\Task\\CachingFrameworkGarbageCollectionAdditionalFieldProvider'
);
// Add file indexing task
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['TYPO3\\CMS\\Scheduler\\Task\\FileIndexingTask'] = array(
	'extension' => $_EXTKEY,
	'title' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xlf:fileIndexing.name',
	'description' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xlf:fileIndexing.description'
);

// Add task to index file in a storage
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['TYPO3\\CMS\\Scheduler\\Task\\FileStorageIndexingTask'] = array(
	'extension' => $_EXTKEY,
	'title' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xlf:fileStorageIndexing.name',
	'description' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xlf:fileStorageIndexing.description',
	'additionalFields' => 'TYPO3\\CMS\\Scheduler\\Task\\FileStorageIndexingAdditionalFieldProvider'
);

// Add task for extracting metadata from files in a storage
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['TYPO3\\CMS\\Scheduler\\Task\\FileStorageExtractionTask'] = array(
	'extension' => $_EXTKEY,
	'title' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xlf:fileStorageExtraction.name',
	'description' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xlf:fileStorageExtraction.description',
	'additionalFields' => 'TYPO3\\CMS\\Scheduler\\Task\\FileStorageExtractionAdditionalFieldProvider'

);

// Add recycler directory cleanup task. Windows is not supported
// because "filectime" does not change after moving a file
if (TYPO3_OS !== 'WIN') {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['TYPO3\\CMS\\Scheduler\\Task\\RecyclerGarbageCollectionTask'] = array(
		'extension' => $_EXTKEY,
		'title' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xlf:recyclerGarbageCollection.name',
		'description' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xlf:recyclerGarbageCollection.description',
		'additionalFields' => 'TYPO3\\CMS\\Scheduler\\Task\\RecyclerGarbageCollectionAdditionalFieldProvider'
	);
}

// Add table garbage collection task
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['TYPO3\\CMS\\Scheduler\\Task\\TableGarbageCollectionTask'] = array(
	'extension' => $_EXTKEY,
	'title' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xlf:tableGarbageCollection.name',
	'description' => 'LLL:EXT:' . $_EXTKEY . '/locallang.xlf:tableGarbageCollection.description',
	'additionalFields' => 'TYPO3\\CMS\\Scheduler\\Task\\TableGarbageCollectionAdditionalFieldProvider'
);
// Initialize option array of table garbage collection task if not already done by some other extension or localconf.php
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['TYPO3\\CMS\\Scheduler\\Task\\TableGarbageCollectionTask']['options'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['TYPO3\\CMS\\Scheduler\\Task\\TableGarbageCollectionTask']['options'] = array();
}
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['TYPO3\\CMS\\Scheduler\\Task\\TableGarbageCollectionTask']['options']['tables'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['TYPO3\\CMS\\Scheduler\\Task\\TableGarbageCollectionTask']['options']['tables'] = array();
}
// Register sys_log and sys_history table in table garbage collection task
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['TYPO3\\CMS\\Scheduler\\Task\\TableGarbageCollectionTask']['options']['tables']['sys_log'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['TYPO3\\CMS\\Scheduler\\Task\\TableGarbageCollectionTask']['options']['tables']['sys_log'] = array(
		'dateField' => 'tstamp',
		'expirePeriod' => 180
	);
}
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['TYPO3\\CMS\\Scheduler\\Task\\TableGarbageCollectionTask']['options']['tables']['sys_history'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['TYPO3\\CMS\\Scheduler\\Task\\TableGarbageCollectionTask']['options']['tables']['sys_history'] = array(
		'dateField' => 'tstamp',
		'expirePeriod' => 30
	);
}

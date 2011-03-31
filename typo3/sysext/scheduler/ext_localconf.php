<?php

if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

	// Register the Scheduler as a possible key for CLI calls
$TYPO3_CONF_VARS['SC_OPTIONS']['GLOBAL']['cliKeys'][$_EXTKEY] = array(
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

	// Add sys_log table garbage collection task
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_scheduler_SysLogGarbageCollection'] = array(
		'extension'        => $_EXTKEY,
		'title'            => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:sysLogGarbageCollection.name',
		'description'      => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:sysLogGarbageCollection.description',
		'additionalFields' => 'tx_scheduler_SysLogGarbageCollection_AdditionalFieldProvider',
	);

?>

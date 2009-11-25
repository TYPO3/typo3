<?php
/* $Id$ */

if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

	// Register the Scheduler as a possible key for CLI calls
$TYPO3_CONF_VARS['SC_OPTIONS']['GLOBAL']['cliKeys'][$_EXTKEY] = array(
	'EXT:' . $_EXTKEY . '/cli/scheduler_cli_dispatch.php', '_CLI_scheduler'
);

	// Register information for the test and sleep tasks
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
?>
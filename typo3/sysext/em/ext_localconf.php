<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

	// Register extension list update task
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['em_tasks_UpdateExtensionList'] = array(
	'extension'        => $_EXTKEY,
	'title'            => 'LLL:EXT:' . $_EXTKEY . '/language/locallang.xml:tasks_updateExtensionlistTask.name',
	'description'      => 'LLL:EXT:' . $_EXTKEY . '/language/locallang.xml:tasks_updateExtensionlistTask.description',
	'additionalFields' => '',
);
?>

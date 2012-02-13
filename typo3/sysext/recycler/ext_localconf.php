<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_recycler_tasks_CleanerTask'] = array(
	'extension'        => $_EXTKEY,
	'title'            => 'LLL:EXT:' . $_EXTKEY . '/locallang_tasks.xml:cleanerTaskTitle',
	'description'      => 'LLL:EXT:' . $_EXTKEY . '/locallang_tasks.xml:cleanerTaskDescription',
	'additionalFields' => 'tx_recycler_tasks_CleanerTaskAdditionalFields'
);

$TYPO3_CONF_VARS['BE']['AJAX']['tx_recycler::controller'] = t3lib_extMgm::extPath($_EXTKEY) . 'classes/controller/class.tx_recycler_controller_ajax.php:tx_recycler_controller_ajax->init';

?>
<?php
defined('TYPO3_MODE') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler(
	'RecyclerAjaxController::init',
	\TYPO3\CMS\Recycler\Task\CleanerTask::class . '->init'
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Recycler\Task\CleanerTask::class] = array(
	'extension' => 'recycler',
	'title' => 'LLL:EXT:recycler/Resources/Private/Language/locallang_tasks.xlf:cleanerTaskTitle',
	'description' => 'LLL:EXT:recycler/Resources/Private/Language/locallang_tasks.xlf:cleanerTaskDescription',
	'additionalFields' => \TYPO3\CMS\Recycler\Task\CleanerFieldProvider::class
);

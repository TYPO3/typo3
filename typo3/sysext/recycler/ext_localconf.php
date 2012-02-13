<?php
defined('TYPO3_MODE') or die();

if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler('RecyclerAjaxController::dispatch', \TYPO3\CMS\Recycler\Controller\RecyclerAjaxController::class . '->dispatch');
}
$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['RecyclerAjaxController::init'] = \TYPO3\CMS\Recycler\Task\CleanerTask::class . '->init';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Recycler\Task\CleanerTask::class] = array(
	'extension' => $_EXTKEY,
	'title' => 'LLL:EXT:' . $_EXTKEY . '/locallang_tasks.xlf:cleanerTaskTitle',
	'description' => 'LLL:EXT:' . $_EXTKEY . '/locallang_tasks.xlf:cleanerTaskDescription',
	'additionalFields' => \TYPO3\CMS\Recycler\Task\CleanerFieldProvider::class
);
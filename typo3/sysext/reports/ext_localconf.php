<?php
defined('TYPO3_MODE') or die();

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Reports\Task\SystemStatusUpdateTask::class] = [
    'extension' => 'reports',
    'title' => 'LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_updateTaskTitle',
    'description' => 'LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_updateTaskDescription',
    'additionalFields' => \TYPO3\CMS\Reports\Task\SystemStatusUpdateTaskNotificationEmailField::class
];

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['displayWarningMessages']['tx_reports_WarningMessagePostProcessor'] = \TYPO3\CMS\Reports\Report\Status\WarningMessagePostProcessor::class;

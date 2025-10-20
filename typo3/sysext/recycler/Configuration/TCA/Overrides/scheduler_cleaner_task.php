<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Recycler\Task\CleanerTask;

defined('TYPO3') or die();

if (isset($GLOBALS['TCA']['tx_scheduler_task'])) {
    ExtensionManagementUtility::addRecordType(
        [
            'label' => 'LLL:EXT:recycler/Resources/Private/Language/locallang_tasks.xlf:cleanerTaskTitle',
            'description' => 'LLL:EXT:recycler/Resources/Private/Language/locallang_tasks.xlf:cleanerTaskDescription',
            'value' => CleanerTask::class,
            'icon' => 'mimetypes-x-tx_scheduler_task_group',
            'group' => 'recycler',
        ],
        '
            --div--;core.form.tabs:general,
                tasktype,
                task_group,
                description,
                selected_tables;LLL:EXT:recycler/Resources/Private/Language/locallang_tasks.xlf:cleanerTaskTCA,
                number_of_days;LLL:EXT:recycler/Resources/Private/Language/locallang_tasks.xlf:cleanerTaskPeriod,
            --div--;LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:scheduler.form.palettes.timing,
                execution_details,
                nextexecution,
                --palette--;;lastexecution,
            --div--;core.form.tabs:access,
                disable,
            --div--;core.form.tabs:extended,
        ',
        [
            'columnsOverrides' => [
                'selected_tables' => [
                    'label' => 'LLL:EXT:recycler/Resources/Private/Language/locallang_tasks.xlf:cleanerTaskTCA',
                    'config' => [
                        'size' => 10,
                        'maxitems' => 100,
                        'itemsProcFunc' => CleanerTask::class . '->getAllTcaTables',
                    ],
                ],
            ],
        ],
        '',
        'tx_scheduler_task'
    );
}

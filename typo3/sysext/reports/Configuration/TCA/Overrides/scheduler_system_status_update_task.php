<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Reports\Task\SystemStatusUpdateTask;

defined('TYPO3') or die();

if (isset($GLOBALS['TCA']['tx_scheduler_task'])) {
    ExtensionManagementUtility::addTCAcolumns(
        'tx_scheduler_task',
        [
            'tx_reports_notification_email' => [
                'label' => 'LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_updateTaskField_notificationEmails',
                'config' => [
                    'type' => 'text',
                    'rows' => 3,
                    'cols' => 50,
                    'required' => true,
                    'placeholder' => 'admin@example.com',
                ],
            ],
            'tx_reports_notification_all' => [
                'label' => 'LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_updateTaskField_notificationAll',
                'config' => [
                    'type' => 'check',
                    'renderType' => 'checkboxToggle',
                    'default' => 0,
                ],
            ],
        ]
    );

    ExtensionManagementUtility::addRecordType(
        [
            'label' => 'LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_updateTaskTitle',
            'description' => 'LLL:EXT:reports/Resources/Private/Language/locallang_reports.xlf:status_updateTaskDescription',
            'value' => SystemStatusUpdateTask::class,
            'icon' => 'mimetypes-x-tx_scheduler_task_group',
            'group' => 'reports',
        ],
        '
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
            tasktype,
            task_group,
            description,
            tx_reports_notification_email,
            tx_reports_notification_all,
        --div--;LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:scheduler.form.palettes.timing,
            execution_details,
            nextexecution,
            --palette--;;lastexecution,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
            disable,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,',
        [],
        '',
        'tx_scheduler_task'
    );
}

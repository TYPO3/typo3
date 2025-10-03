<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use MyVendor\MyExtension\Task\MyTask;

defined('TYPO3') or die();

if (isset($GLOBALS['TCA']['tx_scheduler_task'])) {
    // Add custom fields to the tx_scheduler_task table
    ExtensionManagementUtility::addTCAcolumns(
        'tx_scheduler_task',
        [
            'my_extension_field' => [
                'label' => 'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:field.label',
                'config' => [
                    'type' => 'input',
                    'size' => 30,
                    'required' => true,
                    'eval' => 'trim',
                    'placeholder' => 'Enter value here...',
                ],
            ],
            'my_extension_email_list' => [
                'label' => 'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:emailList.label',
                'config' => [
                    'type' => 'text',
                    'rows' => 3,
                    'required' => true,
                    'placeholder' => 'admin@example.com',
                ],
            ],
        ]
    );

    // Register the task type
    ExtensionManagementUtility::addRecordType(
        [
            'label' => 'Some title or LLL:EXT reference',
            'description' => 'Some description or LLL:EXT reference',
            'value' => MyTask::class,
            'icon' => 'mimetypes-x-tx_scheduler_task_group',
            'group' => 'my_extension',
        ],
        '
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                tasktype,
                task_group,
                description,
                my_extension_field,
                my_extension_email_list,
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

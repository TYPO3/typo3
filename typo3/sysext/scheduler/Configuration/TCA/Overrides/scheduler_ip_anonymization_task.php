<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Scheduler\Task\IpAnonymizationTask;

defined('TYPO3') or die();

ExtensionManagementUtility::addTCAcolumns(
    'tx_scheduler_task',
    [
        'ip_mask' => [
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.ipAnonymization.mask',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'default' => 2,
                'items' => [
                    ['label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.ipAnonymization.mask.1', 'value' => 1],
                    ['label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.ipAnonymization.mask.2', 'value' => 2],
                ],
                'minitems' => 1,
                'maxitems' => 1,
            ],
        ],
    ]
);

ExtensionManagementUtility::addRecordType(
    [
        'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:ipAnonymization.name',
        'description' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:ipAnonymization.description',
        'value' => IpAnonymizationTask::class,
        'icon' => 'mimetypes-x-tx_scheduler_task_group',
        'group' => 'scheduler',
    ],
    '
        --div--;core.form.tabs:general,
            tasktype,
            task_group,
            description,
            ip_mask,
            selected_tables;LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.ipAnonymization.table,
            number_of_days;LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.ipAnonymization.numberOfDays,
        --div--;LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:scheduler.form.palettes.timing,
            execution_details,
            nextexecution,
            --palette--;;lastexecution,
        --div--;core.form.tabs:access,
            disable,
        --div--;core.form.tabs:extended,',
    [
        'columnsOverrides' => [
            'selected_tables' => [
                'displayCond' => 'FIELD:all_tables:=:0',
                'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.ipAnonymization.table',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'size' => 1,
                    'minitems' => 1,
                    'maxitems' => 1,
                    'itemsProcFunc' => IpAnonymizationTask::class . '->getAnonymizableTables',
                ],
            ],
            'number_of_days' => [
                'displayCond' => 'FIELD:all_tables:=:0',
                'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.ipAnonymization.numberOfDays',
                'config' => [
                    'type' => 'number',
                    'default' => 0,
                    'range' => [
                        'lower' => 0,
                    ],
                ],
            ],
        ],
        'taskOptions' => [
            'tables' => [
                'sys_log' => [
                    'dateField' => 'tstamp',
                    'ipField' => 'IP',
                ],
            ],
        ],
    ],
    '',
    'tx_scheduler_task'
);

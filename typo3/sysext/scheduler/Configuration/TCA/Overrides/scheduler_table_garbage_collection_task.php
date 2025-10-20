<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask;

defined('TYPO3') or die();

ExtensionManagementUtility::addTCAcolumns(
    'tx_scheduler_task',
    [
        'all_tables' => [
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.tableGarbageCollection.allTables',
            'description' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.tableGarbageCollection.allTables.description',
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
        'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:tableGarbageCollection.name',
        'description' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:tableGarbageCollection.description',
        'value' => TableGarbageCollectionTask::class,
        'icon' => 'mimetypes-x-tx_scheduler_task_group',
        'group' => 'scheduler',
    ],
    '
        --div--;core.form.tabs:general,
            tasktype,
            task_group,
            description,
            all_tables,
            selected_tables;LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.tableGarbageCollection.table,
            number_of_days;LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.tableGarbageCollection.numberOfDays,
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
                'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.tableGarbageCollection.table',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectSingle',
                    'size' => 1,
                    'minitems' => 1,
                    'maxitems' => 1,
                    'itemsProcFunc' => TableGarbageCollectionTask::class . '->getCleanableTables',
                ],
            ],
            'number_of_days' => [
                'displayCond' => 'FIELD:all_tables:=:0',
                'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.tableGarbageCollection.numberOfDays',
                'config' => [
                    'type' => 'number',
                    'default' => 0,
                    'range' => [
                        'lower' => 0,
                    ],
                    'fieldInformation' => [
                        'expirePeriodInformation' => [
                            'renderType' => 'expirePeriodInformation',
                            'options' => [
                                'refField' => 'selected_tables',
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'taskOptions' => [
            'tables' => [
                'sys_log' => [
                    'dateField' => 'tstamp',
                    'expirePeriod' => 180,
                ],
                'sys_history' => [
                    'dateField' => 'tstamp',
                    'expirePeriod' => 30,
                ],
            ],
        ],
    ],
    '',
    'tx_scheduler_task'
);

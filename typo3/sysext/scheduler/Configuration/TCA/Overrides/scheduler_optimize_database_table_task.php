<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Scheduler\Task\OptimizeDatabaseTableTask;

defined('TYPO3') or die();

ExtensionManagementUtility::addRecordType(
    [
        'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:optimizeDatabaseTable.name',
        'description' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:optimizeDatabaseTable.description',
        'value' => OptimizeDatabaseTableTask::class,
        'icon' => 'mimetypes-x-tx_scheduler_task_group',
        'group' => 'scheduler',
    ],
    '
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
            tasktype,
            task_group,
            description,
            selected_tables;LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.optimizeDatabaseTables.selectTables,
        --div--;LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:scheduler.form.palettes.timing,
            execution_details,
            nextexecution,
            --palette--;;lastexecution,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
            disable,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,',
    [
        'columnsOverrides' => [
            'selected_tables' => [
                'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.optimizeDatabaseTables.selectTables',
                'config' => [
                    'type' => 'select',
                    'renderType' => 'selectMultipleSideBySide',
                    'size' => 10,
                    'minitems' => 1,
                    'maxitems' => 100,
                    'itemsProcFunc' => OptimizeDatabaseTableTask::class . '->getOptimizableTables',
                ],
            ],
        ],
    ],
    '',
    'tx_scheduler_task'
);

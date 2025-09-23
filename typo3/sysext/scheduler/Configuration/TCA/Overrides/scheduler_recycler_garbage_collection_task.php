<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Scheduler\Task\RecyclerGarbageCollectionTask;

defined('TYPO3') or die();

ExtensionManagementUtility::addRecordType(
    [
        'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:recyclerGarbageCollection.name',
        'description' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:recyclerGarbageCollection.description',
        'value' => RecyclerGarbageCollectionTask::class,
        'icon' => 'mimetypes-x-tx_scheduler_task_group',
        'group' => 'scheduler',
    ],
    '
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
            tasktype,
            task_group,
            description,
            number_of_days;LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.recyclerGarbageCollection.numberOfDays,
        --div--;LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:scheduler.form.palettes.timing,
            execution_details,
            nextexecution,
            --palette--;;lastexecution,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
            disable,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,',
    [
        'columnsOverrides' => [
            'number_of_days' => [
                'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.recyclerGarbageCollection.numberOfDays',
                'config' => [
                    'type' => 'number',
                    'default' => 30,
                    'range' => [
                        'lower' => 0,
                    ],
                ],
            ],
        ],
    ],
    '',
    'tx_scheduler_task'
);

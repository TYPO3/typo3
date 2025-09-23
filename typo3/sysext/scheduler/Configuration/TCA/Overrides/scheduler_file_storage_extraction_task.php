<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Scheduler\Task\FileStorageExtractionTask;

defined('TYPO3') or die();

ExtensionManagementUtility::addTCAcolumns(
    'tx_scheduler_task',
    [
        'max_file_count' => [
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.fileStorageExtraction.fileCount',
            'config' => [
                'type' => 'number',
                'size' => 10,
                'default' => 0,
                'range' => [
                    'lower' => 1,
                    'upper' => 9999,
                ],
            ],
        ],
        'registered_extractors' => [
            'config' => [
                'type' => 'none',
                'renderType' => 'registeredExtractors',
            ],
        ],
    ]
);

ExtensionManagementUtility::addRecordType(
    [
        'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:fileStorageExtraction.name',
        'description' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:fileStorageExtraction.description',
        'value' => FileStorageExtractionTask::class,
        'icon' => 'mimetypes-x-tx_scheduler_task_group',
        'group' => 'scheduler',
    ],
    '
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
            tasktype,
            task_group,
            description,
            file_storage;LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.fileStorageIndexing.storage,
            max_file_count,
            registered_extractors,
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

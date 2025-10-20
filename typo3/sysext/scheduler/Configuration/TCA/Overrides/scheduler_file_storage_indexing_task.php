<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Scheduler\Task\FileStorageIndexingTask;

defined('TYPO3') or die();

ExtensionManagementUtility::addRecordType(
    [
        'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:fileStorageIndexing.name',
        'description' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:fileStorageIndexing.description',
        'value' => FileStorageIndexingTask::class,
        'icon' => 'mimetypes-x-tx_scheduler_task_group',
        'group' => 'scheduler',
    ],
    '
        --div--;core.form.tabs:general,
            tasktype,
            task_group,
            description,
            file_storage;LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.fileStorageIndexing.storage,
        --div--;LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:scheduler.form.palettes.timing,
            execution_details,
            nextexecution,
            --palette--;;lastexecution,
        --div--;core.form.tabs:access,
            disable,
        --div--;core.form.tabs:extended,',
    [],
    '',
    'tx_scheduler_task'
);

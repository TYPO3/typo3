<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Scheduler\Task\CachingFrameworkGarbageCollectionTask;

defined('TYPO3') or die();

ExtensionManagementUtility::addTCAcolumns(
    'tx_scheduler_task',
    [
        'cache_backends' => [
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.cachingFrameworkGarbageCollection.selectBackends',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'itemsProcFunc' => CachingFrameworkGarbageCollectionTask::class . '->getRegisteredBackends',
                'size' => 10,
                'minitems' => 0,
                'maxitems' => 100,
                'default' => '',
            ],
        ],
    ]
);

ExtensionManagementUtility::addRecordType(
    [
        'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:cachingFrameworkGarbageCollection.name',
        'description' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:cachingFrameworkGarbageCollection.description',
        'value' => CachingFrameworkGarbageCollectionTask::class,
        'icon' => 'mimetypes-x-tx_scheduler_task_group',
        'group' => 'scheduler',
    ],
    '
        --div--;core.form.tabs:general,
            tasktype,
            task_group,
            description,
            cache_backends;LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.cachingFrameworkGarbageCollection.selectBackends,
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

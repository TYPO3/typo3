<?php

return [
    'ctrl' => [
        'label' => 'tasktype',
        'label_alt' => 'description',
        'label_alt_force' => true,
        'title' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_tca.xlf:tx_scheduler_task',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'default_sortby' => 'nextexecution',
        'typeicon_classes' => [
            // @todo, TYPO3.icons needs to introduce tx_scheduler_task and use the current icon for "tx_scheduler_task_group"
            'default' => 'mimetypes-x-tx_scheduler_task_group',
        ],
        'hideTable' => true,
        'adminOnly' => true, // Only admin users can edit
        'groupName' => 'system',
        'rootLevel' => 1,
        'enablecolumns' => [
            'disabled' => 'disable',
        ],
    ],
    'columns' => [
        'tasktype' => [
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_tca.xlf:tx_scheduler_task.tasktype',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'itemsProcFunc' => \TYPO3\CMS\Scheduler\Service\TaskService::class . '->getTaskTypesForTcaItems',
                'items' => [
                    ['value' => '', 'label' => ''],
                ],
                'default' => '',
                'required' => true,
                // relevant for migration
                'nullable' => true,
            ],
        ],
        'task_group' => [
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_tca.xlf:tx_scheduler_task.task_group',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['value' => 0, 'label' => ''],
                ],
                'foreign_table' => 'tx_scheduler_task_group',
                'foreign_table_where' => 'AND {#tx_scheduler_task_group}.deleted=0',
                'required' => true,
                'default' => 0,
            ],
        ],
        'description' => [
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_tca.xlf:tx_scheduler_task.description',
            'config' => [
                'type' => 'text',
            ],
        ],
        'parameters' => [
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_tca.xlf:tx_scheduler_task.parameters',
            'config' => [
                'type' => 'json',
            ],
        ],
        'execution_details' => [
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_tca.xlf:tx_scheduler_task.execution_details',
            'config' => [
                'type' => 'json',
            ],
        ],
        'nextexecution' => [
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_tca.xlf:tx_scheduler_task.nextexecution',
            'config' => [
                'type' => 'datetime',
            ],
        ],
        'lastexecution_time' => [
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_tca.xlf:tx_scheduler_task.lastexecution_time',
            'config' => [
                'type' => 'datetime',
            ],
        ],
        'lastexecution_failure' => [
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_tca.xlf:tx_scheduler_task.lastexecution_failure',
            'config' => [
                'type' => 'text',
            ],
        ],
        'lastexecution_context' => [
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_tca.xlf:tx_scheduler_task.lastexecution_context',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['value' => 'CLI', 'label' => 'CLI'],
                    ['value' => 'BE', 'label' => 'BE'],
                    ['value' => '', 'label' => ''],
                ],
                'dbFieldLength' => 3,
                'default' => '',
            ],
        ],
        'serialized_executions' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    tasktype,
                    task_group,
                    description,
                    parameters;LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:scheduler.form.palettes.settings,
                --div--;LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:scheduler.form.palettes.timing,
                    execution_details,
                    nextexecution,
                    --palette--;;lastexecution,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    disable,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
        ],
    ],
    'palettes' => [
        'lastexecution' => [
            'showitem' => '
                lastexecution_context,
                lastexecution_time,
                lastexecution_failure,
            ',
        ],
    ],
];

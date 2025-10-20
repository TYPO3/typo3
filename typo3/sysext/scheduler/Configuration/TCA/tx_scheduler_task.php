<?php

use TYPO3\CMS\Scheduler\Service\TaskService;

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
        'type' => 'tasktype',
        'hideTable' => true, // Disabled for now until sorting and grouping is usable in list module
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
                'renderType' => 'taskTypeInfo',
                'itemsProcFunc' => TaskService::class . '->getTaskTypesForTcaItems',
                // Always select the first tasktype
                'items' => [],
                'default' => '',
                'required' => true,
                // relevant for migration
                'nullable' => true,
            ],
        ],
        'task_group' => [
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_tca.xlf:tx_scheduler_task.task_group',
            'config' => [
                'type' => 'group',
                'allowed' => 'tx_scheduler_task_group',
                'size' => 1,
                'maxitems' => 1,
                'default' => 0,
                'hideSuggest' => true,
                'fieldWizard' => [
                    'tableList' => [
                        'disabled' => true,
                    ],
                    'recordsOverview' => [
                        'disabled' => true,
                    ],
                ],
                'fieldControl' => [
                    'editPopup' => [
                        'disabled' => true,
                    ],
                    'addRecord' => [
                        'disabled' => false,
                    ],
                    'listModule' => [
                        'disabled' => true,
                    ],
                ],
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
                'renderType' => 'schedulerAdditionalFields',
            ],
        ],
        'execution_details' => [
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_tca.xlf:tx_scheduler_task.execution_details',
            'config' => [
                'type' => 'json',
                'renderType' => 'schedulerTimingOptions',
                'overrideFieldTca' => [
                    'frequency' => [
                        'config' => [
                            'valuePicker' => [
                                'items' => [
                                    [ 'value' => '0 9,15 * * 1-5', 'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:command.example1' ],
                                    [ 'value' => '0 */2 * * *', 'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:command.example2' ],
                                    [ 'value' => '*/20 * * * *', 'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:command.example3' ],
                                    [ 'value' => '0 7 * * 2', 'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:command.example4' ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'nextexecution' => [
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_tca.xlf:tx_scheduler_task.nextexecution',
            'config' => [
                'type' => 'datetime',
                'readOnly' => true,
            ],
        ],
        'lastexecution_time' => [
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_tca.xlf:tx_scheduler_task.lastexecution_time',
            'config' => [
                'type' => 'datetime',
                'readOnly' => true,
            ],
        ],
        'lastexecution_failure' => [
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_tca.xlf:tx_scheduler_task.lastexecution_failure',
            'config' => [
                'type' => 'text',
                'readOnly' => true,
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
                'readOnly' => true,
                'dbFieldLength' => 3,
                'default' => '',
            ],
        ],
        'serialized_executions' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'number_of_days' => [
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:tx_scheduler_task.number_of_days',
            'config' => [
                'type' => 'number',
                'default' => 0,
            ],
        ],
        'selected_tables' => [
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:tx_scheduler_task.selected_tables',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 10,
                'minitems' => 1,
                'maxitems' => 100,
                'items' => [],
            ],
        ],
        'file_storage' => [
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:tx_scheduler_task.file_storage',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'sys_file_storage',
                'size' => 1,
                'minitems' => 1,
                'maxitems' => 1,
                'items' => [],
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => '
                --div--;core.form.tabs:general,
                    tasktype,
                    task_group,
                    description,
                    parameters;LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:scheduler.form.palettes.settings,
                --div--;LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:scheduler.form.palettes.timing,
                    execution_details,
                    nextexecution,
                    --palette--;;lastexecution,
                --div--;core.form.tabs:access,
                    disable,
                --div--;core.form.tabs:extended,
            ',
        ],
    ],
    'palettes' => [
        'lastexecution' => [
            'showitem' => '
                lastexecution_context,
                lastexecution_time,
                --linebreak--,
                lastexecution_failure,
            ',
        ],
    ],
];

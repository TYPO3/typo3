<?php

return [
    'ctrl' => [
        'label' => 'groupName',
        'tstamp' => 'tstamp',
        'title' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_tca.xlf:tx_scheduler_task_group',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'typeicon_classes' => [
            'default' => 'mimetypes-x-tx_scheduler_task_group',
        ],
        'adminOnly' => true, // Only admin users can edit
        'groupName' => 'system',
        'rootLevel' => 1,
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'searchFields' => 'groupName',
    ],
    'columns' => [
        'groupName' => [
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_tca.xlf:tx_scheduler_task_group.groupName',
            'config' => [
                'type' => 'input',
                'size' => 35,
                'max' => 80,
                'required' => true,
                'eval' => 'unique,trim',
                'softref' => 'substitute',
            ],
        ],
        'description' => [
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_tca.xlf:tx_scheduler_task_group.description',
            'config' => [
                'type' => 'text',
            ],
        ],
        'hidden' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.enabled',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
                'items' => [
                    [
                        'label' => '',
                        'invertStateDisplay' => true,
                    ],
                ],
            ],
        ],
    ],
    'types' => [
        '1' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    groupName,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    hidden,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    description,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
        ],
    ],
];

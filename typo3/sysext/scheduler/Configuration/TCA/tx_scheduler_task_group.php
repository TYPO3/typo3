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
        'color' => [
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_tca.xlf:tx_scheduler_task_group.color',
            'config' => [
                'type' => 'color',
                'size' => 10,
                'valuePicker' => [
                    'items' => [
                        ['label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_tca.xlf:tx_scheduler_task_group.color.typo3Orange', 'value' => '#FF8700'],
                        ['label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_tca.xlf:tx_scheduler_task_group.color.white', 'value' => '#ffffff'],
                        ['label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_tca.xlf:tx_scheduler_task_group.color.gray', 'value' => '#808080'],
                        ['label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_tca.xlf:tx_scheduler_task_group.color.black', 'value' => '#000000'],
                        ['label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_tca.xlf:tx_scheduler_task_group.color.blue', 'value' => '#2671d9'],
                        ['label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_tca.xlf:tx_scheduler_task_group.color.purple', 'value' => '#5e4db2'],
                        ['label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_tca.xlf:tx_scheduler_task_group.color.teal', 'value' => '#2da8d2'],
                        ['label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_tca.xlf:tx_scheduler_task_group.color.green', 'value' => '#3cc38c'],
                        ['label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_tca.xlf:tx_scheduler_task_group.color.magenta', 'value' => '#c6398f'],
                        ['label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_tca.xlf:tx_scheduler_task_group.color.yellow', 'value' => '#ffbf00'],
                        ['label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_tca.xlf:tx_scheduler_task_group.color.red', 'value' => '#d13a2e'],
                    ],
                ],
            ],
        ],
        'description' => [
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_tca.xlf:tx_scheduler_task_group.description',
            'config' => [
                'type' => 'text',
            ],
        ],
    ],
    'types' => [
        '1' => [
            'showitem' => '
                --div--;core.form.tabs:general, groupName, color,
                --div--;core.form.tabs:access, hidden,
                --div--;core.form.tabs:notes, description,
                --div--;core.form.tabs:extended,
            ',
        ],
    ],
];

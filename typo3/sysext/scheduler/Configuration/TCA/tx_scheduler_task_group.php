<?php
return [
    'ctrl' => [
        'label' => 'groupName',
        'tstamp' => 'tstamp',
        'title' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_tca.xlf:tx_scheduler_task_group',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'typeicon_classes' => [
            'default' => 'mimetypes-x-tx_scheduler_task_group'
        ],
        'adminOnly' => 1, // Only admin users can edit
        'rootLevel' => 1,
        'enablecolumns' => [
            'disabled' => 'hidden'
        ],
        'searchFields' => 'groupName'
    ],
    'interface' => [
        'showRecordFieldList' => 'hidden,groupName'
    ],
    'columns' => [
        'groupName' => [
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_tca.xlf:tx_scheduler_task_group.groupName',
            'config' => [
                'type' => 'input',
                'size' => '35',
                'max' => '80',
                'eval' => 'required,unique,trim',
                'softref' => 'substitute'
            ]
        ],
        'description' => [
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang_tca.xlf:tx_scheduler_task_group.description',
            'config' => [
                'type' => 'text'
            ],
        ],
        'hidden' => [
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.disable',
            'exclude' => 1,
            'config' => [
                'type' => 'check',
                'default' => '0'
            ]
        ]
    ],
    'types' => [
        '1' => [
            'showitem' => 'hidden, groupName, description',
        ],
    ],
];

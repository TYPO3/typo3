<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'Machine',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'type' => 'type',
        'delete' => 'deleted',
        'iconfile' => 'EXT:blog_example/Resources/Public/Icons/icon_tx_blogexample_domain_model_dateexample.gif',
    ],
    'columns' => [
        'type' => [
            'label' => 'Type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'default' => 'machine',
                'items' => [
                    ['label' => 'Machine', 'value' => 'machine'],
                    ['label' => 'Robot', 'value' => 'robot'],
                    ['label' => 'Drone', 'value' => 'drone'],
                ],
            ],
        ],
        'title' => [
            'label' => 'Title',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
            ],
        ],
        'battery' => [
            'label' => 'Battery',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
            ],
        ],
    ],
    'types' => [
        'machine' => ['showitem' => 'title, type'],
        'robot' => ['showitem' => 'title, type, battery'],
        'drone' => ['showitem' => 'title, type'],
    ],
];

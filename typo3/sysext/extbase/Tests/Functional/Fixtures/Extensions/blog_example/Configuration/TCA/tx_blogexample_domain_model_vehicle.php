<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'Vehicle',
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
                'default' => '0',
                'items' => [
                    ['label' => 'Vehicle', 'value' => '0'],
                    ['label' => 'Car', 'value' => 'car'],
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
    ],
    'types' => [
        '0' => ['showitem' => 'title, type'],
        'car' => ['showitem' => 'title, type'],
    ],
];

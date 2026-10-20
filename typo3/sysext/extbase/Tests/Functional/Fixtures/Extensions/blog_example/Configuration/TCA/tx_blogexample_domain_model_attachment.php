<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'Attachment',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'iconfile' => 'EXT:blog_example/Resources/Public/Icons/icon_tx_blogexample_domain_model_dateexample.gif',
    ],
    'columns' => [
        'title' => [
            'label' => 'Title',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
            ],
        ],
        'parent' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'parent_table' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'parent_field' => [
            'label' => 'Parent field',
            'config' => [
                'type' => 'input',
            ],
        ],
        'sorting' => [
            'label' => 'Sorting',
            'config' => [
                'type' => 'number',
            ],
        ],
    ],
    'types' => [
        '1' => ['showitem' => 'title, parent, parent_table, parent_field, sorting'],
    ],
];

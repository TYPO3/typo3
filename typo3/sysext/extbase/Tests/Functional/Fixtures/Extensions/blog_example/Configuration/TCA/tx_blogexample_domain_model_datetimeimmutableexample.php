<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'DateTimeImmutable Example',
        'label' => 'title',
        'iconfile' => 'EXT:blog_example/Resources/Public/Icons/icon_tx_blogexample_domain_model_dateexample.gif',
    ],
    'columns' => [
        'datetime_immutable_text' => [
            'exclude' => 1,
            'label' => 'type=datetime, db=text',
            'config' => [
                'type' => 'datetime',
            ],
        ],
        'datetime_immutable_int' => [
            'exclude' => 1,
            'label' => 'type=datetime, db=int',
            'config' => [
                'type' => 'datetime',
            ],
        ],
        'datetime_immutable_datetime' => [
            'exclude' => 1,
            'label' => 'type=datetime, db=datetime',
            'config' => [
                'type' => 'datetime',
                'dbType' => 'datetime',
            ],
        ],
    ],
    'types' => [
        '1' => ['showitem' => 'datetime_immutable_text, datetime_immutable_int, datetime_immutable_datetime'],
    ],
];

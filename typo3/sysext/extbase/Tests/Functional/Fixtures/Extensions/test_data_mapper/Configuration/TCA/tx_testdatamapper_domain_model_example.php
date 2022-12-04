<?php

return [
    'columns' => [
        'first_property' => [
            'config' => [
                'type' => 'input',
            ],
        ],
        'second_property' => [
            'config' => [
                'type' => 'number',
            ],
        ],
        'third_property' => [
            'config' => [
                'type' => 'number',
                'format' => 'decimal',
            ],
        ],
        'fourth_property' => [
            'config' => [
                'type' => 'check',
            ],
        ],
        'uninitialized_string_property' => [
            'config' => [
                'type' => 'input',
            ],
        ],
        'uninitialized_date_time_property' => [
            'config' => [
                'type' => 'datetime',
            ],
        ],
        'uninitialized_mandatory_date_time_property' => [
            'config' => [
                'type' => 'datetime',
            ],
        ],
        'initialized_date_time_property' => [
            'config' => [
                'type' => 'datetime',
            ],
        ],
        'custom_date_time' => [
            'config' => [
                'type' => 'datetime',
                'dbType' => 'datetime',
            ],
        ],
        'unknown_type' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ],
];

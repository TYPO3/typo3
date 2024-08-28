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
        'initialized_date_time_property_date' => [
            'config' => [
                'type' => 'datetime',
                'dbType' => 'date',
            ],
        ],
        'initialized_date_time_property_datetime' => [
            'config' => [
                'type' => 'datetime',
                'dbType' => 'datetime',
            ],
        ],
        'initialized_date_time_property_time' => [
            'config' => [
                'type' => 'datetime',
                'dbType' => 'time',
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
        'string_backed_enum' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'nullable_string_backed_enum' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'integer_backed_enum' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'nullable_integer_backed_enum' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ],
];

<?php

return [
    'ctrl' => [
        'title' => 'A foreign table',
        'label' => 'title',
        'rootLevel' => -1,
    ],
    'columns' => [
        'title' => [
            'label' => 'Title',
            'config' => [
                'type' => 'input',
            ],
        ],
        'fal_field' => [
            'label' => 'FAL',
            'config' => [
                'type' => 'file',
            ],
        ],
    ],
];

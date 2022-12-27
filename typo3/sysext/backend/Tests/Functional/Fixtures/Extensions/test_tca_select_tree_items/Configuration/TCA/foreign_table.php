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
        'children_field' => [
            'label' => 'Children Field',
            'config' => [
                'type' => 'input',
            ],
        ],
    ],
];

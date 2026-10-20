<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'Identified',
        'label' => 'title',
    ],
    'types' => [
        '1' => ['showitem' => 'title, identifier'],
    ],
    'columns' => [
        'title' => [
            'label' => 'Title',
            'config' => [
                'type' => 'input',
            ],
        ],
        'identifier' => [
            'label' => 'Identifier',
            'config' => [
                'type' => 'uuid',
            ],
        ],
    ],
];

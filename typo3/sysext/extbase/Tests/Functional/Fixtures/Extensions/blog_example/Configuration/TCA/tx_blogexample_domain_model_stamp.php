<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'Stamp',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
    ],
    'types' => [
        '1' => ['showitem' => 'title'],
    ],
    'columns' => [
        'crdate' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'title' => [
            'label' => 'Title',
            'config' => [
                'type' => 'input',
            ],
        ],
    ],
];

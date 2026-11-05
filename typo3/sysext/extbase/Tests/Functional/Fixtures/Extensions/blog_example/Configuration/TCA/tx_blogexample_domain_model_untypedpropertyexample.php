<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'Untyped property example',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
    ],
    'columns' => [
        'untyped' => [
            'label' => 'untyped',
            'config' => [
                'type' => 'input',
                'nullable' => true,
            ],
        ],
    ],
    'types' => [
        '1' => ['showitem' => 'untyped'],
    ],
];

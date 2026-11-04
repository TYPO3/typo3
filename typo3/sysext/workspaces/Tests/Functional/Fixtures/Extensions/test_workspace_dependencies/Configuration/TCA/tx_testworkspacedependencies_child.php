<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'Child',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'sortby' => 'sorting',
        'delete' => 'deleted',
        'versioningWS' => true,
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'parentid' => [
            'config' => [
                'type' => 'passthrough',
                'default' => 0,
            ],
        ],
        'parenttable' => [
            'config' => [
                'type' => 'passthrough',
                'default' => '',
            ],
        ],
        'title' => [
            'label' => 'Title',
            'config' => [
                'type' => 'input',
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => 'title',
        ],
    ],
];

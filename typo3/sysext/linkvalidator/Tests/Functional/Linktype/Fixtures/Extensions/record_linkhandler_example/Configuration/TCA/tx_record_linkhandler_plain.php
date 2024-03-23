<?php

// Table without soft delete and enable fields
return [
    'ctrl' => [
        'title' => 'RecordLinkhandlerPlain',
        'label' => 'title',
    ],
    'columns' => [
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

<?php

return [
    'ctrl' => [
        'title' => 'RecordLinkhandlerExample',
        'label' => 'title',
        'label_alt' => 'subtitle',
        'label_alt_force' => true,
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
    ],
    'columns' => [
        'title' => [
            'label' => 'Title',
            'config' => [
                'type' => 'input',
            ],
        ],
        'subtitle' => [
            'label' => 'Subtitle',
            'config' => [
                'type' => 'input',
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => 'title, subtitle, --div--;core.form.tabs:access, hidden, starttime, endtime',
        ],
    ],
];

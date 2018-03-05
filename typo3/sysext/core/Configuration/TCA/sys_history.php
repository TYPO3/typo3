<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_history',
        'label' => 'tablename',
        'tstamp' => 'tstamp',
        'adminOnly' => true,
        'rootLevel' => 1,
        'hideTable' => true,
        'default_sortby' => 'uid DESC',
    ],
    'columns' => [
        'history_data' => [
            'label' => 'history_data',
            'config' => [
                'type' => 'input'
            ]
        ],
        'recuid' => [
            'label' => 'recuid',
            'config' => [
                'type' => 'input'
            ]
        ],
        'tablename' => [
            'label' => 'tablename',
            'config' => [
                'type' => 'input'
            ]
        ],
        'tstamp' => [
            'label' => 'tstamp',
            'config' => [
                'type' => 'input'
            ]
        ],
        'snapshot' => [
            'label' => 'snapshot',
            'config' => [
                'type' => 'input'
            ]
        ]
    ],
    'types' => [
        '1' => [
            'showitem' => 'history_data, recuid, tablename, tstamp, snapshot'
        ]
    ]
];

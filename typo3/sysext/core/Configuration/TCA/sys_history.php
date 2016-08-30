<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:lang/locallang_tca.xlf:sys_history',
        'label' => 'tablename',
        'tstamp' => 'tstamp',
        'adminOnly' => true,
        'rootLevel' => true,
        'hideTable' => true,
        'default_sortby' => 'uid DESC',
    ],
    'columns' => [
        'sys_log_uid' => [
            'label' => 'sys_log_uid',
            'config' => [
                'type' => 'input'
            ]
        ],
        'history_data' => [
            'label' => 'history_data',
            'config' => [
                'type' => 'input'
            ]
        ],
        'fieldlist' => [
            'label' => 'fieldlist',
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
        'history_files' => [
            'label' => 'history_files',
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
            'showitem' => 'sys_log_uid, history_data, fieldlist, recuid, tablename, tstamp, history_files, snapshot'
        ]
    ]
];

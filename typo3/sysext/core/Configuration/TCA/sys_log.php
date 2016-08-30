<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:lang/locallang_tca.xlf:sys_log',
        'label' => 'details',
        'tstamp' => 'tstamp',
        'adminOnly' => true,
        'rootLevel' => true,
        'hideTable' => true,
        'default_sortby' => 'uid DESC',
    ],
    'columns' => [
        'tstamp' => [
            'label' => 'tstamp',
            'config' => [
                'type' => 'input'
            ]
        ],
        'userid' => [
            'label' => 'userid',
            'config' => [
                'type' => 'input'
            ]
        ],
        'action' => [
            'label' => 'action',
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
        'recpid' => [
            'label' => 'recpid',
            'config' => [
                'type' => 'input'
            ]
        ],
        'error' => [
            'label' => 'error',
            'config' => [
                'type' => 'input'
            ]
        ],
        'details' => [
            'label' => 'details',
            'config' => [
                'type' => 'input'
            ]
        ],
        'type' => [
            'label' => 'type',
            'config' => [
                'type' => 'input'
            ]
        ],
        'details_nr' => [
            'label' => 'details_nr',
            'config' => [
                'type' => 'input'
            ]
        ],
        'IP' => [
            'label' => 'IP',
            'config' => [
                'type' => 'input'
            ]
        ],
        'log_data' => [
            'label' => 'log_data',
            'config' => [
                'type' => 'input'
            ]
        ],
        'event_pid' => [
            'label' => 'event_pid',
            'config' => [
                'type' => 'input'
            ]
        ],
        'workspace' => [
            'label' => 'workspace',
            'config' => [
                'type' => 'input'
            ]
        ],
        'NEWid' => [
            'label' => 'NEWid',
            'config' => [
                'type' => 'input'
            ]
        ]
    ],
    'types' => [
        '1' => [
            'showitem' => 'tstamp, userid, action, recuid, tablename, recpid, error, details, type, details_nr, IP, log_data, event_pid, workspace, NEWid'
        ]
    ]
];

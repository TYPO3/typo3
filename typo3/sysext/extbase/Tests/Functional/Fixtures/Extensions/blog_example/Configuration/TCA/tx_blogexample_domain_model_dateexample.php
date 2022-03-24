<?php

declare(strict_types=1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xlf:tx_blogexample_domain_model_dateexample',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'fe_group' => 'fe_group',
        ],
        'iconfile' => 'EXT:blog_example/Resources/Public/Icons/icon_tx_blogexample_domain_model_dateexample.gif',
    ],
    'columns' => [
        'datetime_text' => [
            'exclude' => 1,
            'label' => 'type=datetime, db=text',
            'config' => [
                'type' => 'datetime',
            ],
        ],
        'datetime_int' => [
            'exclude' => 1,
            'label' => 'type=datetime, db=int',
            'config' => [
                'type' => 'datetime',
            ],
        ],
        'datetime_datetime' => [
            'exclude' => 1,
            'label' => 'type=datetime, db=datetime',
            'config' => [
                'type' => 'datetime',
                'dbType' => 'datetime',
            ],
        ],
    ],
    'types' => [
        '1' => ['showitem' => 'datetime_text, datetime_int, datetime_datetime'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];

<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:lang/locallang_tca.xlf:sys_news',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'adminOnly' => true,
        'rootLevel' => true,
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime'
        ],
        'default_sortby' => 'crdate DESC',
        'typeicon_classes' => [
            'default' => 'mimetypes-x-sys_news'
        ]
    ],
    'interface' => [
        'showRecordFieldList' => 'hidden,title,content,starttime,endtime'
    ],
    'columns' => [
        'hidden' => [
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.disable',
            'exclude' => 1,
            'config' => [
                'type' => 'check',
                'default' => '0'
            ]
        ],
        'starttime' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'input',
                'size' => '13',
                'eval' => 'datetime',
                'default' => '0'
            ]
        ],
        'endtime' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'input',
                'size' => '13',
                'eval' => 'datetime',
                'default' => '0'
            ]
        ],
        'title' => [
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.title',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'max' => '255',
                'eval' => 'required'
            ]
        ],
        'content' => [
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.text',
            'config' => [
                'type' => 'text',
                'cols' => '48',
                'rows' => '5',
                'wizards' => [
                    'RTE' => [
                        'notNewRecords' => 1,
                        'RTEonly' => 1,
                        'type' => 'script',
                        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext.W.RTE',
                        'icon' => 'actions-wizard-rte',
                        'module' => [
                            'name' => 'wizard_rte'
                        ]
                    ]
                ]
            ],
            'defaultExtras' => 'richtext:rte_transform[mode=ts_css]',
        ]
    ],
    'types' => [
        '1' => [
            'showitem' => 'hidden, title, content, --div--;LLL:EXT:lang/locallang_tca.xlf:sys_news.tabs.access, starttime, endtime',
        ],
    ],
];

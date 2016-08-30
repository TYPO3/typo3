<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config',
        'label' => 'title',
        'descriptionColumn' => 'description',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'type' => 'type',
        'default_sortby' => 'ORDER BY crdate',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime'
        ],
        'typeicon_classes' => [
            'default' => 'mimetypes-x-index_config'
        ]
    ],
    'interface' => [
        'showRecordFieldList' => 'hidden,starttime,title,description,type,depth,table2index,alternative_source_pid,get_params,chashcalc,filepath,extensions'
    ],
    'columns' => [
        'hidden' => [
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.disable',
            'config' => [
                'type' => 'check',
                'default' => '1'
            ]
        ],
        'starttime' => [
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'input',
                'size' => '8',
                'max' => '20',
                'eval' => 'date',
                'default' => '0',
            ]
        ],
        'title' => [
            'label' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.title',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'required'
            ]
        ],
        'description' => [
            'label' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.description',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '2'
            ]
        ],
        'type' => [
            'label' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.type.I.0', '0'],
                    ['LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.type.I.1', '1'],
                    ['LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.type.I.2', '2'],
                    ['LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.type.I.3', '3'],
                    ['LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.type.I.4', '4'],
                    ['LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.type.I.5', '5']
                ],
                'size' => 1,
                'maxitems' => 1
            ]
        ],
        'depth' => [
            'label' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.depth',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:lang/locallang_core.xlf:labels.depth_0', '0'],
                    ['LLL:EXT:lang/locallang_core.xlf:labels.depth_1', '1'],
                    ['LLL:EXT:lang/locallang_core.xlf:labels.depth_2', '2'],
                    ['LLL:EXT:lang/locallang_core.xlf:labels.depth_3', '3'],
                    ['LLL:EXT:lang/locallang_core.xlf:labels.depth_4', '4']
                ],
                'size' => 1,
                'maxitems' => 1
            ]
        ],
        'table2index' => [
            'label' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.table2index',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.table2index.I.0', '0']
                ],
                'special' => 'tables',
                'size' => 1,
                'maxitems' => 1
            ]
        ],
        'alternative_source_pid' => [
            'label' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.alternative_source_pid',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
                'wizards' => [
                    'suggest' => [
                        'type' => 'suggest'
                    ]
                ]
            ]
        ],
        'indexcfgs' => [
            'label' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.indexcfgs',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'index_config,pages',
                'size' => 5,
                'minitems' => 0,
                'maxitems' => 200,
                'wizards' => [
                    'suggest' => [
                        'type' => 'suggest'
                    ]
                ]
            ]
        ],
        'get_params' => [
            'label' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.get_params',
            'config' => [
                'type' => 'input',
                'size' => '30'
            ]
        ],
        'fieldlist' => [
            'label' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.fields',
            'config' => [
                'type' => 'input',
                'size' => '30'
            ]
        ],
        'externalUrl' => [
            'label' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.externalUrl',
            'config' => [
                'type' => 'input',
                'size' => '30'
            ]
        ],
        'chashcalc' => [
            'label' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.chashcalc',
            'config' => [
                'type' => 'check'
            ]
        ],
        'filepath' => [
            'label' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.filepath',
            'config' => [
                'type' => 'input',
                'size' => '30'
            ]
        ],
        'extensions' => [
            'label' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.extensions',
            'config' => [
                'type' => 'input',
                'size' => '30'
            ]
        ],
        'url_deny' => [
            'label' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.url_deny',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '2'
            ]
        ],
        'records_indexonchange' => [
            'label' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.records_indexonchange',
            'config' => [
                'type' => 'check',
                'default' => '0'
            ]
        ],
        'timer_next_indexing' => [
            'label' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.timer_next_indexing',
            'config' => [
                'type' => 'input',
                'size' => '12',
                'eval' => 'datetime',
                'default' => '0',
            ]
        ],
        'timer_offset' => [
            'label' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.timer_offset',
            'config' => [
                'type' => 'input',
                'size' => '8',
                'max' => '20',
                'eval' => 'time',
                'default' => 3600
            ]
        ],
        'timer_frequency' => [
            'label' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.timer_frequency',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.timer_frequency.I.0', '3600'],
                    ['LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.timer_frequency.I.1', '86400'],
                    ['LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.timer_frequency.I.2', '604800']
                ],
                'size' => 1,
                'maxitems' => 1,
                'default' => 86400
            ]
        ],
        'recordsbatch' => [
            'label' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.recordsbatch',
            'config' => [
                'type' => 'input',
                'size' => '8',
                'max' => '20',
                'eval' => 'int',
                'default' => '0',
            ]
        ],
        'set_id' => [
            'label' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.set_id',
            'config' => [
                'type' => 'none'
            ]
        ]
    ],
    'types' => [
        '0' => [
            'showitem' => 'title, --palette--;;1, description, timer_next_indexing, timer_offset, timer_frequency, set_id, type',
        ],
        '1' => [
            'showitem' => 'title, --palette--;;1, description, timer_next_indexing, timer_offset, timer_frequency, set_id, type, table2index, alternative_source_pid, fieldlist, get_params, chashcalc,recordsbatch,records_indexonchange',
        ],
        '2' => [
            'showitem' => 'title, --palette--;;1, description, timer_next_indexing, timer_offset, timer_frequency, set_id, type, filepath, extensions, depth',
        ],
        '3' => [
            'showitem' => 'title, --palette--;;1, timer_next_indexing, timer_offset, timer_frequency, set_id, type, externalUrl, depth, url_deny',
        ],
        '4' => [
            'showitem' => 'title, --palette--;;1, description, timer_next_indexing, timer_offset, timer_frequency, set_id, type, alternative_source_pid;LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.rootpage, depth',
        ],
        '5' => [
            'showitem' => 'title, description, type, indexcfgs',
        ],
    ],
    'palettes' => [
        '1' => [
            'showitem' => 'starttime, hidden',
        ],
    ],
];

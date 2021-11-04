<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config',
        'label' => 'title',
        'descriptionColumn' => 'description',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'groupName' => 'system',
        'type' => 'type',
        'default_sortby' => 'crdate',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
        ],
        'typeicon_classes' => [
            'default' => 'mimetypes-x-index_config',
        ],
    ],
    'columns' => [
        'hidden' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.enabled',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 1,
                'items' => [
                    [
                        0 => '',
                        'invertStateDisplay' => true,
                    ],
                ],
            ],
        ],
        'starttime' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'date,int',
                'default' => 0,
            ],
        ],
        'title' => [
            'label' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.title',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'required',
            ],
        ],
        'description' => [
            'label' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.description',
            'config' => [
                'type' => 'text',
                'cols' => 30,
                'rows' => 2,
            ],
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
                    ['LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.type.I.5', '5'],
                ],
                'maxitems' => 1,
            ],
        ],
        'depth' => [
            'label' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.depth',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_0', '0'],
                    ['LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_1', '1'],
                    ['LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_2', '2'],
                    ['LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_3', '3'],
                    ['LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_4', '4'],
                ],
                'maxitems' => 1,
            ],
        ],
        'table2index' => [
            'label' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.table2index',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.table2index.I.0', '0'],
                ],
                'itemsProcFunc' => \TYPO3\CMS\IndexedSearch\Hook\AvailableTcaTables::class . '->populateTables',
                'maxitems' => 1,
            ],
        ],
        'alternative_source_pid' => [
            'label' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.alternative_source_pid',
            'config' => [
                'type' => 'group',
                'allowed' => 'pages',
                'size' => 1,
                'maxitems' => 1,
            ],
        ],
        'indexcfgs' => [
            'label' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.indexcfgs',
            'config' => [
                'type' => 'group',
                'allowed' => 'index_config,pages',
                'size' => 5,
                'maxitems' => 200,
            ],
        ],
        'get_params' => [
            'label' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.get_params',
            'config' => [
                'type' => 'input',
                'size' => 30,
            ],
        ],
        'fieldlist' => [
            'label' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.fields',
            'config' => [
                'type' => 'input',
                'size' => 30,
            ],
        ],
        'externalUrl' => [
            'label' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.externalUrl',
            'config' => [
                'type' => 'input',
                'size' => 30,
            ],
        ],
        'filepath' => [
            'label' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.filepath',
            'config' => [
                'type' => 'input',
                'size' => 30,
            ],
        ],
        'extensions' => [
            'label' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.extensions',
            'config' => [
                'type' => 'input',
                'size' => 30,
            ],
        ],
        'url_deny' => [
            'label' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.url_deny',
            'config' => [
                'type' => 'text',
                'cols' => 30,
                'rows' => 2,
            ],
        ],
        'records_indexonchange' => [
            'label' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.records_indexonchange',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
            ],
        ],
        'timer_next_indexing' => [
            'label' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.timer_next_indexing',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime,int',
                'default' => 0,
            ],
        ],
        'timer_offset' => [
            'label' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.timer_offset',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'time,int',
                'default' => 3600,
            ],
        ],
        'timer_frequency' => [
            'label' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.timer_frequency',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.timer_frequency.I.0', '3600'],
                    ['LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.timer_frequency.I.1', '86400'],
                    ['LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.timer_frequency.I.2', '604800'],
                ],
                'maxitems' => 1,
                'default' => 86400,
            ],
        ],
        'recordsbatch' => [
            'label' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.recordsbatch',
            'config' => [
                'type' => 'input',
                'size' => 8,
                'max' => 20,
                'eval' => 'int',
                'default' => 0,
            ],
        ],
        'set_id' => [
            'label' => 'LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.set_id',
            'config' => [
                'type' => 'input',
                'readOnly' => true,
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    type,title,timer_next_indexing, timer_offset, timer_frequency, set_id,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    hidden,starttime,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    description,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
        ],
        '1' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    type,title,timer_next_indexing, timer_offset, timer_frequency, set_id, table2index, alternative_source_pid, fieldlist, get_params,recordsbatch,records_indexonchange,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    hidden,starttime,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    description,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
        ],
        '2' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    type,title,timer_next_indexing, timer_offset, timer_frequency, set_id, filepath, extensions, depth,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    hidden,starttime,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    description,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
        ],
        '3' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    type, title, timer_next_indexing, timer_offset, timer_frequency, set_id, externalUrl, depth, url_deny,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    hidden,starttime,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    description,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
        ],
        '4' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    type,title,timer_next_indexing, timer_offset, timer_frequency, set_id, alternative_source_pid;LLL:EXT:indexed_search/Resources/Private/Language/locallang_db.xlf:index_config.rootpage, depth,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    hidden,starttime,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    description,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
        ],
        '5' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    type,title,indexcfgs,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    hidden,starttime,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    description,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
        ],
    ],
];

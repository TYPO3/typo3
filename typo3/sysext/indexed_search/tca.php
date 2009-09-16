<?php
if (!defined ('TYPO3_MODE'))     die ('Access denied.');

$TCA['index_config'] = Array (
    'ctrl' => $TCA['index_config']['ctrl'],
    'interface' => Array (
        'showRecordFieldList' => 'hidden,starttime,title,description,type,depth,table2index,alternative_source_pid,get_params,chashcalc,filepath,extensions'
    ),
    'feInterface' => $TCA['index_config']['feInterface'],
    'columns' => Array (
        'hidden' => Array (
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.disable',
            'config' => Array (
                'type' => 'check',
                'default' => '1'
            )
        ),
        'starttime' => Array (
            'label' => 'LLL:EXT:lang/locallang_general.php:LGL.starttime',
            'config' => Array (
                'type' => 'input',
                'size' => '8',
                'max' => '20',
                'eval' => 'date',
                'default' => '0',
                'checkbox' => '0'
            )
        ),
        'title' => Array (
            'label' => 'LLL:EXT:indexed_search/locallang_db.php:index_config.title',
            'config' => Array (
                'type' => 'input',
                'size' => '30',
                'eval' => 'required',
            )
        ),
        'description' => Array (
            'label' => 'LLL:EXT:indexed_search/locallang_db.php:index_config.description',
            'config' => Array (
                'type' => 'text',
                'cols' => '30',
                'rows' => '2',
            )
        ),
        'type' => Array (
            'label' => 'LLL:EXT:indexed_search/locallang_db.php:index_config.type',
            'config' => Array (
                'type' => 'select',
                'items' => Array (
                    Array('LLL:EXT:indexed_search/locallang_db.php:index_config.type.I.0', '0'),
                    Array('LLL:EXT:indexed_search/locallang_db.php:index_config.type.I.1', '1'),
                    Array('LLL:EXT:indexed_search/locallang_db.php:index_config.type.I.2', '2'),
                    Array('LLL:EXT:indexed_search/locallang_db.php:index_config.type.I.3', '3'),
                    Array('LLL:EXT:indexed_search/locallang_db.php:index_config.type.I.4', '4'),
                    Array('LLL:EXT:indexed_search/locallang_db.php:index_config.type.I.5', '5'),
                ),
                'size' => 1,
                'maxitems' => 1,
            )
        ),
        'depth' => Array (
            'label' => 'LLL:EXT:indexed_search/locallang_db.php:index_config.depth',
            'config' => Array (
                'type' => 'select',
                'items' => Array (
                    Array('LLL:EXT:indexed_search/locallang_db.php:index_config.depth.I.0', '0'),
                    Array('LLL:EXT:indexed_search/locallang_db.php:index_config.depth.I.1', '1'),
                    Array('LLL:EXT:indexed_search/locallang_db.php:index_config.depth.I.2', '2'),
                    Array('LLL:EXT:indexed_search/locallang_db.php:index_config.depth.I.3', '3'),
                ),
                'size' => 1,
                'maxitems' => 1,
            )
        ),
        'table2index' => Array (
            'label' => 'LLL:EXT:indexed_search/locallang_db.php:index_config.table2index',
            'config' => Array (
                'type' => 'select',
                'items' => Array (
                    Array('LLL:EXT:indexed_search/locallang_db.php:index_config.table2index.I.0', '0'),
                ),
				'special' => 'tables',
                'size' => 1,
                'maxitems' => 1,
            )
        ),
        'alternative_source_pid' => Array (
            'label' => 'LLL:EXT:indexed_search/locallang_db.php:index_config.alternative_source_pid',
            'config' => Array (
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
				'wizards' => array(
					'suggest' => array(
						'type' => 'suggest',
					)
				)
            )
        ),
        'indexcfgs' => Array (
            'label' => 'LLL:EXT:indexed_search/locallang_db.php:index_config.indexcfgs',
            'config' => Array (
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'index_config,pages',
                'size' => 5,
                'minitems' => 0,
                'maxitems' => 200,
				'wizards' => array(
					'suggest' => array(
						'type' => 'suggest',
					)
				)
            )
        ),
        'get_params' => Array (
            'label' => 'LLL:EXT:indexed_search/locallang_db.php:index_config.get_params',
            'config' => Array (
                'type' => 'input',
                'size' => '30',
            )
        ),
        'fieldlist' => Array (
            'label' => 'LLL:EXT:indexed_search/locallang_db.php:index_config.fields',
            'config' => Array (
                'type' => 'input',
                'size' => '30',
            )
        ),
        'externalUrl' => Array (
            'label' => 'LLL:EXT:indexed_search/locallang_db.php:index_config.externalUrl',
            'config' => Array (
                'type' => 'input',
                'size' => '30',
            )
        ),
        'chashcalc' => Array (
            'label' => 'LLL:EXT:indexed_search/locallang_db.php:index_config.chashcalc',
            'config' => Array (
                'type' => 'check',
            )
        ),
        'filepath' => Array (
            'label' => 'LLL:EXT:indexed_search/locallang_db.php:index_config.filepath',
            'config' => Array (
                'type' => 'input',
                'size' => '30',
            )
        ),
        'extensions' => Array (
            'label' => 'LLL:EXT:indexed_search/locallang_db.php:index_config.extensions',
            'config' => Array (
                'type' => 'input',
                'size' => '30',
            )
        ),
        'url_deny' => Array (
            'label' => 'LLL:EXT:indexed_search/locallang_db.php:index_config.url_deny',
            'config' => Array (
                'type' => 'text',
                'cols' => '30',
                'rows' => '2',
            )
        ),
        'records_indexonchange' => Array (
            'label' => 'LLL:EXT:indexed_search/locallang_db.php:index_config.records_indexonchange',
            'config' => Array (
                'type' => 'check',
                'default' => '0',
            )
        ),
        'timer_next_indexing' => Array (
            'label' => 'LLL:EXT:indexed_search/locallang_db.php:index_config.timer_next_indexing',
            'config' => Array (
                'type' => 'input',
                'size' => '12',
                'max' => '20',
                'eval' => 'datetime',
                'default' => '0',
                'checkbox' => '0'
            )
        ),
        'timer_offset' => Array (
            'label' => 'LLL:EXT:indexed_search/locallang_db.php:index_config.timer_offset',
            'config' => Array (
                'type' => 'input',
                'size' => '8',
                'max' => '20',
                'eval' => 'time',
                'default' => 3600,
            )
        ),
        'timer_frequency' => Array (
            'label' => 'LLL:EXT:indexed_search/locallang_db.php:index_config.timer_frequency',
            'config' => Array (
                'type' => 'select',
                'items' => Array (
                    Array('LLL:EXT:indexed_search/locallang_db.php:index_config.timer_frequency.I.0', '3600'),
                    Array('LLL:EXT:indexed_search/locallang_db.php:index_config.timer_frequency.I.1', '86400'),
                    Array('LLL:EXT:indexed_search/locallang_db.php:index_config.timer_frequency.I.2', '604800'),
                ),
                'size' => 1,
                'maxitems' => 1,
                'default' => 86400,
            )
        ),
        'recordsbatch' => Array (
            'label' => 'LLL:EXT:indexed_search/locallang_db.php:index_config.recordsbatch',
            'config' => Array (
                'type' => 'input',
                'size' => '8',
                'max' => '20',
                'eval' => 'int',
                'default' => '0',
                'checkbox' => '0'
            )
        ),
        'set_id' => Array (
            'label' => 'LLL:EXT:indexed_search/locallang_db.php:index_config.set_id',
            'config' => Array (
                'type' => 'none',
            )
        ),
    ),
    'types' => Array (
        '0' => Array('showitem' => 'title;;1;;2-2-2, description, timer_next_indexing, timer_offset, timer_frequency, set_id, type;;;;3-3-3'),
        '1' => Array('showitem' => 'title;;1;;2-2-2, description, timer_next_indexing, timer_offset, timer_frequency, set_id, type;;;;3-3-3, table2index;;;;3-3-3, alternative_source_pid, fieldlist, get_params, chashcalc,recordsbatch,records_indexonchange'),
        '2' => Array('showitem' => 'title;;1;;2-2-2, description, timer_next_indexing, timer_offset, timer_frequency, set_id, type;;;;3-3-3, filepath;;;;3-3-3, extensions, depth'),
        '3' => Array('showitem' => 'title;;1;;2-2-2, description, timer_next_indexing, timer_offset, timer_frequency, set_id, type;;;;3-3-3, externalUrl;;;;3-3-3, depth, url_deny'),
        '4' => Array('showitem' => 'title;;1;;2-2-2, description, timer_next_indexing, timer_offset, timer_frequency, set_id, type;;;;3-3-3, alternative_source_pid;LLL:EXT:indexed_search/locallang_db.php:index_config.rootpage;;;3-3-3, depth'),
        '5' => Array('showitem' => 'title;;;;2-2-2, description, type;;;;3-3-3, indexcfgs;;;;3-3-3'),
    ),
    'palettes' => Array (
        '1' => Array('showitem' => 'starttime,hidden')
    )
);
?>
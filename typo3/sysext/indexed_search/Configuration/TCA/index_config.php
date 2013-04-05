<?php
return array(
	'ctrl' => array(
		'title' => 'LLL:EXT:indexed_search/locallang_db.php:index_config',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'type' => 'type',
		'default_sortby' => 'ORDER BY crdate',
		'enablecolumns' => array(
			'disabled' => 'hidden',
			'starttime' => 'starttime'
		),
		'iconfile' => 'default.gif'
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'hidden, starttime, title, description, type, depth, table2index, alternative_source_pid, get_params, chashcalc, filepath, extensions'
	),
	'interface' => array(
		'showRecordFieldList' => 'hidden,starttime,title,description,type,depth,table2index,alternative_source_pid,get_params,chashcalc,filepath,extensions'
	),
	'columns' => array(
		'hidden' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.disable',
			'config' => array(
				'type' => 'check',
				'default' => '1'
			)
		),
		'starttime' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.starttime',
			'config' => array(
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'default' => '0',
				'checkbox' => '0'
			)
		),
		'title' => array(
			'label' => 'LLL:EXT:indexed_search/locallang_db.php:index_config.title',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'required'
			)
		),
		'description' => array(
			'label' => 'LLL:EXT:indexed_search/locallang_db.php:index_config.description',
			'config' => array(
				'type' => 'text',
				'cols' => '30',
				'rows' => '2'
			)
		),
		'type' => array(
			'label' => 'LLL:EXT:indexed_search/locallang_db.php:index_config.type',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('LLL:EXT:indexed_search/locallang_db.php:index_config.type.I.0', '0'),
					array('LLL:EXT:indexed_search/locallang_db.php:index_config.type.I.1', '1'),
					array('LLL:EXT:indexed_search/locallang_db.php:index_config.type.I.2', '2'),
					array('LLL:EXT:indexed_search/locallang_db.php:index_config.type.I.3', '3'),
					array('LLL:EXT:indexed_search/locallang_db.php:index_config.type.I.4', '4'),
					array('LLL:EXT:indexed_search/locallang_db.php:index_config.type.I.5', '5')
				),
				'size' => 1,
				'maxitems' => 1
			)
		),
		'depth' => array(
			'label' => 'LLL:EXT:indexed_search/locallang_db.php:index_config.depth',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('LLL:EXT:indexed_search/locallang_db.php:index_config.depth.I.0', '0'),
					array('LLL:EXT:indexed_search/locallang_db.php:index_config.depth.I.1', '1'),
					array('LLL:EXT:indexed_search/locallang_db.php:index_config.depth.I.2', '2'),
					array('LLL:EXT:indexed_search/locallang_db.php:index_config.depth.I.3', '3')
				),
				'size' => 1,
				'maxitems' => 1
			)
		),
		'table2index' => array(
			'label' => 'LLL:EXT:indexed_search/locallang_db.php:index_config.table2index',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('LLL:EXT:indexed_search/locallang_db.php:index_config.table2index.I.0', '0')
				),
				'special' => 'tables',
				'size' => 1,
				'maxitems' => 1
			)
		),
		'alternative_source_pid' => array(
			'label' => 'LLL:EXT:indexed_search/locallang_db.php:index_config.alternative_source_pid',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'pages',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
				'wizards' => array(
					'suggest' => array(
						'type' => 'suggest'
					)
				)
			)
		),
		'indexcfgs' => array(
			'label' => 'LLL:EXT:indexed_search/locallang_db.php:index_config.indexcfgs',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'index_config,pages',
				'size' => 5,
				'minitems' => 0,
				'maxitems' => 200,
				'wizards' => array(
					'suggest' => array(
						'type' => 'suggest'
					)
				)
			)
		),
		'get_params' => array(
			'label' => 'LLL:EXT:indexed_search/locallang_db.php:index_config.get_params',
			'config' => array(
				'type' => 'input',
				'size' => '30'
			)
		),
		'fieldlist' => array(
			'label' => 'LLL:EXT:indexed_search/locallang_db.php:index_config.fields',
			'config' => array(
				'type' => 'input',
				'size' => '30'
			)
		),
		'externalUrl' => array(
			'label' => 'LLL:EXT:indexed_search/locallang_db.php:index_config.externalUrl',
			'config' => array(
				'type' => 'input',
				'size' => '30'
			)
		),
		'chashcalc' => array(
			'label' => 'LLL:EXT:indexed_search/locallang_db.php:index_config.chashcalc',
			'config' => array(
				'type' => 'check'
			)
		),
		'filepath' => array(
			'label' => 'LLL:EXT:indexed_search/locallang_db.php:index_config.filepath',
			'config' => array(
				'type' => 'input',
				'size' => '30'
			)
		),
		'extensions' => array(
			'label' => 'LLL:EXT:indexed_search/locallang_db.php:index_config.extensions',
			'config' => array(
				'type' => 'input',
				'size' => '30'
			)
		),
		'url_deny' => array(
			'label' => 'LLL:EXT:indexed_search/locallang_db.php:index_config.url_deny',
			'config' => array(
				'type' => 'text',
				'cols' => '30',
				'rows' => '2'
			)
		),
		'records_indexonchange' => array(
			'label' => 'LLL:EXT:indexed_search/locallang_db.php:index_config.records_indexonchange',
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),
		'timer_next_indexing' => array(
			'label' => 'LLL:EXT:indexed_search/locallang_db.php:index_config.timer_next_indexing',
			'config' => array(
				'type' => 'input',
				'size' => '12',
				'max' => '20',
				'eval' => 'datetime',
				'default' => '0',
				'checkbox' => '0'
			)
		),
		'timer_offset' => array(
			'label' => 'LLL:EXT:indexed_search/locallang_db.php:index_config.timer_offset',
			'config' => array(
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'time',
				'default' => 3600
			)
		),
		'timer_frequency' => array(
			'label' => 'LLL:EXT:indexed_search/locallang_db.php:index_config.timer_frequency',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('LLL:EXT:indexed_search/locallang_db.php:index_config.timer_frequency.I.0', '3600'),
					array('LLL:EXT:indexed_search/locallang_db.php:index_config.timer_frequency.I.1', '86400'),
					array('LLL:EXT:indexed_search/locallang_db.php:index_config.timer_frequency.I.2', '604800')
				),
				'size' => 1,
				'maxitems' => 1,
				'default' => 86400
			)
		),
		'recordsbatch' => array(
			'label' => 'LLL:EXT:indexed_search/locallang_db.php:index_config.recordsbatch',
			'config' => array(
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'int',
				'default' => '0',
				'checkbox' => '0'
			)
		),
		'set_id' => array(
			'label' => 'LLL:EXT:indexed_search/locallang_db.php:index_config.set_id',
			'config' => array(
				'type' => 'none'
			)
		)
	),
	'types' => array(
		'0' => array('showitem' => 'title;;1;;2-2-2, description, timer_next_indexing, timer_offset, timer_frequency, set_id, type;;;;3-3-3'),
		'1' => array('showitem' => 'title;;1;;2-2-2, description, timer_next_indexing, timer_offset, timer_frequency, set_id, type;;;;3-3-3, table2index;;;;3-3-3, alternative_source_pid, fieldlist, get_params, chashcalc,recordsbatch,records_indexonchange'),
		'2' => array('showitem' => 'title;;1;;2-2-2, description, timer_next_indexing, timer_offset, timer_frequency, set_id, type;;;;3-3-3, filepath;;;;3-3-3, extensions, depth'),
		'3' => array('showitem' => 'title;;1;;2-2-2, description, timer_next_indexing, timer_offset, timer_frequency, set_id, type;;;;3-3-3, externalUrl;;;;3-3-3, depth, url_deny'),
		'4' => array('showitem' => 'title;;1;;2-2-2, description, timer_next_indexing, timer_offset, timer_frequency, set_id, type;;;;3-3-3, alternative_source_pid;LLL:EXT:indexed_search/locallang_db.php:index_config.rootpage;;;3-3-3, depth'),
		'5' => array('showitem' => 'title;;;;2-2-2, description, type;;;;3-3-3, indexcfgs;;;;3-3-3')
	),
	'palettes' => array(
		'1' => array('showitem' => 'starttime,hidden')
	)
);
?>
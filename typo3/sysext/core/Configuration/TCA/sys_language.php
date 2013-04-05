<?php
return array(
	'ctrl' => array(
		'label' => 'title',
		'tstamp' => 'tstamp',
		'default_sortby' => 'ORDER BY title',
		'title' => 'LLL:EXT:lang/locallang_tca.xlf:sys_language',
		'adminOnly' => 1,
		'rootLevel' => 1,
		'enablecolumns' => array(
			'disabled' => 'hidden'
		),
		'typeicon_classes' => array(
			'default' => 'mimetypes-x-sys_language'
		),
		'versioningWS_alwaysAllowLiveEdit' => TRUE
	),
	'interface' => array(
		'showRecordFieldList' => 'hidden,title'
	),
	'columns' => array(
		'title' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.language',
			'config' => array(
				'type' => 'input',
				'size' => '35',
				'max' => '80',
				'eval' => 'trim,required'
			)
		),
		'hidden' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.disable',
			'exclude' => 1,
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),
		'static_lang_isocode' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_language.isocode',
			'displayCond' => 'EXT:static_info_tables:LOADED:true',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0)
				),
				'foreign_table' => 'static_languages',
				'foreign_table_where' => 'AND static_languages.pid=0 ORDER BY static_languages.lg_name_en',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1
			)
		),
		'flag' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_language.flag',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0, '')
				),
				'selicon_cols' => 16,
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1
			)
		)
	),
	'types' => array(
		'1' => array('showitem' => 'hidden;;;;1-1-1,title;;;;2-2-2,static_lang_isocode,flag')
	)
);
?>
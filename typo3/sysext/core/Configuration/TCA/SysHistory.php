<?php
return array(
	'ctrl' => array(
		'title' => 'LLL:EXT:lang/locallang_tca.xlf:sys_history',
		'label' => 'tablename',
		'tstamp' => 'tstamp',
		'adminOnly' => TRUE,
		'rootLevel' => TRUE,
		'hideTable' => TRUE,
		'default_sortby' => 'uid DESC',
	),
	'columns' => array(
		'sys_log_uid' => array(
			'label' => 'sys_log_uid',
			'config' => array(
				'type' => 'input'
			)
		),
		'history_data' => array(
			'label' => 'history_data',
			'config' => array(
				'type' => 'input'
			)
		),
		'fieldlist' => array(
			'label' => 'fieldlist',
			'config' => array(
				'type' => 'input'
			)
		),
		'recuid' => array(
			'label' => 'recuid',
			'config' => array(
				'type' => 'input'
			)
		),
		'tablename' => array(
			'label' => 'tablename',
			'config' => array(
				'type' => 'input'
			)
		),
		'tstamp' => array(
			'label' => 'tstamp',
			'config' => array(
				'type' => 'input'
			)
		),
		'history_files' => array(
			'label' => 'history_files',
			'config' => array(
				'type' => 'input'
			)
		),
		'snapshot' => array(
			'label' => 'snapshot',
			'config' => array(
				'type' => 'input'
			)
		)
	),
	'types' => array(
		'1' => array(
			'showitem' => 'sys_log_uid, history_data, fieldlist, recuid, tablename, tstamp, history_files, snapshot'
		)
	)
);
?>
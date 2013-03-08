<?php
return array(
	'ctrl' => array(
		'title' => 'LLL:EXT:lang/locallang_tca.xlf:sys_log',
		'label' => 'details',
		'tstamp' => 'tstamp',
		'adminOnly' => TRUE,
		'rootLevel' => TRUE,
		'hideTable' => TRUE,
		'default_sortby' => 'uid DESC',
	),
	'columns' => array(
		'tstamp' => array(
			'label' => 'tstamp',
			'config' => array(
				'type' => 'input'
			)
		),
		'userid' => array(
			'label' => 'userid',
			'config' => array(
				'type' => 'input'
			)
		),
		'action' => array(
			'label' => 'action',
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
		'recpid' => array(
			'label' => 'recpid',
			'config' => array(
				'type' => 'input'
			)
		),
		'error' => array(
			'label' => 'error',
			'config' => array(
				'type' => 'input'
			)
		),
		'details' => array(
			'label' => 'details',
			'config' => array(
				'type' => 'input'
			)
		),
		'type' => array(
			'label' => 'type',
			'config' => array(
				'type' => 'input'
			)
		),
		'details_nr' => array(
			'label' => 'details_nr',
			'config' => array(
				'type' => 'input'
			)
		),
		'IP' => array(
			'label' => 'IP',
			'config' => array(
				'type' => 'input'
			)
		),
		'log_data' => array(
			'label' => 'log_data',
			'config' => array(
				'type' => 'input'
			)
		),
		'event_pid' => array(
			'label' => 'event_pid',
			'config' => array(
				'type' => 'input'
			)
		),
		'workspace' => array(
			'label' => 'workspace',
			'config' => array(
				'type' => 'input'
			)
		),
		'NEWid' => array(
			'label' => 'NEWid',
			'config' => array(
				'type' => 'input'
			)
		)
	),
	'types' => array(
		'1' => array(
			'showitem' => 'tstamp, userid, action, recuid, tablename, recpid, error, details, type, details_nr, IP, log_data, event_pid, workspace, NEWid'
		)
	)
);
?>
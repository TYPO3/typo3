<?php

// ******************************************************************
// sys_action
// ******************************************************************
$TCA['sys_action'] = Array (
	'ctrl' => $TCA['sys_action']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'hidden,title,type,description,assign_to_groups'
	),
	'columns' => Array (
		'title' => Array (
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.title',
			'config' => Array (
				'type' => 'input',
				'size' => '25',
				'max' => '256',
				'eval' => 'trim,required'
			)
		),
		'description' => Array (
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.description',
			'config' => Array (
				'type' => 'text',
				'rows' => 10,
				'cols' => 48
			)
		),
		'hidden' => Array (
			'label' => 'Deactivated:',
			'config' => Array (
				'type' => 'check'
			)
		),
		'type' => Array (
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.type',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', '0'),
					Array('Create Backend User', '1'),
					Array('SQL-query', '2'),
					Array('Record list', '3'),
					Array('Edit records', '4'),
					Array('New Record', '5')
				)
			)
		),
		'assign_to_groups' => Array (
			'label' => 'Assign action to groups:',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'be_groups',
				'foreign_table_where' => 'ORDER BY be_groups.title',
				'MM' => 'sys_action_asgr_mm',
				'size' => '5',
				'minitems' => '0',
				'maxitems' => '200'
			)
		),
		't1_userprefix' => Array (
			'label' =>  'User prefix:',
			'config' => Array (
				'type' => 'input',
				'size' => '25',
				'max' => '10',
				'eval' => 'trim'
			)
		),
		't1_allowed_groups' => Array (
			'label' => 'Groups which may be assigned through the action:',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'be_groups',
				'foreign_table_where' => 'ORDER BY be_groups.title',
				'size' => '2',
				'maxitems' => '20'
			)
		),
		't1_create_user_dir' => Array (
			'label' => 'Create User Home Directory:',
			'config' => Array (
				'type' => 'check'
			)
		),
		't1_copy_of_user' => Array (
			'label' => 'Template user:',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'be_users',
				'size' => '1',
				'maxitems' => '1',
				'minitems' => '1',
				'show_thumbs' => '1'
			)
		),
		't3_listPid' => Array (
			'label' => 'List pid:',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'pages',
				'size' => '1',
				'maxitems' => '1',
				'minitems' => '1',
				'show_thumbs' => '1'
			)
		),
		't3_tables' => Array (
			'label' => 'List only table:',
			'config' => Array (
				'type' => 'select',
				'special' => 'tables',
				'items' => Array (
					Array('','')
				)
			)
		),
		't4_recordsToEdit' => Array (
			'label' => 'Records to edit:',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => '*',
				'prepend_tname' => 1,
				'size' => '5',
				'maxitems' => '50',
				'minitems' => '1',
				'show_thumbs' => '1'
			)
		),

	),
	'types' => Array (
		'0' => Array('showitem' => 'hidden;;;;1-1-1,type,title;;;;2-2-2'),
		'1' => Array('showitem' => 'hidden;;;;1-1-1,type,title;;;;2-2-2,description;;;;3-3-3,assign_to_groups,--div--,t1_userprefix;;;;5-5-5,t1_copy_of_user,t1_allowed_groups,t1_create_user_dir'),
		'2' => Array('showitem' => 'hidden;;;;1-1-1,type,title;;;;2-2-2,description;;;;3-3-3,assign_to_groups,--div--,'),
		'3' => Array('showitem' => 'hidden;;;;1-1-1,type,title;;;;2-2-2,description;;;;3-3-3,assign_to_groups,--div--,t3_listPid;;;;5-5-5,t3_tables;'),
		'4' => Array('showitem' => 'hidden;;;;1-1-1,type,title;;;;2-2-2,description;;;;3-3-3,assign_to_groups,--div--,t4_recordsToEdit;;;;5-5-5'),
		'5' => Array('showitem' => 'hidden;;;;1-1-1,type,title;;;;2-2-2,description;;;;3-3-3,assign_to_groups,--div--,t3_listPid;Where to create records:;;;5-5-5,t3_tables;Create records in table:'),
	)
);
?>
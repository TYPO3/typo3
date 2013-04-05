<?php
return array(
	'ctrl' => array(
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'delete' => 'deleted',
		'prependAtCopy' => 'LLL:EXT:lang/locallang_general.xlf:LGL.prependAtCopy',
		'enablecolumns' => array(
			'disabled' => 'hidden'
		),
		'title' => 'LLL:EXT:cms/locallang_tca.xlf:fe_groups',
		'typeicon_classes' => array(
			'default' => 'status-user-group-frontend'
		),
		'useColumnsForDefaultValues' => 'lockToDomain',
		'dividers2tabs' => 1,
		'searchFields' => 'title,description'
	),
	'interface' => array(
		'showRecordFieldList' => 'title,hidden,subgroup,lockToDomain,description'
	),
	'columns' => array(
		'hidden' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.disable',
			'exclude' => 1,
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),
		'title' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:fe_groups.title',
			'config' => array(
				'type' => 'input',
				'size' => '20',
				'max' => '50',
				'eval' => 'trim,required'
			)
		),
		'subgroup' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:fe_groups.subgroup',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'fe_groups',
				'foreign_table_where' => 'AND NOT(fe_groups.uid = ###THIS_UID###) AND fe_groups.hidden=0 ORDER BY fe_groups.title',
				'size' => 6,
				'autoSizeMax' => 10,
				'minitems' => 0,
				'maxitems' => 20
			)
		),
		'lockToDomain' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:fe_groups.lockToDomain',
			'config' => array(
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '50'
			)
		),
		'description' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.description',
			'config' => array(
				'type' => 'text',
				'rows' => 5,
				'cols' => 48
			)
		),
		'TSconfig' => array(
			'exclude' => 1,
			'label' => 'TSconfig:',
			'config' => array(
				'type' => 'text',
				'cols' => '40',
				'rows' => '10',
				'wizards' => array(
					'_PADDING' => 4,
					'0' => array(
						'title' => 'TSconfig QuickReference',
						'script' => 'wizard_tsconfig.php?mode=fe_users',
						'icon' => 'wizard_tsconfig.gif',
						'JSopenParams' => 'height=500,width=780,status=0,menubar=0,scrollbars=1'
					)
				),
				'softref' => 'TSconfig'
			),
			'defaultExtras' => 'fixed-font : enable-tab'
		)
	),
	'types' => array(
		'0' => array('showitem' => '
			hidden;;;;1-1-1,title;;;;2-2-2,description,subgroup;;;;3-3-3,
			--div--;LLL:EXT:cms/locallang_tca.xlf:fe_groups.tabs.options, lockToDomain;;;;1-1-1, TSconfig;;;;2-2-2,
			--div--;LLL:EXT:cms/locallang_tca.xlf:fe_groups.tabs.extended
		')
	)
);
?>
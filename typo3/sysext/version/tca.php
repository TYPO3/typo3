<?php
/**
 * System workspaces - Defines the offline workspaces available to users in TYPO3.
 */
$TCA['sys_workspace'] = array(
	'ctrl' => $TCA['sys_workspace']['ctrl'],
	'columns' => array(
		'title' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.title',
			'config' => array(
				'type' => 'input',
				'size' => '20',
				'max' => '30',
				'eval' => 'required,trim,unique'
			)
		),
		'description' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.description',
			'config' => array(
				'type' => 'text',
				'rows' => 5,
				'cols' => 30
			)
		),
		'adminusers' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:sys_workspace.adminusers',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'be_users',
				'size' => '3',
				'maxitems' => '10',
				'autoSizeMax' => 10,
				'show_thumbs' => '1',
				'wizards' => array(
					'suggest' => array(
						'type' => 'suggest',
					)
				)
			)
		),
		'members' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:sys_workspace.members',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'be_users,be_groups',
				'prepend_tname' => 1,
				'size' => '3',
				'maxitems' => '100',
				'autoSizeMax' => 10,
				'show_thumbs' => '1',
				'wizards' => array(
					'suggest' => array(
						'type' => 'suggest',
					)
				)
			)
		),
		'reviewers' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:sys_workspace.reviewers',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'be_users,be_groups',
				'prepend_tname' => 1,
				'size' => '3',
				'maxitems' => '100',
				'autoSizeMax' => 10,
				'show_thumbs' => '1',
				'wizards' => array(
					'suggest' => array(
						'type' => 'suggest',
					)
				)
			)
		),
		'db_mountpoints' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:db_mountpoints',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'db',
					'allowed' => 'pages',
				'size' => '3',
				'maxitems' => '10',
				'autoSizeMax' => 10,
				'show_thumbs' => '1',
				'wizards' => array(
					'suggest' => array(
						'type' => 'suggest',
					)
				)
			)
		),
		'file_mountpoints' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:file_mountpoints',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'sys_filemounts',
				'foreign_table_where' => ' AND sys_filemounts.pid=0 ORDER BY sys_filemounts.title',
				'size' => '3',
				'maxitems' => '10',
				'autoSizeMax' => 10,
				'renderMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['accessListRenderMode'],
				'iconsInOptionTags' => 1,
			)
		),
		'publish_time' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:sys_workspace.publish_time',
			'config' => array(
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'datetime',
				'default' => '0',
				'checkbox' => '0'
			)
		),
		'unpublish_time' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:sys_workspace.unpublish_time',
			'config' => array(
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'datetime',
				'checkbox' => '0',
				'default' => '0',
				'range' => array(
					'upper' => mktime(0,0,0,12,31,2020),
				)
			)
		),
		'freeze' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:sys_workspace.freeze',
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),
		'live_edit' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:sys_workspace.live_edit',
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),
		'review_stage_edit' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:sys_workspace.review_stage_edit',
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),
		'disable_autocreate' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:sys_workspace.disable_autocreate',
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),
		'swap_modes' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:sys_workspace.swap_modes',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0),
					array('Swap-Into-Workspace on Auto-publish', 1),
					array('Disable Swap-Into-Workspace', 2)
				),
			)
		),
		'vtypes' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:sys_workspace.vtypes',
			'config' => array(
				'type' => 'check',
				'items' => array(
					array('Element', 0),
					array('Page', 0),
					array('Branch', 0)
				),
			)
		),
		'publish_access' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:sys_workspace.publish_access',
			'config' => array(
				'type' => 'check',
				'items' => array(
					array('Publish only content in publish stage', 0),
					array('Only workspace owner can publish', 0),
				),
			)
		),
		'stagechg_notification' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:sys_workspace.stagechg_notification',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0),
					array('Notify users on next stage only', 1),
					array('Notify all users on any change', 10)
				),
			)
		),
	),
	'types' => array(
		'0' => array('showitem' => 'title,description,
			--div--;LLL:EXT:lang/locallang_tca.xml:sys_filemounts.tabs.users,adminusers,members,reviewers,stagechg_notification,
			--div--;LLL:EXT:lang/locallang_tca.xml:sys_filemounts.tabs.mountpoints,db_mountpoints,file_mountpoints,
			--div--;LLL:EXT:lang/locallang_tca.xml:sys_filemounts.tabs.publishing,publish_time,unpublish_time,
			--div--;LLL:EXT:lang/locallang_tca.xml:sys_filemounts.tabs.other,freeze,live_edit,review_stage_edit,disable_autocreate,swap_modes,vtypes,publish_access'
		)
	)
);

?>
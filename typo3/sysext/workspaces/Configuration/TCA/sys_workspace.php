<?php
return array(
	'ctrl' => array(
		'label' => 'title',
		'tstamp' => 'tstamp',
		'title' => 'LLL:EXT:lang/locallang_tca.xml:sys_workspace',
		'adminOnly' => 1,
		'rootLevel' => 1,
		'delete' => 'deleted',
		'iconfile' => 'sys_workspace.png',
		'typeicon_classes' => array(
			'default' => 'mimetypes-x-sys_workspace'
		),
		'versioningWS_alwaysAllowLiveEdit' => TRUE,
		'dividers2tabs' => TRUE
	),
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
				'allowed' => 'be_users,be_groups',
				'prepend_tname' => 1,
				'size' => '3',
				'maxitems' => '10',
				'autoSizeMax' => 10,
				'show_thumbs' => '1',
				'wizards' => array(
					'suggest' => array(
						'type' => 'suggest'
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
						'type' => 'suggest'
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
				'maxitems' => 25,
				'autoSizeMax' => 10,
				'show_thumbs' => '1',
				'wizards' => array(
					'suggest' => array(
						'type' => 'suggest'
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
				'maxitems' => 25,
				'autoSizeMax' => 10,
				'renderMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['accessListRenderMode'],
				'iconsInOptionTags' => 1
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
					'upper' => mktime(0, 0, 0, 12, 31, 2020)
				)
			),
			'displayCond' => 'FALSE'
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
				)
			)
		),
		'publish_access' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:sys_workspace.publish_access',
			'config' => array(
				'type' => 'check',
				'items' => array(
					array('Publish only content in publish stage', 0),
					array('Only workspace owner can publish', 0)
				)
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
				)
			)
		),
		'custom_stages' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xml:sys_workspace.custom_stages',
			'config' => array(
				'type' => 'inline',
				'foreign_table' => 'sys_workspace_stage',
				'appearance' => 'useSortable,expandSingle',
				'foreign_field' => 'parentid',
				'foreign_table_field' => 'parenttable',
				'minitems' => 0
			),
			'default' => 0
		),
		'edit_notification_mode' => array(
			'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xml:sys_workspace.edit_notification_mode',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xml:sys_workspace.notification_mode.0', 0),
					array('LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xml:sys_workspace.notification_mode.1', 1),
					array('LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xml:sys_workspace.notification_mode.2', 2)
				)
			)
		),
		'edit_notification_defaults' => array(
			'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xml:sys_workspace.edit_notification_defaults',
			'displayCond' => 'FIELD:edit_notification_mode:IN:0,1',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'be_users,be_groups',
				'prepend_tname' => 1,
				'size' => '3',
				'maxitems' => '100',
				'autoSizeMax' => 20,
				'show_thumbs' => '1',
				'wizards' => array(
					'suggest' => array(
						'type' => 'suggest'
					)
				)
			)
		),
		'edit_allow_notificaton_settings' => array(
			'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xml:sys_workspace.edit_allow_notificaton_settings',
			'config' => array(
				'type' => 'check',
				'default' => 1
			)
		),
		'publish_notification_mode' => array(
			'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xml:sys_workspace.publish_notification_mode',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xml:sys_workspace.notification_mode.0', 0),
					array('LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xml:sys_workspace.notification_mode.1', 1),
					array('LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xml:sys_workspace.notification_mode.2', 2)
				)
			)
		),
		'publish_notification_defaults' => array(
			'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xml:sys_workspace.publish_notification_defaults',
			'displayCond' => 'FIELD:publish_notification_mode:IN:0,1',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'be_users,be_groups',
				'prepend_tname' => 1,
				'size' => '3',
				'maxitems' => '100',
				'autoSizeMax' => 20,
				'show_thumbs' => '1',
				'wizards' => array(
					'suggest' => array(
						'type' => 'suggest'
					)
				)
			)
		),
		'publish_allow_notificaton_settings' => array(
			'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xml:sys_workspace.publish_allow_notificaton_settings',
			'config' => array(
				'type' => 'check',
				'default' => 1
			)
		)
	),
	'types' => array(
		'0' => array('showitem' => 'title,description,
			--div--;LLL:EXT:lang/locallang_tca.xml:sys_filemounts.tabs.users,adminusers,members,
			--div--;LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xml:tabs.notification_settings,stagechg_notification,edit_notification_mode,edit_notification_defaults,edit_allow_notificaton_settings,publish_notification_mode,publish_notification_defaults,publish_allow_notificaton_settings,
			--div--;LLL:EXT:lang/locallang_tca.xml:sys_filemounts.tabs.mountpoints,db_mountpoints,file_mountpoints,
			--div--;LLL:EXT:lang/locallang_tca.xml:sys_filemounts.tabs.publishing,publish_time,unpublish_time,
			--div--;LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xml:sys_filemounts.tabs.staging,custom_stages,
			--div--;LLL:EXT:lang/locallang_tca.xml:sys_filemounts.tabs.other,freeze,live_edit,disable_autocreate,swap_modes,publish_access')
	)
);
?>
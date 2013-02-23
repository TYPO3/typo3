<?php
return array(
	'ctrl' => array(
		'label' => 'title',
		'tstamp' => 'tstamp',
		'sortby' => 'sorting',
		'title' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xml:sys_workspace_stage',
		'adminOnly' => 1,
		'rootLevel' => 1,
		'hideTable' => TRUE,
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
				'eval' => 'required,trim'
			)
		),
		'responsible_persons' => array(
			'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xml:sys_workspace_stage.responsible_persons',
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
		'default_mailcomment' => array(
			'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xml:sys_workspace_stage.default_mailcomment',
			'config' => array(
				'type' => 'text',
				'rows' => 5,
				'cols' => 30
			)
		),
		'parentid' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xml:sys_workspace_stage.parentid',
			'config' => array(
				'type' => 'passthrough'
			)
		),
		'parenttable' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xml:sys_workspace_stage.parenttable',
			'config' => array(
				'type' => 'passthrough'
			)
		),
		'notification_mode' => array(
			'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xml:sys_workspace_stage.notification_mode',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xml:sys_workspace.notification_mode.0', 0),
					array('LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xml:sys_workspace.notification_mode.1', 1),
					array('LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xml:sys_workspace.notification_mode.2', 2)
				)
			)
		),
		'notification_defaults' => array(
			'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xml:sys_workspace_stage.notification_defaults',
			'displayCond' => 'FIELD:notification_mode:IN:0,1',
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
		'allow_notificaton_settings' => array(
			'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xml:sys_workspace_stage.allow_notificaton_settings',
			'config' => array(
				'type' => 'check',
				'default' => 1
			)
		)
	),
	'types' => array(
		'0' => array('showitem' => '
			--div--;LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:tabs.general,title,responsible_persons,
			--div--;LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:tabs.notification_settings,notification_mode,notification_defaults,allow_notificaton_settings,default_mailcomment')
	)
);
?>
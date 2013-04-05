<?php
return array(
	'ctrl' => array(
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'delete' => 'deleted',
		'default_sortby' => 'ORDER BY title',
		'prependAtCopy' => 'LLL:EXT:lang/locallang_general.xlf:LGL.prependAtCopy',
		'adminOnly' => 1,
		'rootLevel' => 1,
		'type' => 'inc_access_lists',
		'typeicon_column' => 'inc_access_lists',
		'typeicons' => array(
			'1' => 'be_groups_lists.gif'
		),
		'typeicon_classes' => array(
			'default' => 'status-user-group-backend'
		),
		'enablecolumns' => array(
			'disabled' => 'hidden'
		),
		'title' => 'LLL:EXT:lang/locallang_tca.xlf:be_groups',
		'useColumnsForDefaultValues' => 'lockToDomain, fileoper_perms',
		'dividers2tabs' => TRUE,
		'versioningWS_alwaysAllowLiveEdit' => TRUE,
		'searchFields' => 'title'
	),
	'interface' => array(
		'showRecordFieldList' => 'title, db_mountpoints, file_mountpoints, fileoper_perms, inc_access_lists, tables_select, tables_modify, pagetypes_select, non_exclude_fields, groupMods, lockToDomain, description'
	),
	'columns' => array(
		'title' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_groups.title',
			'config' => array(
				'type' => 'input',
				'size' => '25',
				'max' => '50',
				'eval' => 'trim,required'
			)
		),
		'db_mountpoints' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:db_mountpoints',
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
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:file_mountpoints',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'sys_filemounts',
				'foreign_table_where' => ' AND sys_filemounts.pid=0 ORDER BY sys_filemounts.title',
				'size' => '3',
				'maxitems' => 25,
				'autoSizeMax' => 10,
				'iconsInOptionTags' => 1,
				'wizards' => array(
					'_PADDING' => 1,
					'_VERTICAL' => 1,
					'edit' => array(
						'type' => 'popup',
						'title' => 'LLL:EXT:lang/locallang_tca.xlf:file_mountpoints_edit_title',
						'script' => 'wizard_edit.php',
						'popup_onlyOpenIfSelected' => 1,
						'icon' => 'edit2.gif',
						'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1'
					),
					'add' => array(
						'type' => 'script',
						'title' => 'LLL:EXT:lang/locallang_tca.xlf:file_mountpoints_add_title',
						'icon' => 'add.gif',
						'params' => array(
							'table' => 'sys_filemounts',
							'pid' => '0',
							'setValue' => 'prepend'
						),
						'script' => 'wizard_add.php'
					),
					'list' => array(
						'type' => 'script',
						'title' => 'LLL:EXT:lang/locallang_tca.xlf:file_mountpoints_list_title',
						'icon' => 'list.gif',
						'params' => array(
							'table' => 'sys_filemounts',
							'pid' => '0'
						),
						'script' => 'wizard_list.php'
					)
				)
			)
		),
		'fileoper_perms' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_groups.fileoper_perms',
			'config' => array(
				'type' => 'check',
				'items' => array(
					array('LLL:EXT:lang/locallang_tca.xlf:be_groups.fileoper_perms_general', 0),
					array('LLL:EXT:lang/locallang_tca.xlf:be_groups.fileoper_perms_unzip', 0),
					array('LLL:EXT:lang/locallang_tca.xlf:be_groups.fileoper_perms_diroper_perms', 0),
					array('LLL:EXT:lang/locallang_tca.xlf:be_groups.fileoper_perms_diroper_perms_copy', 0),
					array('LLL:EXT:lang/locallang_tca.xlf:be_groups.fileoper_perms_diroper_perms_delete', 0)
				),
				'default' => '7'
			)
		),
		'workspace_perms' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:workspace_perms',
			'config' => array(
				'type' => 'check',
				'items' => array(
					array('LLL:EXT:lang/locallang_tca.xlf:workspace_perms_live', 0)
				),
				'default' => 0
			)
		),
		'pagetypes_select' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_groups.pagetypes_select',
			'config' => array(
				'type' => 'select',
				'special' => 'pagetypes',
				'size' => '5',
				'autoSizeMax' => 50,
				'maxitems' => 20,
				'renderMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['accessListRenderMode'],
				'iconsInOptionTags' => 1
			)
		),
		'tables_modify' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_groups.tables_modify',
			'config' => array(
				'type' => 'select',
				'special' => 'tables',
				'size' => '5',
				'autoSizeMax' => 50,
				'maxitems' => 100,
				'renderMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['accessListRenderMode'],
				'iconsInOptionTags' => 1
			)
		),
		'tables_select' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_groups.tables_select',
			'config' => array(
				'type' => 'select',
				'special' => 'tables',
				'size' => '5',
				'autoSizeMax' => 50,
				'maxitems' => 100,
				'renderMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['accessListRenderMode'],
				'iconsInOptionTags' => 1
			)
		),
		'non_exclude_fields' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_groups.non_exclude_fields',
			'config' => array(
				'type' => 'select',
				'special' => 'exclude',
				'size' => '25',
				'maxitems' => 1000,
				'autoSizeMax' => 50,
				'renderMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['accessListRenderMode'],
				'itemListStyle' => 'width:500px'
			)
		),
		'explicit_allowdeny' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_groups.explicit_allowdeny',
			'config' => array(
				'type' => 'select',
				'special' => 'explicitValues',
				'maxitems' => 1000,
				'renderMode' => 'checkbox'
			)
		),
		'allowed_languages' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:allowed_languages',
			'config' => array(
				'type' => 'select',
				'special' => 'languages',
				'maxitems' => 1000,
				'renderMode' => 'checkbox'
			)
		),
		'custom_options' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_groups.custom_options',
			'config' => array(
				'type' => 'select',
				'special' => 'custom',
				'maxitems' => 1000,
				'renderMode' => 'checkbox'
			)
		),
		'hidden' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.disable',
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),
		'lockToDomain' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:lockToDomain',
			'config' => array(
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '50',
				'softref' => 'substitute'
			)
		),
		'groupMods' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:userMods',
			'config' => array(
				'type' => 'select',
				'special' => 'modListGroup',
				'size' => '5',
				'autoSizeMax' => 50,
				'maxitems' => 100,
				'renderMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['accessListRenderMode'],
				'iconsInOptionTags' => 1
			)
		),
		'inc_access_lists' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_groups.inc_access_lists',
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),
		'description' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.description',
			'config' => array(
				'type' => 'text',
				'rows' => 5,
				'cols' => 30
			)
		),
		'TSconfig' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:TSconfig',
			'config' => array(
				'type' => 'text',
				'cols' => '40',
				'rows' => '5',
				'wizards' => array(
					'_PADDING' => 4,
					'0' => array(
						'type' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('tsconfig_help') ? 'popup' : '',
						'title' => 'LLL:EXT:lang/locallang_tca.xlf:TSconfig_title',
						'script' => 'wizard_tsconfig.php?mode=beuser',
						'icon' => 'wizard_tsconfig.gif',
						'JSopenParams' => 'height=500,width=780,status=0,menubar=0,scrollbars=1'
					)
				),
				'softref' => 'TSconfig'
			),
			'defaultExtras' => 'fixed-font : enable-tab'
		),
		'hide_in_lists' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_groups.hide_in_lists',
			'config' => array(
				'type' => 'check',
				'default' => 0
			)
		),
		'subgroup' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_groups.subgroup',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'be_groups',
				'foreign_table_where' => 'AND NOT(be_groups.uid = ###THIS_UID###) AND be_groups.hidden=0 ORDER BY be_groups.title',
				'size' => '5',
				'autoSizeMax' => 50,
				'maxitems' => 20,
				'iconsInOptionTags' => 1
			)
		)
	),
	'types' => array(
		'0' => array('showitem' => 'hidden;;;;1-1-1, title;;;;2-2-2, description, subgroup;;;;3-3-3,
			--div--;LLL:EXT:lang/locallang_tca.xlf:be_groups.tabs.base_rights, inc_access_lists;;;;1-1-1,
			--div--;LLL:EXT:lang/locallang_tca.xlf:be_groups.tabs.mounts_and_workspaces, workspace_perms;;;;1-1-1, db_mountpoints;;;;2-2-2, file_mountpoints;;;;3-3-3, fileoper_perms,
			--div--;LLL:EXT:lang/locallang_tca.xlf:be_groups.tabs.options, lockToDomain;;;;1-1-1, hide_in_lists;;;;2-2-2, TSconfig;;;;3-3-3,
			--div--;LLL:EXT:lang/locallang_tca.xlf:be_groups.tabs.extended'),
		'1' => array('showitem' => 'hidden;;;;1-1-1, title;;;;2-2-2, description, subgroup;;;;3-3-3,
			--div--;LLL:EXT:lang/locallang_tca.xlf:be_groups.tabs.base_rights, inc_access_lists;;;;1-1-1, groupMods, tables_select, tables_modify, pagetypes_select, non_exclude_fields, explicit_allowdeny , allowed_languages;;;;2-2-2, custom_options;;;;3-3-3,
			--div--;LLL:EXT:lang/locallang_tca.xlf:be_groups.tabs.mounts_and_workspaces, workspace_perms;;;;1-1-1, db_mountpoints;;;;2-2-2, file_mountpoints;;;;3-3-3, fileoper_perms,
			--div--;LLL:EXT:lang/locallang_tca.xlf:be_groups.tabs.options, lockToDomain;;;;1-1-1, hide_in_lists;;;;2-2-2, TSconfig;;;;3-3-3,
			--div--;LLL:EXT:lang/locallang_tca.xlf:be_groups.tabs.extended')
	)
);
?>
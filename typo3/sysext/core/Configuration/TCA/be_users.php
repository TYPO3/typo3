<?php
return array(
	'ctrl' => array(
		'label' => 'username',
		'tstamp' => 'tstamp',
		'title' => 'LLL:EXT:lang/locallang_tca.xlf:be_users',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'delete' => 'deleted',
		'adminOnly' => 1, // Only admin users can edit
		'rootLevel' => 1,
		'default_sortby' => 'ORDER BY admin, username',
		'enablecolumns' => array(
			'disabled' => 'disable',
			'starttime' => 'starttime',
			'endtime' => 'endtime'
		),
		'type' => 'admin',
		'typeicon_column' => 'admin',
		'typeicons' => array(
			'0' => 'be_users.gif',
			'1' => 'be_users_admin.gif'
		),
		'typeicon_classes' => array(
			'0' => 'status-user-backend',
			'1' => 'status-user-admin',
			'default' => 'status-user-backend'
		),
		'mainpalette' => '1',
		'useColumnsForDefaultValues' => 'usergroup,lockToDomain,options,db_mountpoints,file_mountpoints,file_permissions,userMods',
		'dividers2tabs' => TRUE,
		'versioningWS_alwaysAllowLiveEdit' => TRUE,
		'searchFields' => 'username,email,realName'
	),
	'interface' => array(
		'showRecordFieldList' => 'username,usergroup,db_mountpoints,file_mountpoints,admin,options,file_permissions,userMods,lockToDomain,realName,email,disable,starttime,endtime,lastlogin'
	),
	'columns' => array(
		'username' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_users.username',
			'config' => array(
				'type' => 'input',
				'size' => '20',
				'max' => '50',
				'eval' => 'nospace,lower,unique,required'
			)
		),
		'password' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_users.password',
			'config' => array(
				'type' => 'input',
				'size' => '20',
				'max' => '40',
				'eval' => 'required,md5,password'
			)
		),
		'usergroup' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_users.usergroup',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'be_groups',
				'foreign_table_where' => 'ORDER BY be_groups.title',
				'size' => '5',
				'maxitems' => '20',
				'iconsInOptionTags' => 1,
				'wizards' => array(
					'_PADDING' => 1,
					'_VERTICAL' => 1,
					'edit' => array(
						'type' => 'popup',
						'title' => 'LLL:EXT:lang/locallang_tca.xlf:be_users.usergroup_edit_title',
						'module' => array(
							'name' => 'wizard_edit',
						),
						'popup_onlyOpenIfSelected' => 1,
						'icon' => 'edit2.gif',
						'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1'
					),
					'add' => array(
						'type' => 'script',
						'title' => 'LLL:EXT:lang/locallang_tca.xlf:be_users.usergroup_add_title',
						'icon' => 'add.gif',
						'params' => array(
							'table' => 'be_groups',
							'pid' => '0',
							'setValue' => 'prepend'
						),
						'module' => array(
							'name' => 'wizard_add'
						)
					),
					'list' => array(
						'type' => 'script',
						'title' => 'LLL:EXT:lang/locallang_tca.xlf:be_users.usergroup_list_title',
						'icon' => 'list.gif',
						'params' => array(
							'table' => 'be_groups',
							'pid' => '0'
						),
						'module' => array(
							'name' => 'wizard_list'
						)
					)
				)
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
		'db_mountpoints' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_users.options_db_mounts',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'pages',
				'size' => '3',
				'maxitems' => 100,
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
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_users.options_file_mounts',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'sys_filemounts',
				'foreign_table_where' => ' AND sys_filemounts.pid=0 ORDER BY sys_filemounts.title',
				'size' => '3',
				'maxitems' => 100,
				'autoSizeMax' => 10,
				'iconsInOptionTags' => 1,
				'wizards' => array(
					'_PADDING' => 1,
					'_VERTICAL' => 1,
					'edit' => array(
						'type' => 'popup',
						'title' => 'LLL:EXT:lang/locallang_tca.xlf:file_mountpoints_edit_title',
						'module' => array(
							'name' => 'wizard_edit',
						),
						'icon' => 'edit2.gif',
						'popup_onlyOpenIfSelected' => 1,
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
						'module' => array(
							'name' => 'wizard_add'
						)
					),
					'list' => array(
						'type' => 'script',
						'title' => 'LLL:EXT:lang/locallang_tca.xlf:file_mountpoints_list_title',
						'icon' => 'list.gif',
						'params' => array(
							'table' => 'sys_filemounts',
							'pid' => '0'
						),
						'module' => array(
							'name' => 'wizard_list'
						)
					)
				)
			)
		),
		'email' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.email',
			'config' => array(
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '80',
				'softref' => 'email[subst]'
			)
		),
		'realName' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.name',
			'config' => array(
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '80'
			)
		),
		'disable' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.disable',
			'config' => array(
				'type' => 'check'
			)
		),
		'disableIPlock' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_users.disableIPlock',
			'config' => array(
				'type' => 'check'
			)
		),
		'admin' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_users.admin',
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),
		'options' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_users.options',
			'config' => array(
				'type' => 'check',
				'items' => array(
					array('LLL:EXT:lang/locallang_tca.xlf:be_users.options_db_mounts', 0),
					array('LLL:EXT:lang/locallang_tca.xlf:be_users.options_file_mounts', 0)
				),
				'default' => '3'
			)
		),
		'file_permissions' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_groups.fileoper_perms',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('LLL:EXT:lang/locallang_tca.xlf:be_groups.file_permissions.folder', '--div--', 'apps-filetree-folder-default'),
					array('LLL:EXT:lang/locallang_tca.xlf:be_groups.file_permissions.folder_read', 'readFolder', 'apps-filetree-folder-default'),
					array('LLL:EXT:lang/locallang_tca.xlf:be_groups.file_permissions.folder_write', 'writeFolder', 'apps-filetree-folder-default'),
					array('LLL:EXT:lang/locallang_tca.xlf:be_groups.file_permissions.folder_add', 'addFolder', 'apps-filetree-folder-default'),
					array('LLL:EXT:lang/locallang_tca.xlf:be_groups.file_permissions.folder_rename', 'renameFolder', 'apps-filetree-folder-default'),
					array('LLL:EXT:lang/locallang_tca.xlf:be_groups.file_permissions.folder_move', 'moveFolder', 'apps-filetree-folder-default'),
					array('LLL:EXT:lang/locallang_tca.xlf:be_groups.file_permissions.folder_copy', 'copyFolder', 'apps-filetree-folder-default'),
					array('LLL:EXT:lang/locallang_tca.xlf:be_groups.file_permissions.folder_delete', 'deleteFolder', 'apps-filetree-folder-default'),
					array('LLL:EXT:lang/locallang_tca.xlf:be_groups.file_permissions.folder_recursivedelete', 'recursivedeleteFolder', 'apps-filetree-folder-default'),
					array('LLL:EXT:lang/locallang_tca.xlf:be_groups.file_permissions.files', '--div--', 'mimetypes-other-other'),
					array('LLL:EXT:lang/locallang_tca.xlf:be_groups.file_permissions.files_read', 'readFile', 'mimetypes-other-other'),
					array('LLL:EXT:lang/locallang_tca.xlf:be_groups.file_permissions.files_write', 'writeFile', 'mimetypes-other-other'),
					array('LLL:EXT:lang/locallang_tca.xlf:be_groups.file_permissions.files_add', 'addFile', 'mimetypes-other-other'),
					array('LLL:EXT:lang/locallang_tca.xlf:be_groups.file_permissions.files_upload', 'files_upload', 'mimetypes-other-other'),
					array('LLL:EXT:lang/locallang_tca.xlf:be_groups.file_permissions.files_rename', 'renameFile', 'mimetypes-other-other'),
					array('LLL:EXT:lang/locallang_tca.xlf:be_groups.file_permissions.files_move', 'moveFile', 'mimetypes-other-other'),
					array('LLL:EXT:lang/locallang_tca.xlf:be_groups.file_permissions.files_copy', 'copyFile', 'mimetypes-other-other'),
					array('LLL:EXT:lang/locallang_tca.xlf:be_groups.fileoper_perms_unzip', 'unzipFile', 'mimetypes-other-other'),
					array('LLL:EXT:lang/locallang_tca.xlf:be_groups.file_permissions.files_delete', 'deleteFile', 'mimetypes-other-other')
				),
				'renderMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['accessListRenderMode'],
				'size' => 16,
				'maxitems' => 16,
				'itemListStyle' => 'width:500px',
				'default' => 'readFolder,writeFolder,addFolder,renameFolder,moveFolder,deleteFolder,readFile,writeFile,addFile,renameFile,moveFile,files_copy,deleteFile'
			)
		),
		'workspace_perms' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:workspace_perms',
			'config' => array(
				'type' => 'check',
				'items' => array(
					array('LLL:EXT:lang/locallang_tca.xlf:workspace_perms_live', 0)
				),
				'default' => 1
			)
		),
		'starttime' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.starttime',
			'config' => array(
				'type' => 'input',
				'size' => '13',
				'max' => '20',
				'eval' => 'datetime',
				'default' => '0'
			)
		),
		'endtime' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.endtime',
			'config' => array(
				'type' => 'input',
				'size' => '13',
				'max' => '20',
				'eval' => 'datetime',
				'default' => '0',
				'range' => array(
					'upper' => mktime(0, 0, 0, 1, 1, 2038)
				)
			)
		),
		'lang' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_users.lang',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('English', '')
				)
			)
		),
		'userMods' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:userMods',
			'config' => array(
				'type' => 'select',
				'special' => 'modListUser',
				'size' => '5',
				'autoSizeMax' => 50,
				'maxitems' => '100',
				'renderMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['accessListRenderMode'],
				'iconsInOptionTags' => 1
			)
		),
		'allowed_languages' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:allowed_languages',
			'config' => array(
				'type' => 'select',
				'special' => 'languages',
				'maxitems' => '1000',
				'renderMode' => 'checkbox'
			)
		),
		'TSconfig' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:TSconfig',
			'config' => array(
				'type' => 'text',
				'cols' => '40',
				'rows' => '5',
				'softref' => 'TSconfig'
			),
			'defaultExtras' => 'fixed-font : enable-tab'
		),
		'createdByAction' => array(
			'config' => array(
				'type' => 'passthrough'
			)
		),
		'lastlogin' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.lastlogin',
			'config' => array(
				'type' => 'input',
				'readOnly' => '1',
				'size' => '12',
				'eval' => 'datetime',
				'default' => 0
			)
		),
		'category_perms' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:category_perms',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'sys_category',
				'foreign_table_where' => ' AND (sys_category.sys_language_uid = 0 OR sys_category.l10n_parent = 0) ORDER BY sys_category.sorting',
				'renderMode' => 'tree',
				'treeConfig' => array(
					'parentField' => 'parent',
					'appearance' => array(
						'expandAll' => FALSE,
						'showHeader' => FALSE,
						'maxLevels' => 99,
					),
				),
				'size' => 10,
				'autoSizeMax' => 20,
				'minitems' => 0,
				'maxitems' => 9999
			)
		)
	),
	'types' => array(
		'0' => array('showitem' => 'disable;;;;1-1-1, username;;;;2-2-2, password, usergroup;;;;3-3-3, admin;;;;1-1-1, realName;;;;3-3-3, email, lang, lastlogin;;;;1-1-1,
			--div--;LLL:EXT:lang/locallang_tca.xlf:be_users.tabs.rights, userMods;;;;2-2-2, allowed_languages,
			--div--;LLL:EXT:lang/locallang_tca.xlf:be_users.tabs.mounts_and_workspaces, workspace_perms;;;;1-1-1, db_mountpoints;;;;2-2-2, options, file_mountpoints;;;;3-3-3, file_permissions, category_perms,
			--div--;LLL:EXT:lang/locallang_tca.xlf:be_users.tabs.options, lockToDomain;;;;1-1-1, disableIPlock, TSconfig;;;;2-2-2,
			--div--;LLL:EXT:lang/locallang_tca.xlf:be_users.tabs.access, starttime;;;;1-1-1,endtime,
			--div--;LLL:EXT:lang/locallang_tca.xlf:be_users.tabs.extended'),
		'1' => array('showitem' => 'disable;;;;1-1-1, username;;;;2-2-2, password, usergroup;;;;3-3-3, admin;;;;1-1-1, realName;;;;3-3-3, email, lang, lastlogin;;;;1-1-1,
			--div--;LLL:EXT:lang/locallang_tca.xlf:be_users.tabs.options, disableIPlock;;;;1-1-1, TSconfig;;;;2-2-2, db_mountpoints;;;;3-3-3, options, file_mountpoints;;;;4-4-4,
			--div--;LLL:EXT:lang/locallang_tca.xlf:be_users.tabs.access, starttime;;;;1-1-1,endtime,
			--div--;LLL:EXT:lang/locallang_tca.xlf:be_users.tabs.extended')
	)
);

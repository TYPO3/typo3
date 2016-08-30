<?php
return [
    'ctrl' => [
        'label' => 'username',
        'descriptionColumn' => 'description',
        'tstamp' => 'tstamp',
        'title' => 'LLL:EXT:lang/locallang_tca.xlf:be_users',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'adminOnly' => 1, // Only admin users can edit
        'rootLevel' => 1,
        'default_sortby' => 'ORDER BY admin, username',
        'enablecolumns' => [
            'disabled' => 'disable',
            'starttime' => 'starttime',
            'endtime' => 'endtime'
        ],
        'type' => 'admin',
        'typeicon_column' => 'admin',
        'typeicon_classes' => [
            '0' => 'status-user-backend',
            '1' => 'status-user-admin',
            'default' => 'status-user-backend'
        ],
        'useColumnsForDefaultValues' => 'usergroup,lockToDomain,options,db_mountpoints,file_mountpoints,file_permissions,userMods',
        'versioningWS_alwaysAllowLiveEdit' => true,
        'searchFields' => 'username,email,realName'
    ],
    'interface' => [
        'showRecordFieldList' => 'username,description,usergroup,db_mountpoints,file_mountpoints,admin,options,file_permissions,userMods,lockToDomain,realName,email,disable,starttime,endtime,lastlogin'
    ],
    'columns' => [
        'username' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_users.username',
            'config' => [
                'type' => 'input',
                'size' => '20',
                'max' => '50',
                'eval' => 'nospace,trim,lower,unique,required',
                'autocomplete' => false,
            ]
        ],
        'description' => [
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.description',
            'config' => [
                'type' => 'text',
                'rows' => 5,
                'cols' => 30,
                'max' => '2000',
            ]
        ],
        'password' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_users.password',
            'config' => [
                'type' => 'input',
                'size' => '20',
                'max' => '40',
                'eval' => 'trim,required,md5,password',
                'autocomplete' => false,
            ]
        ],
        'usergroup' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_users.usergroup',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'be_groups',
                'foreign_table_where' => 'ORDER BY be_groups.title',
                'size' => '5',
                'maxitems' => '20',
                'enableMultiSelectFilterTextfield' => true,
                'wizards' => [
                    '_VERTICAL' => 1,
                    'edit' => [
                        'type' => 'popup',
                        'title' => 'LLL:EXT:lang/locallang_tca.xlf:be_users.usergroup_edit_title',
                        'module' => [
                            'name' => 'wizard_edit',
                        ],
                        'popup_onlyOpenIfSelected' => 1,
                        'icon' => 'actions-open',
                        'JSopenParams' => 'width=800,height=600,status=0,menubar=0,scrollbars=1'
                    ],
                    'add' => [
                        'type' => 'script',
                        'title' => 'LLL:EXT:lang/locallang_tca.xlf:be_users.usergroup_add_title',
                        'icon' => 'actions-add',
                        'params' => [
                            'table' => 'be_groups',
                            'pid' => '0',
                            'setValue' => 'prepend'
                        ],
                        'module' => [
                            'name' => 'wizard_add'
                        ]
                    ],
                    'list' => [
                        'type' => 'script',
                        'title' => 'LLL:EXT:lang/locallang_tca.xlf:be_users.usergroup_list_title',
                        'icon' => 'actions-system-list-open',
                        'params' => [
                            'table' => 'be_groups',
                            'pid' => '0'
                        ],
                        'module' => [
                            'name' => 'wizard_list'
                        ]
                    ]
                ]
            ]
        ],
        'avatar' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_users.avatar',
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
                'avatar',
                ['maxitems' => 1],
                $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
            )
        ],
        'lockToDomain' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:lockToDomain',
            'config' => [
                'type' => 'input',
                'size' => '20',
                'eval' => 'trim',
                'max' => '50',
                'softref' => 'substitute'
            ]
        ],
        'db_mountpoints' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_users.options_db_mounts',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages',
                'size' => '3',
                'maxitems' => 100,
                'autoSizeMax' => 10,
                'show_thumbs' => '1',
                'wizards' => [
                    'suggest' => [
                        'type' => 'suggest'
                    ]
                ]
            ]
        ],
        'file_mountpoints' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_users.options_file_mounts',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'sys_filemounts',
                'foreign_table_where' => ' AND sys_filemounts.pid=0 ORDER BY sys_filemounts.title',
                'size' => '3',
                'maxitems' => 100,
                'autoSizeMax' => 10,
                'wizards' => [
                    '_VERTICAL' => 1,
                    'edit' => [
                        'type' => 'popup',
                        'title' => 'LLL:EXT:lang/locallang_tca.xlf:file_mountpoints_edit_title',
                        'module' => [
                            'name' => 'wizard_edit',
                        ],
                        'icon' => 'actions-open',
                        'popup_onlyOpenIfSelected' => 1,
                        'JSopenParams' => 'width=800,height=600,status=0,menubar=0,scrollbars=1'
                    ],
                    'add' => [
                        'type' => 'script',
                        'title' => 'LLL:EXT:lang/locallang_tca.xlf:file_mountpoints_add_title',
                        'icon' => 'actions-add',
                        'params' => [
                            'table' => 'sys_filemounts',
                            'pid' => '0',
                            'setValue' => 'prepend'
                        ],
                        'module' => [
                            'name' => 'wizard_add'
                        ]
                    ],
                    'list' => [
                        'type' => 'script',
                        'title' => 'LLL:EXT:lang/locallang_tca.xlf:file_mountpoints_list_title',
                        'icon' => 'actions-system-list-open',
                        'params' => [
                            'table' => 'sys_filemounts',
                            'pid' => '0'
                        ],
                        'module' => [
                            'name' => 'wizard_list'
                        ]
                    ]
                ]
            ]
        ],
        'email' => [
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.email',
            'config' => [
                'type' => 'input',
                'size' => '20',
                'eval' => 'trim',
                'max' => '80',
                'softref' => 'email[subst]'
            ]
        ],
        'realName' => [
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.name',
            'config' => [
                'type' => 'input',
                'size' => '20',
                'eval' => 'trim',
                'max' => '80'
            ]
        ],
        'disable' => [
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.disable',
            'config' => [
                'type' => 'check'
            ]
        ],
        'disableIPlock' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_users.disableIPlock',
            'config' => [
                'type' => 'check'
            ]
        ],
        'admin' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_users.admin',
            'config' => [
                'type' => 'check',
                'default' => '0'
            ]
        ],
        'options' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_users.options',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['LLL:EXT:lang/locallang_tca.xlf:be_users.options_db_mounts', 0],
                    ['LLL:EXT:lang/locallang_tca.xlf:be_users.options_file_mounts', 0]
                ],
                'default' => '3'
            ]
        ],
        'file_permissions' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_groups.fileoper_perms',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'items' => [
                    ['LLL:EXT:lang/locallang_tca.xlf:be_groups.file_permissions.folder', '--div--', 'apps-filetree-folder-default'],
                    ['LLL:EXT:lang/locallang_tca.xlf:be_groups.file_permissions.folder_read', 'readFolder', 'apps-filetree-folder-default'],
                    ['LLL:EXT:lang/locallang_tca.xlf:be_groups.file_permissions.folder_write', 'writeFolder', 'apps-filetree-folder-default'],
                    ['LLL:EXT:lang/locallang_tca.xlf:be_groups.file_permissions.folder_add', 'addFolder', 'apps-filetree-folder-default'],
                    ['LLL:EXT:lang/locallang_tca.xlf:be_groups.file_permissions.folder_rename', 'renameFolder', 'apps-filetree-folder-default'],
                    ['LLL:EXT:lang/locallang_tca.xlf:be_groups.file_permissions.folder_move', 'moveFolder', 'apps-filetree-folder-default'],
                    ['LLL:EXT:lang/locallang_tca.xlf:be_groups.file_permissions.folder_copy', 'copyFolder', 'apps-filetree-folder-default'],
                    ['LLL:EXT:lang/locallang_tca.xlf:be_groups.file_permissions.folder_delete', 'deleteFolder', 'apps-filetree-folder-default'],
                    ['LLL:EXT:lang/locallang_tca.xlf:be_groups.file_permissions.folder_recursivedelete', 'recursivedeleteFolder', 'apps-filetree-folder-default'],
                    ['LLL:EXT:lang/locallang_tca.xlf:be_groups.file_permissions.files', '--div--', 'mimetypes-other-other'],
                    ['LLL:EXT:lang/locallang_tca.xlf:be_groups.file_permissions.files_read', 'readFile', 'mimetypes-other-other'],
                    ['LLL:EXT:lang/locallang_tca.xlf:be_groups.file_permissions.files_write', 'writeFile', 'mimetypes-other-other'],
                    ['LLL:EXT:lang/locallang_tca.xlf:be_groups.file_permissions.files_add', 'addFile', 'mimetypes-other-other'],
                    ['LLL:EXT:lang/locallang_tca.xlf:be_groups.file_permissions.files_rename', 'renameFile', 'mimetypes-other-other'],
                    ['LLL:EXT:lang/locallang_tca.xlf:be_groups.file_permissions.files_replace', 'replaceFile', 'mimetypes-other-other'],
                    ['LLL:EXT:lang/locallang_tca.xlf:be_groups.file_permissions.files_move', 'moveFile', 'mimetypes-other-other'],
                    ['LLL:EXT:lang/locallang_tca.xlf:be_groups.file_permissions.files_copy', 'copyFile', 'mimetypes-other-other'],
                    ['LLL:EXT:lang/locallang_tca.xlf:be_groups.fileoper_perms_unzip', 'unzipFile', 'mimetypes-other-other'],
                    ['LLL:EXT:lang/locallang_tca.xlf:be_groups.file_permissions.files_delete', 'deleteFile', 'mimetypes-other-other']
                ],
                'size' => 17,
                'maxitems' => 17,
                'default' => 'readFolder,writeFolder,addFolder,renameFolder,moveFolder,deleteFolder,readFile,writeFile,addFile,renameFile,replaceFile,moveFile,copyFile,deleteFile'
            ]
        ],
        'workspace_perms' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:workspace_perms',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['LLL:EXT:lang/locallang_tca.xlf:workspace_perms_live', 0]
                ],
                'default' => 1
            ]
        ],
        'starttime' => [
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'input',
                'size' => '13',
                'eval' => 'datetime',
                'default' => '0'
            ]
        ],
        'endtime' => [
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'input',
                'size' => '13',
                'eval' => 'datetime',
                'default' => '0',
                'range' => [
                    'upper' => mktime(0, 0, 0, 1, 1, 2038)
                ]
            ]
        ],
        'lang' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_users.lang',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['English', '']
                ]
            ]
        ],
        'userMods' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:userMods',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'special' => 'modListUser',
                'size' => '5',
                'autoSizeMax' => 50,
                'maxitems' => '100',
            ]
        ],
        'allowed_languages' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:allowed_languages',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'special' => 'languages',
                'maxitems' => '1000',
            ]
        ],
        'TSconfig' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:TSconfig',
            'config' => [
                'type' => 'text',
                'cols' => '40',
                'rows' => '5',
                'softref' => 'TSconfig'
            ],
            'defaultExtras' => 'fixed-font : enable-tab'
        ],
        'createdByAction' => [
            'config' => [
                'type' => 'passthrough'
            ]
        ],
        'lastlogin' => [
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.lastlogin',
            'config' => [
                'type' => 'input',
                'readOnly' => '1',
                'size' => '12',
                'eval' => 'datetime',
                'default' => 0
            ]
        ],
        'category_perms' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:category_perms',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectTree',
                'foreign_table' => 'sys_category',
                'foreign_table_where' => ' AND (sys_category.sys_language_uid = 0 OR sys_category.l10n_parent = 0) ORDER BY sys_category.sorting',
                'treeConfig' => [
                    'parentField' => 'parent',
                    'appearance' => [
                        'expandAll' => false,
                        'showHeader' => false,
                        'maxLevels' => 99,
                    ],
                ],
                'size' => 10,
                'autoSizeMax' => 20,
                'minitems' => 0,
                'maxitems' => 9999
            ]
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'disable, username, password, description, avatar, usergroup, admin, realName, email, lang, lastlogin,
			--div--;LLL:EXT:lang/locallang_tca.xlf:be_users.tabs.rights, userMods, allowed_languages,
			--div--;LLL:EXT:lang/locallang_tca.xlf:be_users.tabs.mounts_and_workspaces, workspace_perms, db_mountpoints, options, file_mountpoints, file_permissions, category_perms,
			--div--;LLL:EXT:lang/locallang_tca.xlf:be_users.tabs.options, lockToDomain, disableIPlock, TSconfig,
			--div--;LLL:EXT:lang/locallang_tca.xlf:be_users.tabs.access, starttime,endtime,
			--div--;LLL:EXT:lang/locallang_tca.xlf:be_users.tabs.extended'],
        '1' => ['showitem' => 'disable, username, password, avatar,description, usergroup, admin, realName, email, lang, lastlogin,
			--div--;LLL:EXT:lang/locallang_tca.xlf:be_users.tabs.options, disableIPlock, TSconfig, db_mountpoints, options, file_mountpoints,
			--div--;LLL:EXT:lang/locallang_tca.xlf:be_users.tabs.access, starttime,endtime,
			--div--;LLL:EXT:lang/locallang_tca.xlf:be_users.tabs.extended']
    ]
];

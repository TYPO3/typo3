<?php
return [
    'ctrl' => [
        'label' => 'username',
        'descriptionColumn' => 'description',
        'tstamp' => 'tstamp',
        'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'adminOnly' => true,
        'rootLevel' => 1,
        'default_sortby' => 'admin, username',
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
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.username',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'max' => 50,
                'eval' => 'nospace,trim,lower,unique,required',
                'autocomplete' => false,
            ]
        ],
        'description' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.description',
            'config' => [
                'type' => 'text',
                'rows' => 5,
                'cols' => 30,
                'max' => 2000,
            ]
        ],
        'password' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.password',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'max' => 100,
                'eval' => 'trim,required,password,saltedPassword',
                'autocomplete' => false,
            ]
        ],
        'usergroup' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.usergroup',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'be_groups',
                'foreign_table_where' => 'ORDER BY be_groups.title',
                'size' => 5,
                'maxitems' => 20,
                'enableMultiSelectFilterTextfield' => true,
                'fieldControl' => [
                    'editPopup' => [
                        'disabled' => false,
                        'options' => [
                            'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.usergroup_edit_title',
                        ],
                    ],
                    'addRecord' => [
                        'disabled' => false,
                        'options' => [
                            'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.usergroup_add_title',
                            'setValue' => 'prepend',
                        ],
                    ],
                    'listModule' => [
                        'disabled' => false,
                        'options' => [
                            'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.usergroup_list_title',
                        ],
                    ],
                ],
            ],
        ],
        'avatar' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.avatar',
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
                'avatar',
                ['maxitems' => 1],
                $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
            )
        ],
        'lockToDomain' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:lockToDomain',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim',
                'max' => 50,
                'softref' => 'substitute'
            ]
        ],
        'db_mountpoints' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.options_db_mounts',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages',
                'size' => 3,
                'maxitems' => 100,
                'autoSizeMax' => 10,
            ]
        ],
        'file_mountpoints' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.options_file_mounts',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'enableMultiSelectFilterTextfield' => true,
                'foreign_table' => 'sys_filemounts',
                'foreign_table_where' => ' AND sys_filemounts.pid=0 ORDER BY sys_filemounts.title',
                'size' => 3,
                'maxitems' => 100,
                'autoSizeMax' => 10,
                'fieldControl' => [
                    'editPopup' => [
                        'disabled' => false,
                        'options' => [
                            'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:file_mountpoints_edit_title',
                        ],
                    ],
                    'addRecord' => [
                        'disabled' => false,
                        'options' => [
                            'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:file_mountpoints_add_title',
                            'setValue' => 'prepend',
                        ],
                    ],
                    'listModule' => [
                        'disabled' => false,
                        'options' => [
                            'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:file_mountpoints_list_title',
                        ],
                    ],
                ],
            ],
        ],
        'email' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.email',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim',
                'max' => 255,
                'softref' => 'email[subst]'
            ]
        ],
        'realName' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.name',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim',
                'max' => 80
            ]
        ],
        'disable' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.enabled',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        0 => '',
                        1 => '',
                        'invertStateDisplay' => true
                    ],
                ],
            ]
        ],
        'disableIPlock' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.IPlockState',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxLabeledToggle',
                'items' => [
                    [
                        0 => '',
                        1 => '',
                        'labelChecked' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.enabled',
                        'labelUnchecked' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.disabled',
                        'invertStateDisplay' => true
                    ],
                ],
            ]
        ],
        'admin' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.admin',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
                'items' => [
                    [
                        0 => '',
                        1 => '',
                    ],
                ],
                'fieldInformation' => [
                    'adminIsSystemMaintainer' => [
                        'renderType' => 'adminIsSystemMaintainer',
                    ],
                ],
            ],
        ],
        'options' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.options',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.options_db_mounts', 0],
                    ['LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.options_file_mounts', 0]
                ],
                'default' => 3
            ]
        ],
        'file_permissions' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.fileoper_perms',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'items' => [
                    ['LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.folder', '--div--', 'apps-filetree-folder-default'],
                    ['LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.folder_read', 'readFolder', 'apps-filetree-folder-default'],
                    ['LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.folder_write', 'writeFolder', 'apps-filetree-folder-default'],
                    ['LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.folder_add', 'addFolder', 'apps-filetree-folder-default'],
                    ['LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.folder_rename', 'renameFolder', 'apps-filetree-folder-default'],
                    ['LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.folder_move', 'moveFolder', 'apps-filetree-folder-default'],
                    ['LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.folder_copy', 'copyFolder', 'apps-filetree-folder-default'],
                    ['LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.folder_delete', 'deleteFolder', 'apps-filetree-folder-default'],
                    ['LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.folder_recursivedelete', 'recursivedeleteFolder', 'apps-filetree-folder-default'],
                    ['LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.files', '--div--', 'mimetypes-other-other'],
                    ['LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.files_read', 'readFile', 'mimetypes-other-other'],
                    ['LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.files_write', 'writeFile', 'mimetypes-other-other'],
                    ['LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.files_add', 'addFile', 'mimetypes-other-other'],
                    ['LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.files_rename', 'renameFile', 'mimetypes-other-other'],
                    ['LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.files_replace', 'replaceFile', 'mimetypes-other-other'],
                    ['LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.files_move', 'moveFile', 'mimetypes-other-other'],
                    ['LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.files_copy', 'copyFile', 'mimetypes-other-other'],
                    ['LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.files_delete', 'deleteFile', 'mimetypes-other-other']
                ],
                'size' => 17,
                'maxitems' => 17,
                'default' => 'readFolder,writeFolder,addFolder,renameFolder,moveFolder,deleteFolder,readFile,writeFile,addFile,renameFile,replaceFile,moveFile,copyFile,deleteFile'
            ]
        ],
        'workspace_perms' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:workspace_perms',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 1,
                'items' => [
                    [
                        0 => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:workspace_perms_live',
                        1 => '',
                    ],
                ],
            ]
        ],
        'starttime' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime,int',
                'default' => 0
            ]
        ],
        'endtime' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime,int',
                'default' => 0,
                'range' => [
                    'upper' => mktime(0, 0, 0, 1, 1, 2038)
                ]
            ]
        ],
        'lang' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.lang',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['English', '']
                ]
            ]
        ],
        'userMods' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:userMods',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'special' => 'modListUser',
                'size' => 5,
                'autoSizeMax' => 50,
                'maxitems' => 100,
            ]
        ],
        'allowed_languages' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:allowed_languages',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'special' => 'languages',
            ]
        ],
        'TSconfig' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:TSconfig',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 5,
                'enableTabulator' => true,
                'fixedFont' => true,
            ],
        ],
        'createdByAction' => [
            'config' => [
                'type' => 'passthrough'
            ]
        ],
        'lastlogin' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.lastlogin',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'readOnly' => true,
                'eval' => 'datetime,int',
                'default' => 0
            ]
        ],
        'category_perms' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:category_perms',
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
                'size' => 20,
                'minitems' => 0,
            ]
        ],
    ],
    'types' => [
        '0' => ['showitem' => '
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                admin, username, password, avatar, usergroup, realName, email, lang, lastlogin,
            --div--;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.tabs.rights,
                userMods, allowed_languages,
            --div--;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.tabs.mounts_and_workspaces,
                workspace_perms, db_mountpoints, options, file_mountpoints, file_permissions, category_perms,
            --div--;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.tabs.options,
                lockToDomain, disableIPlock, TSconfig,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                disable,--palette--;;timeRestriction,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                description,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
        '],
        '1' => ['showitem' => '
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                admin, username, password, avatar, usergroup, realName, email, lang, lastlogin,
            --div--;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.tabs.options,
                disableIPlock, TSconfig, db_mountpoints, options, file_mountpoints,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                disable,--palette--;;timeRestriction,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                description,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
        ']
    ],
    'palettes' => [
        'timeRestriction' => ['showitem' => 'starttime, endtime']
    ],
];

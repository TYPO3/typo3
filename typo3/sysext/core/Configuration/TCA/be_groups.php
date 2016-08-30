<?php
return [
    'ctrl' => [
        'label' => 'title',
        'descriptionColumn' => 'description',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'default_sortby' => 'ORDER BY title',
        'prependAtCopy' => 'LLL:EXT:lang/locallang_general.xlf:LGL.prependAtCopy',
        'adminOnly' => 1,
        'rootLevel' => 1,
        'typeicon_classes' => [
            'default' => 'status-user-group-backend'
        ],
        'enablecolumns' => [
            'disabled' => 'hidden'
        ],
        'title' => 'LLL:EXT:lang/locallang_tca.xlf:be_groups',
        'useColumnsForDefaultValues' => 'lockToDomain, file_permissions',
        'versioningWS_alwaysAllowLiveEdit' => true,
        'searchFields' => 'title'
    ],
    'interface' => [
        'showRecordFieldList' => 'title, db_mountpoints, file_mountpoints, file_permissions, tables_select, tables_modify, pagetypes_select, non_exclude_fields, groupMods, lockToDomain, description'
    ],
    'columns' => [
        'title' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_groups.title',
            'config' => [
                'type' => 'input',
                'size' => '25',
                'max' => '50',
                'eval' => 'trim,required'
            ]
        ],
        'db_mountpoints' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:db_mountpoints',
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
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:file_mountpoints',
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
                        'popup_onlyOpenIfSelected' => 1,
                        'icon' => 'actions-open',
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
                'default' => 0
            ]
        ],
        'pagetypes_select' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_groups.pagetypes_select',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'special' => 'pagetypes',
                'size' => '5',
                'autoSizeMax' => 50,
                'maxitems' => 20,
            ]
        ],
        'tables_modify' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_groups.tables_modify',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'special' => 'tables',
                'size' => '5',
                'autoSizeMax' => 50,
                'maxitems' => 100,
            ]
        ],
        'tables_select' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_groups.tables_select',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'special' => 'tables',
                'size' => '5',
                'autoSizeMax' => 50,
                'maxitems' => 100,
            ]
        ],
        'non_exclude_fields' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_groups.non_exclude_fields',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'special' => 'exclude',
                'size' => '25',
                'maxitems' => 1000,
                'autoSizeMax' => 50,
            ]
        ],
        'explicit_allowdeny' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_groups.explicit_allowdeny',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'special' => 'explicitValues',
                'maxitems' => 1000,
            ]
        ],
        'allowed_languages' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:allowed_languages',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'special' => 'languages',
                'maxitems' => 1000,
            ]
        ],
        'custom_options' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_groups.custom_options',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'special' => 'custom',
                'maxitems' => 1000,
            ]
        ],
        'hidden' => [
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.disable',
            'config' => [
                'type' => 'check',
                'default' => '0'
            ]
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
        'groupMods' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:userMods',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'special' => 'modListGroup',
                'size' => '5',
                'autoSizeMax' => 50,
                'maxitems' => 100,
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
        'hide_in_lists' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_groups.hide_in_lists',
            'config' => [
                'type' => 'check',
                'default' => 0
            ]
        ],
        'subgroup' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:be_groups.subgroup',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'be_groups',
                'foreign_table_where' => 'AND NOT(be_groups.uid = ###THIS_UID###) AND be_groups.hidden=0 ORDER BY be_groups.title',
                'size' => '5',
                'autoSizeMax' => 50,
                'maxitems' => 20,
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
        ]
    ],
    'types' => [
        '0' => ['showitem' => 'hidden, title, description, subgroup,
			--div--;LLL:EXT:lang/locallang_tca.xlf:be_groups.tabs.base_rights, groupMods, tables_select, tables_modify, pagetypes_select, non_exclude_fields, explicit_allowdeny, allowed_languages, custom_options,
			--div--;LLL:EXT:lang/locallang_tca.xlf:be_groups.tabs.mounts_and_workspaces, workspace_perms, db_mountpoints, file_mountpoints, file_permissions, category_perms,
			--div--;LLL:EXT:lang/locallang_tca.xlf:be_groups.tabs.options, lockToDomain, hide_in_lists, TSconfig,
			--div--;LLL:EXT:lang/locallang_tca.xlf:be_groups.tabs.extended'],
    ]
];

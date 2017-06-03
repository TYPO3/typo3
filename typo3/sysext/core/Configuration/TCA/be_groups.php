<?php
return [
    'ctrl' => [
        'label' => 'title',
        'descriptionColumn' => 'description',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'default_sortby' => 'title',
        'prependAtCopy' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.prependAtCopy',
        'adminOnly' => true,
        'rootLevel' => 1,
        'typeicon_classes' => [
            'default' => 'status-user-group-backend'
        ],
        'enablecolumns' => [
            'disabled' => 'hidden'
        ],
        'title' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:be_groups',
        'useColumnsForDefaultValues' => 'lockToDomain, file_permissions',
        'versioningWS_alwaysAllowLiveEdit' => true,
        'searchFields' => 'title'
    ],
    'interface' => [
        'showRecordFieldList' => 'title, db_mountpoints, file_mountpoints, file_permissions, tables_select, tables_modify, pagetypes_select, non_exclude_fields, groupMods, lockToDomain, description'
    ],
    'columns' => [
        'title' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:be_groups.title',
            'config' => [
                'type' => 'input',
                'size' => 25,
                'max' => 50,
                'eval' => 'trim,required'
            ]
        ],
        'db_mountpoints' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:db_mountpoints',
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
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:file_mountpoints',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'sys_filemounts',
                'foreign_table_where' => ' AND sys_filemounts.pid=0 ORDER BY sys_filemounts.title',
                'size' => 3,
                'maxitems' => 100,
                'autoSizeMax' => 10,
                'fieldControl' => [
                    'editPopup' => [
                        'disabled' => false,
                        'options' => [
                            'title' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:file_mountpoints_edit_title',
                        ],
                    ],
                    'addRecord' => [
                        'disabled' => false,
                        'options' => [
                            'title' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:file_mountpoints_add_title',
                            'setValue' => 'prepend',
                        ],
                    ],
                    'listModule' => [
                        'disabled' => false,
                        'options' => [
                            'title' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:file_mountpoints_list_title',
                        ],
                    ],
                ],
            ],
        ],
        'file_permissions' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:be_groups.fileoper_perms',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'items' => [
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.folder', '--div--', 'apps-filetree-folder-default'],
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.folder_read', 'readFolder', 'apps-filetree-folder-default'],
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.folder_write', 'writeFolder', 'apps-filetree-folder-default'],
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.folder_add', 'addFolder', 'apps-filetree-folder-default'],
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.folder_rename', 'renameFolder', 'apps-filetree-folder-default'],
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.folder_move', 'moveFolder', 'apps-filetree-folder-default'],
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.folder_copy', 'copyFolder', 'apps-filetree-folder-default'],
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.folder_delete', 'deleteFolder', 'apps-filetree-folder-default'],
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.folder_recursivedelete', 'recursivedeleteFolder', 'apps-filetree-folder-default'],
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.files', '--div--', 'mimetypes-other-other'],
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.files_read', 'readFile', 'mimetypes-other-other'],
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.files_write', 'writeFile', 'mimetypes-other-other'],
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.files_add', 'addFile', 'mimetypes-other-other'],
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.files_rename', 'renameFile', 'mimetypes-other-other'],
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.files_replace', 'replaceFile', 'mimetypes-other-other'],
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.files_move', 'moveFile', 'mimetypes-other-other'],
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.files_copy', 'copyFile', 'mimetypes-other-other'],
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.files_delete', 'deleteFile', 'mimetypes-other-other']
                ],
                'size' => 17,
                'maxitems' => 17,
                'default' => 'readFolder,writeFolder,addFolder,renameFolder,moveFolder,deleteFolder,readFile,writeFile,addFile,renameFile,replaceFile,moveFile,copyFile,deleteFile'
            ]
        ],
        'workspace_perms' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:workspace_perms',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:workspace_perms_live', 0]
                ],
                'default' => 0
            ]
        ],
        'pagetypes_select' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:be_groups.pagetypes_select',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'special' => 'pagetypes',
                'size' => 5,
                'autoSizeMax' => 50,
                'maxitems' => 20,
            ]
        ],
        'tables_modify' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:be_groups.tables_modify',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'special' => 'tables',
                'size' => 5,
                'autoSizeMax' => 50,
                'maxitems' => 100,
            ]
        ],
        'tables_select' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:be_groups.tables_select',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'special' => 'tables',
                'size' => 5,
                'autoSizeMax' => 50,
                'maxitems' => 100,
            ]
        ],
        'non_exclude_fields' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:be_groups.non_exclude_fields',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'special' => 'exclude',
                'size' => 25,
                'autoSizeMax' => 50,
            ]
        ],
        'explicit_allowdeny' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:be_groups.explicit_allowdeny',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'special' => 'explicitValues',
            ]
        ],
        'allowed_languages' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:allowed_languages',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'special' => 'languages',
            ]
        ],
        'custom_options' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:be_groups.custom_options',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'special' => 'custom',
            ]
        ],
        'hidden' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.disable',
            'config' => [
                'type' => 'check',
                'default' => 0
            ]
        ],
        'lockToDomain' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:lockToDomain',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim',
                'max' => 50,
                'softref' => 'substitute'
            ]
        ],
        'groupMods' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:userMods',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'special' => 'modListGroup',
                'size' => 5,
                'autoSizeMax' => 50,
                'maxitems' => 100,
            ]
        ],
        'description' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.description',
            'config' => [
                'type' => 'text',
                'rows' => 5,
                'cols' => 30,
                'max' => 2000,
            ]
        ],
        'TSconfig' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:TSconfig',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 5,
                'enableTabulator' => true,
                'fixedFont' => true,
            ],
        ],
        'hide_in_lists' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:be_groups.hide_in_lists',
            'config' => [
                'type' => 'check',
                'default' => 0
            ]
        ],
        'subgroup' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:be_groups.subgroup',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'be_groups',
                'foreign_table_where' => 'AND NOT(be_groups.uid = ###THIS_UID###) ORDER BY be_groups.title',
                'size' => 5,
                'autoSizeMax' => 50,
                'maxitems' => 20,
            ]
        ],
        'category_perms' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:category_perms',
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
        ]
    ],
    'types' => [
        '0' => ['showitem' => '
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                title,subgroup,
            --div--;LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:be_groups.tabs.base_rights,
                groupMods, tables_select, tables_modify, pagetypes_select, non_exclude_fields, explicit_allowdeny, allowed_languages, custom_options,
            --div--;LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:be_groups.tabs.mounts_and_workspaces,
                workspace_perms, db_mountpoints, file_mountpoints, file_permissions, category_perms,
            --div--;LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:be_groups.tabs.options,
                lockToDomain, TSconfig,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                hidden,hide_in_lists,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                description,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
        '],
    ]
];

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
        'prependAtCopy' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.prependAtCopy',
        'adminOnly' => true,
        'rootLevel' => 1,
        'typeicon_classes' => [
            'default' => 'status-user-group-backend',
        ],
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups',
        'useColumnsForDefaultValues' => 'file_permissions',
        'versioningWS_alwaysAllowLiveEdit' => true,
        'searchFields' => 'title',
    ],
    'columns' => [
        'title' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.title',
            'config' => [
                'type' => 'input',
                'size' => 25,
                'max' => 50,
                'eval' => 'trim,required',
            ],
        ],
        'db_mountpoints' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:db_mountpoints',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages',
                'size' => 3,
                'autoSizeMax' => 10,
            ],
        ],
        'file_mountpoints' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:file_mountpoints',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'sys_filemounts',
                'foreign_table_where' => ' AND sys_filemounts.pid=0',
                'size' => 3,
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
                    ['LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.files_delete', 'deleteFile', 'mimetypes-other-other'],
                ],
                'size' => 17,
                'maxitems' => 17,
                'default' => 'readFolder,writeFolder,addFolder,renameFolder,moveFolder,deleteFolder,readFile,writeFile,addFile,renameFile,replaceFile,moveFile,copyFile,deleteFile',
            ],
        ],
        'workspace_perms' => [
            'exclude' => 1,
            'displayCond' => 'USER:TYPO3\CMS\Core\Hooks\TcaDisplayConditions->isExtensionInstalled:workspaces',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:workspace_perms',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
                'items' => [
                    [
                        0 => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:workspace_perms_live',
                        1 => '',
                    ],
                ],
            ],
        ],
        'pagetypes_select' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.pagetypes_select',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'itemsProcFunc' => \TYPO3\CMS\Core\Hooks\TcaItemsProcessorFunctions::class . '->populateAvailablePageTypes',
                'size' => 5,
                'autoSizeMax' => 50,
            ],
        ],
        'tables_modify' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.tables_modify',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'itemsProcFunc' => \TYPO3\CMS\Core\Hooks\TcaItemsProcessorFunctions::class . '->populateAvailableTables',
                'size' => 5,
                'autoSizeMax' => 50,
            ],
        ],
        'tables_select' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.tables_select',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'itemsProcFunc' => \TYPO3\CMS\Core\Hooks\TcaItemsProcessorFunctions::class . '->populateAvailableTables',
                'size' => 5,
                'autoSizeMax' => 50,
            ],
        ],
        'non_exclude_fields' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.non_exclude_fields',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'itemsProcFunc' => \TYPO3\CMS\Core\Hooks\TcaItemsProcessorFunctions::class . '->populateExcludeFields',
                'size' => 25,
                'autoSizeMax' => 50,
            ],
        ],
        'explicit_allowdeny' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.explicit_allowdeny',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'itemsProcFunc' => \TYPO3\CMS\Core\Hooks\TcaItemsProcessorFunctions::class . '->populateExplicitAuthValues',
            ],
        ],
        'allowed_languages' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:allowed_languages',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'itemsProcFunc' => \TYPO3\CMS\Core\Localization\TcaSystemLanguageCollector::class . '->populateAvailableSiteLanguages',
            ],
        ],
        'custom_options' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.custom_options',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'itemsProcFunc' => \TYPO3\CMS\Core\Hooks\TcaItemsProcessorFunctions::class . '->populateCustomPermissionOptions',
            ],
        ],
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.enabled',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
                'items' => [
                    [
                        0 => '',
                        1 => '',
                        'invertStateDisplay' => true,
                    ],
                ],
            ],
        ],
        'groupMods' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:userMods',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'itemsProcFunc' => \TYPO3\CMS\Core\Hooks\TcaItemsProcessorFunctions::class . '->populateAvailableGroupModules',
                'size' => 5,
                'autoSizeMax' => 50,
            ],
        ],
        'mfa_providers' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:mfa_providers',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'itemsProcFunc' => \TYPO3\CMS\Core\Authentication\Mfa\MfaProviderRegistry::class . '->allowedProvidersItemsProcFunc',
                'size' => 5,
                'autoSizeMax' => 50,
            ],
        ],
        'description' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.description',
            'config' => [
                'type' => 'text',
                'rows' => 5,
                'cols' => 30,
                'max' => 2000,
            ],
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
        'subgroup' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.subgroup',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'be_groups',
                'foreign_table_where' => 'AND NOT(be_groups.uid = ###THIS_UID###)',
                'size' => 5,
                'autoSizeMax' => 50,
            ],
        ],
        'category_perms' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:category_perms',
            'config' => [
                'type' => 'category',
                'relationship' => 'oneToMany',
                'treeConfig' => [
                    'appearance' => [
                        'expandAll' => false,
                        'showHeader' => false,
                    ],
                ],
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => '
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                title,subgroup,
            --div--;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.tabs.base_rights,
                groupMods, mfa_providers, tables_select, tables_modify, pagetypes_select, non_exclude_fields, explicit_allowdeny, allowed_languages, custom_options,
            --div--;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.tabs.mounts_and_workspaces,
                workspace_perms, db_mountpoints, file_mountpoints, file_permissions, category_perms,
            --div--;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.tabs.options,
                TSconfig,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                hidden,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                description,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
        '],
    ],
];

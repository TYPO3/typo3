<?php

return [
    'ctrl' => [
        'label' => 'title',
        'descriptionColumn' => 'description',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'default_sortby' => 'title',
        'prependAtCopy' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.prependAtCopy',
        'adminOnly' => true,
        'groupName' => 'backendaccess',
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
                'required' => true,
                'eval' => 'trim',
            ],
        ],
        'db_mountpoints' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:page_tree_entry_points',
            'description' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:page_tree_entry_points.description',
            'config' => [
                'type' => 'group',
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
                'foreign_table_where' => ' AND {#sys_filemounts}.{#pid}=0',
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
                    ['label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.folder', 'value' => '--div--', 'icon' => 'apps-filetree-folder-default'],
                    ['label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.folder_read', 'value' => 'readFolder', 'icon' => 'apps-filetree-folder-default'],
                    ['label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.folder_write', 'value' => 'writeFolder', 'icon' => 'apps-filetree-folder-default'],
                    ['label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.folder_add', 'value' => 'addFolder', 'icon' => 'apps-filetree-folder-default'],
                    ['label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.folder_rename', 'value' => 'renameFolder', 'icon' => 'apps-filetree-folder-default'],
                    ['label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.folder_move', 'value' => 'moveFolder', 'icon' => 'apps-filetree-folder-default'],
                    ['label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.folder_copy', 'value' => 'copyFolder', 'icon' => 'apps-filetree-folder-default'],
                    ['label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.folder_delete', 'value' => 'deleteFolder', 'icon' => 'apps-filetree-folder-default'],
                    ['label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.folder_recursivedelete', 'value' => 'recursivedeleteFolder', 'icon' => 'apps-filetree-folder-default'],
                    ['label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.files', 'value' => '--div--', 'icon' => 'mimetypes-other-other'],
                    ['label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.files_read', 'value' => 'readFile', 'icon' => 'mimetypes-other-other'],
                    ['label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.files_write', 'value' => 'writeFile', 'icon' => 'mimetypes-other-other'],
                    ['label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.files_add', 'value' => 'addFile', 'icon' => 'mimetypes-other-other'],
                    ['label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.files_rename', 'value' => 'renameFile', 'icon' => 'mimetypes-other-other'],
                    ['label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.files_replace', 'value' => 'replaceFile', 'icon' => 'mimetypes-other-other'],
                    ['label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.files_move', 'value' => 'moveFile', 'icon' => 'mimetypes-other-other'],
                    ['label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.files_copy', 'value' => 'copyFile', 'icon' => 'mimetypes-other-other'],
                    ['label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.file_permissions.files_delete', 'value' => 'deleteFile', 'icon' => 'mimetypes-other-other'],
                ],
                'size' => 17,
                'maxitems' => 17,
                'default' => 'readFolder,writeFolder,addFolder,renameFolder,moveFolder,deleteFolder,readFile,writeFile,addFile,renameFile,replaceFile,moveFile,copyFile,deleteFile',
            ],
        ],
        'workspace_perms' => [
            'displayCond' => 'USER:TYPO3\CMS\Core\Hooks\TcaDisplayConditions->isExtensionInstalled:workspaces',
            'description' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:workspace_perms.description',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:workspace_perms',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
                'items' => [
                    ['label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:workspace_perms_live'],
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
            'description' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.tables_modify.description',
            'config' => [
                'type' => 'select',
                'renderType' => 'tablePermission',
                'selectFieldName' => 'tables_select',
                'itemsProcFunc' => \TYPO3\CMS\Core\Hooks\TcaItemsProcessorFunctions::class . '->populateAvailableTables',
            ],
        ],
        'tables_select' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.tables_select',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'non_exclude_fields' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.non_exclude_fields',
            'description' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.non_exclude_fields.description',
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
            'description' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:allowed_languages.description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'itemsProcFunc' => \TYPO3\CMS\Core\Localization\TcaSystemLanguageCollector::class . '->populateAvailableSiteLanguages',
                'dbFieldLength' => 255,
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
        'groupMods' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:userMods',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'itemsProcFunc' => \TYPO3\CMS\Core\Hooks\TcaItemsProcessorFunctions::class . '->populateAvailableUserModules',
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
                'foreign_table_where' => 'AND NOT({#be_groups}.{#uid} = ###THIS_UID###)',
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
                title, subgroup,
                --palette--;;authentication,
            --div--;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.tabs.record_permissions,
                --palette--;;permissionGeneral,
                --palette--;;permissionSpecific,
                --palette--;;permissionLanguages,
            --div--;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.tabs.module_permissions,
                groupMods, custom_options, workspace_perms,
            --div--;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.tabs.mounts_and_workspaces,
                db_mountpoints, file_mountpoints, file_permissions, category_perms,
            --div--;LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.tabs.options,
                TSconfig,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                hidden,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                description,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
        '],
    ],
    'palettes' => [
        'authentication' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.palettes.authentication',
            'showitem' => 'mfa_providers',
        ],
        'permissionGeneral' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.palettes.permissionGeneral',
            'showitem' => '
                tables_modify,
                --linebreak--, non_exclude_fields
            ',
        ],
        'permissionLanguages' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.palettes.permissionLanguages',
            'showitem' => 'allowed_languages',
        ],
        'permissionSpecific' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.palettes.permissionSpecific',
            'showitem' => '
                pagetypes_select,
                --linebreak--, explicit_allowdeny
            ',
        ],
    ],
];

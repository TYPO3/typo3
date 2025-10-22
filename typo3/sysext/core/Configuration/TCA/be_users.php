<?php

return [
    'ctrl' => [
        'label' => 'username',
        'descriptionColumn' => 'description',
        'tstamp' => 'tstamp',
        'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'adminOnly' => true,
        'rootLevel' => 1,
        'groupName' => 'backendaccess',
        'default_sortby' => 'admin, username',
        'enablecolumns' => [
            'disabled' => 'disable',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'type' => 'admin',
        'typeicon_column' => 'admin',
        'typeicon_classes' => [
            '0' => 'status-user-backend',
            '1' => 'status-user-admin',
            'default' => 'status-user-backend',
        ],
        'useColumnsForDefaultValues' => 'usergroup,options,db_mountpoints,file_mountpoints,file_permissions,userMods',
        'versioningWS_alwaysAllowLiveEdit' => true,
    ],
    'columns' => [
        'username' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.username',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'max' => 50,
                'required' => true,
                'eval' => 'nospace,trim,lower,unique',
                'autocomplete' => false,
            ],
            'authenticationContext' => [
                'group' => 'be.userManagement',
            ],
        ],
        'password' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.password',
            'config' => [
                'type' => 'password',
                'passwordPolicy' => $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordPolicy'] ?? '',
                'size' => 20,
                'required' => true,
            ],
            'authenticationContext' => [
                //'group' => 'be.userManagement',
                'once' => true,
            ],
        ],
        'mfa' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.mfa',
            'config' => [
                // @todo Use a new internal TCA type to prevent raw data being displayed in the backend
                'type' => 'none',
                'renderType' => 'mfaInfo',
            ],
            'authenticationContext' => [
                'group' => 'be.userManagement',
            ],
        ],
        'usergroup' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.usergroup',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'be_groups',
                'size' => 5,
                'dbFieldLength' => 512,
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
            'authenticationContext' => [
                'group' => 'be.userManagement',
            ],
        ],
        'avatar' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.avatar',
            'config' => [
                'type' => 'file',
                'relationship' => 'manyToOne',
                'allowed' => 'common-image-types',
            ],
        ],
        'db_mountpoints' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.options_page_tree_entry_points',
            'description' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.options_page_tree_entry_points.description',
            'config' => [
                'type' => 'group',
                'allowed' => 'pages',
                'size' => 3,
                'maxitems' => 100,
                'autoSizeMax' => 10,
            ],
            'authenticationContext' => [
                'group' => 'be.userManagement',
            ],
        ],
        'file_mountpoints' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.options_file_mounts',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'sys_filemounts',
                'foreign_table_where' => ' AND {#sys_filemounts}.{#pid}=0',
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
            'authenticationContext' => [
                'group' => 'be.userManagement',
            ],
        ],
        'email' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.email',
            'config' => [
                'type' => 'email',
                'size' => 20,
            ],
            'authenticationContext' => [
                'group' => 'be.userManagement',
            ],
        ],
        'realName' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.name',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim',
                'max' => 80,
            ],
        ],
        'admin' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.admin',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
                'fieldInformation' => [
                    'adminIsSystemMaintainer' => [
                        'renderType' => 'adminIsSystemMaintainer',
                    ],
                ],
            ],
            'authenticationContext' => [
                'group' => 'be.userManagement',
            ],
        ],
        'options' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.options',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.options_page_tree_entry_points'],
                    ['label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.options_file_mounts'],
                ],
                'default' => 3,
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
            'authenticationContext' => [
                'group' => 'be.userManagement',
            ],
        ],
        'workspace_perms' => [
            'displayCond' => 'USER:TYPO3\CMS\Core\Hooks\TcaDisplayConditions->isExtensionInstalled:workspaces',
            'description' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:workspace_perms.description',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:workspace_perms',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 1,
                'items' => [
                    ['label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:workspace_perms_live'],
                ],
            ],
            'authenticationContext' => [
                'group' => 'be.userManagement',
            ],
        ],
        'lang' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.lang',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'itemsProcFunc' => \TYPO3\CMS\Core\Localization\TcaSystemLanguageCollector::class . '->populateAvailableSystemLanguagesForBackend',
                'default' => 'default',
                'items' => [
                ],
                'itemGroups' => [
                    'installed' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.languageItemGroups.installed',
                    'unavailable' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.languageItemGroups.unavailable',
                ],
            ],
        ],
        'userMods' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:userMods',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'itemsProcFunc' => \TYPO3\CMS\Core\Hooks\TcaItemsProcessorFunctions::class . '->populateAvailableUserModules',
                'size' => 5,
                'autoSizeMax' => 50,
                'maxitems' => 100,
            ],
            'authenticationContext' => [
                'group' => 'be.userManagement',
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
            'authenticationContext' => [
                'group' => 'be.userManagement',
            ],
        ],
        'TSconfig' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:TSconfig',
            'config' => [
                'type' => 'text',
                'renderType' => 'codeEditor',
                'format' => 'typoscript',
                'cols' => 40,
                'rows' => 5,
                'enableTabulator' => true,
                'fixedFont' => true,
            ],
        ],
        'lastlogin' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.lastlogin',
            'config' => [
                'type' => 'datetime',
                'readOnly' => true,
                'default' => 0,
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
            'authenticationContext' => [
                'group' => 'be.userManagement',
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => '
                --div--;core.form.tabs:general,
                    --palette--;;account,
                    usergroup,
                    --palette--;;authentication,
                --div--;core.form.tabs:personaldata,
                    realName, email, avatar, lang,
                --div--;core.form.tabs:recordpermissions,
                    --palette--;;permissionLanguages,
                --div--;core.form.tabs:modulepermissions,
                    userMods, workspace_perms,
                --div--;core.form.tabs:mounts,
                    db_mountpoints, options, file_mountpoints, file_permissions, category_perms,
                --div--;core.form.tabs:options,
                    TSconfig,
                --div--;core.form.tabs:access,
                    --palette--;;status,
                    --palette--;;timeRestriction,
                --div--;core.form.tabs:notes,
                    description,
                --div--;core.form.tabs:extended,
            ',
            'creationOptions' => [
                'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.types.user',
            ],
        ],
        '1' => [
            'showitem' => '
                --div--;core.form.tabs:general,
                    --palette--;;account,
                    usergroup,
                    --palette--;;authentication,
                --div--;core.form.tabs:personaldata,
                    realName, email, avatar, lang,
                --div--;core.form.tabs:options,
                    TSconfig, db_mountpoints, options, file_mountpoints,
                --div--;core.form.tabs:access,
                    --palette--;;status,
                    --palette--;;timeRestriction,
                --div--;core.form.tabs:notes,
                    description,
                --div--;core.form.tabs:extended,
            ',
            'creationOptions' => [
                'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_users.types.admin',
            ],
        ],
    ],
    'palettes' => [
        'account' => [
            'label' => 'core.form.palettes:account',
            'showitem' => '
                admin,
                --linebreak--, username, password
            ',
        ],
        'authentication' => [
            'label' => 'core.form.palettes:authentication',
            'showitem' => 'mfa',
        ],
        'permissionLanguages' => [
            'label' => 'core.form.palettes:permission_languages',
            'showitem' => 'allowed_languages',
        ],
        'status' => [
            'showitem' => 'disable, lastlogin',
        ],
        'timeRestriction' => [
            'showitem' => 'starttime, endtime',
        ],
    ],
];

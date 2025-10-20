<?php

return [
    'ctrl' => [
        'label' => 'title',
        'tstamp' => 'tstamp',
        'title' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace',
        'descriptionColumn' => 'description',
        'adminOnly' => true,
        'rootLevel' => 1,
        'groupName' => 'system',
        'delete' => 'deleted',
        'typeicon_classes' => [
            'default' => 'mimetypes-x-sys_workspace',
        ],
        'versioningWS_alwaysAllowLiveEdit' => true,
    ],
    'columns' => [
        'title' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.title',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'max' => 30,
                'required' => true,
                'eval' => 'trim,unique',
            ],
        ],
        'adminusers' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.adminusers',
            'config' => [
                'type' => 'group',
                'allowed' => 'be_users,be_groups',
                'prepend_tname' => true,
                'size' => 3,
                'maxitems' => 10,
                'autoSizeMax' => 10,
            ],
        ],
        'members' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.members',
            'config' => [
                'type' => 'group',
                'allowed' => 'be_users,be_groups',
                'prepend_tname' => true,
                'size' => 3,
                'maxitems' => 100,
                'autoSizeMax' => 10,
            ],
        ],
        'db_mountpoints' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:page_tree_entry_points',
            'config' => [
                'type' => 'group',
                'allowed' => 'pages',
                'size' => 3,
                'maxitems' => 100,
                'autoSizeMax' => 10,
            ],
        ],
        'file_mountpoints' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:file_mountpoints',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectCheckBox',
                'foreign_table' => 'sys_filemounts',
                'size' => 3,
                'maxitems' => 100,
                'autoSizeMax' => 10,
            ],
        ],
        'publish_time' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.publish_time',
            'config' => [
                'type' => 'datetime',
                'default' => 0,
            ],
        ],
        'live_edit' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.live_edit',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
            ],
        ],
        'publish_access' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.publish_access',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.publish_access.1'],
                    ['label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.publish_access.2'],
                    ['label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.publish_access.3'],
                ],
            ],
        ],
        'previewlink_lifetime' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.previewlink_lifetime',
            'config' => [
                'type' => 'number',
                'size' => 10,
                'default' => 48,
            ],
        ],
        'stagechg_notification' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.stagechg_notification',
            'description' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.stagechg_notification.description',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 1,
            ],
        ],
        'custom_stages' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.custom_stages',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'sys_workspace_stage',
                'appearance' => [
                    'useSortable' => true,
                    'expandSingle' => true,
                ],
                'foreign_field' => 'parentid',
            ],
            'default' => 0,
        ],
        'edit_notification_defaults' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace_stage.notification_defaults',
            'config' => [
                'type' => 'group',
                'allowed' => 'be_users,be_groups',
                'prepend_tname' => true,
                'size' => 3,
                'maxitems' => 100,
                'autoSizeMax' => 20,
            ],
        ],
        'edit_allow_notificaton_settings' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.settingsDialog',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.settingsDialog.showDialog'],
                    ['label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.settingsDialog.changeablePreselection'],
                ],
                'default' => 3,
                'cols' => 1,
            ],
        ],
        'edit_notification_preselection' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.preselection',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.preselection.owners'],
                    ['label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.preselection.members'],
                ],
                'default' => 2,
                'cols' => 1,
            ],
        ],
        'publish_notification_defaults' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace_stage.notification_defaults',
            'config' => [
                'type' => 'group',
                'allowed' => 'be_users,be_groups',
                'prepend_tname' => true,
                'size' => 3,
                'maxitems' => 100,
                'autoSizeMax' => 20,
            ],
        ],
        'publish_allow_notificaton_settings' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.settingsDialog',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.settingsDialog.showDialog'],
                    ['label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.settingsDialog.changeablePreselection'],
                ],
                'default' => 3,
                'cols' => 1,
            ],
        ],
        'publish_notification_preselection' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.preselection',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.preselection.owners'],
                    ['label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.preselection.members'],
                ],
                'default' => 1,
                'cols' => 1,
            ],
        ],
        'execute_notification_defaults' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace_stage.notification_defaults',
            'config' => [
                'type' => 'group',
                'allowed' => 'be_users,be_groups',
                'prepend_tname' => true,
                'size' => 3,
                'maxitems' => 100,
                'autoSizeMax' => 20,
            ],
        ],
        'execute_allow_notificaton_settings' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.settingsDialog',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.settingsDialog.showDialog'],
                    ['label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.settingsDialog.changeablePreselection'],
                ],
                'default' => 3,
                'cols' => 1,
            ],
        ],
        'execute_notification_preselection' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.preselection',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.preselection.owners'],
                    ['label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.preselection.members'],
                ],
                'default' => 3,
                'cols' => 1,
            ],
        ],
    ],
    'palettes' => [
        'memberlist' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:tabs.users',
            'showitem' => 'adminusers,members',
        ],
        'stage.edit' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.palette.stage.edit',
            'showitem' => 'edit_allow_notificaton_settings, edit_notification_preselection,',
        ],
        'stage.publish' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.palette.stage.publish',
            'showitem' => 'publish_allow_notificaton_settings, publish_notification_preselection,',
        ],
        'stage.execute' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.palette.stage.execute',
            'showitem' => 'execute_allow_notificaton_settings, execute_notification_preselection,',
        ],
    ],
    'types' => [
        '0' => ['showitem' => '
            --div--;core.form.tabs:general,
                title,
                stagechg_notification,
                --palette--;;memberlist,
            --div--;workspaces.db:tabs.internal_stages,
            --palette--;workspaces.db:tabs.notification_settings,
                --palette--;;stage.edit, edit_notification_defaults,
                --palette--;;stage.publish, publish_notification_defaults,
                --palette--;;stage.execute, execute_notification_defaults,
            --div--;workspaces.db:tabs.custom_stages,
                custom_stages,
            --div--;workspaces.db:tabs.mountpoints,
                db_mountpoints,file_mountpoints,
            --div--;workspaces.db:tabs.publish_access,
                previewlink_lifetime,live_edit,publish_access,publish_time,
            --div--;core.form.tabs:notes,
                description,
            --div--;core.form.tabs:extended,
        '],
    ],
];

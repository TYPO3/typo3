<?php
return [
    'ctrl' => [
        'label' => 'title',
        'tstamp' => 'tstamp',
        'title' => 'LLL:EXT:lang/locallang_tca.xlf:sys_workspace',
        'adminOnly' => 1,
        'rootLevel' => 1,
        'delete' => 'deleted',
        'typeicon_classes' => [
            'default' => 'mimetypes-x-sys_workspace'
        ],
        'versioningWS_alwaysAllowLiveEdit' => true
    ],
    'columns' => [
        'title' => [
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.title',
            'config' => [
                'type' => 'input',
                'size' => '20',
                'max' => '30',
                'eval' => 'required,trim,unique'
            ]
        ],
        'description' => [
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.description',
            'config' => [
                'type' => 'text',
                'rows' => 5,
                'cols' => 30
            ]
        ],
        'adminusers' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_workspace.adminusers',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'be_users,be_groups',
                'prepend_tname' => 1,
                'size' => '3',
                'maxitems' => '10',
                'autoSizeMax' => 10,
                'show_thumbs' => '1',
                'wizards' => [
                    'suggest' => [
                        'type' => 'suggest'
                    ]
                ]
            ]
        ],
        'members' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_workspace.members',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'be_users,be_groups',
                'prepend_tname' => 1,
                'size' => '3',
                'maxitems' => '100',
                'autoSizeMax' => 10,
                'show_thumbs' => '1',
                'wizards' => [
                    'suggest' => [
                        'type' => 'suggest'
                    ]
                ]
            ]
        ],
        'db_mountpoints' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:db_mountpoints',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages',
                'size' => '3',
                'maxitems' => 25,
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
                'renderType' => 'selectCheckBox',
                'foreign_table' => 'sys_filemounts',
                'foreign_table_where' => ' AND sys_filemounts.pid=0 ORDER BY sys_filemounts.title',
                'size' => '3',
                'maxitems' => 25,
                'autoSizeMax' => 10,
            ]
        ],
        'publish_time' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_workspace.publish_time',
            'config' => [
                'type' => 'input',
                'size' => '8',
                'eval' => 'datetime',
                'default' => '0',
            ]
        ],
        'unpublish_time' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_workspace.unpublish_time',
            'config' => [
                'type' => 'input',
                'size' => '8',
                'eval' => 'datetime',
                'default' => '0',
                'range' => [
                    'upper' => mktime(0, 0, 0, 12, 31, 2020)
                ]
            ],
            'displayCond' => 'FALSE'
        ],
        'freeze' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_workspace.freeze',
            'config' => [
                'type' => 'check',
                'default' => '0'
            ]
        ],
        'live_edit' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_workspace.live_edit',
            'config' => [
                'type' => 'check',
                'default' => '0'
            ]
        ],
        'swap_modes' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_workspace.swap_modes',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0],
                    ['Swap-Into-Workspace on Auto-publish', 1],
                    ['Disable Swap-Into-Workspace', 2]
                ]
            ]
        ],
        'publish_access' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_workspace.publish_access',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['Publish only content in publish stage', 0],
                    ['Only workspace owner can publish', 0]
                ]
            ]
        ],
        'stagechg_notification' => [
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_workspace.stagechg_notification',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0],
                    ['Notify users on next stage only', 1],
                    ['Notify all users on any change', 10]
                ]
            ]
        ],
        'custom_stages' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.custom_stages',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'sys_workspace_stage',
                'appearance' => 'useSortable,expandSingle',
                'foreign_field' => 'parentid',
                'foreign_table_field' => 'parenttable',
                'minitems' => 0
            ],
            'default' => 0
        ],
        // @deprecated not used anymore
        'edit_notification_mode' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.edit_notification_mode',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.notification_mode.0', 0],
                    ['LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.notification_mode.1', 1],
                    ['LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.notification_mode.2', 2]
                ]
            ]
        ],
        'edit_notification_defaults' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace_stage.notification_defaults',
            'displayCond' => 'FIELD:edit_allow_notificaton_settings:BIT:1',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'be_users,be_groups',
                'prepend_tname' => 1,
                'size' => '3',
                'maxitems' => '100',
                'autoSizeMax' => 20,
                'show_thumbs' => '1',
                'wizards' => [
                    'suggest' => [
                        'type' => 'suggest'
                    ]
                ]
            ]
        ],
        'edit_allow_notificaton_settings' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.settingsDialog',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.settingsDialog.showDialog', ''],
                    ['LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.settingsDialog.changeablePreselection', ''],
                ],
                'default' => 3,
                'cols' => 2,
            ]
        ],
        'edit_notification_preselection' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.preselection',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.preselection.owners', ''],
                    ['LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.preselection.members', ''],
                    ['LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.preselection.editors', ''],
                ],
                'default' => 2,
                'cols' => 3,
            ]
        ],
        // @deprecated not used anymore
        'publish_notification_mode' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.publish_notification_mode',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.notification_mode.0', 0],
                    ['LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.notification_mode.1', 1],
                    ['LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.notification_mode.2', 2]
                ]
            ]
        ],
        'publish_notification_defaults' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace_stage.notification_defaults',
            'displayCond' => 'FIELD:publish_allow_notificaton_settings:BIT:1',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'be_users,be_groups',
                'prepend_tname' => 1,
                'size' => '3',
                'maxitems' => '100',
                'autoSizeMax' => 20,
                'show_thumbs' => '1',
                'wizards' => [
                    'suggest' => [
                        'type' => 'suggest'
                    ]
                ]
            ]
        ],
        'publish_allow_notificaton_settings' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.settingsDialog',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.settingsDialog.showDialog', ''],
                    ['LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.settingsDialog.changeablePreselection', ''],
                ],
                'default' => 3,
                'cols' => 2,
            ]
        ],
        'publish_notification_preselection' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.preselection',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.preselection.owners', ''],
                    ['LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.preselection.members', ''],
                    ['LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.preselection.editors', ''],
                ],
                'default' => 1,
                'cols' => 3,
            ]
        ],
        'execute_notification_defaults' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace_stage.notification_defaults',
            'displayCond' => 'FIELD:execute_allow_notificaton_settings:BIT:1',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'be_users,be_groups',
                'prepend_tname' => 1,
                'size' => '3',
                'maxitems' => '100',
                'autoSizeMax' => 20,
                'show_thumbs' => '1',
                'wizards' => [
                    'suggest' => [
                        'type' => 'suggest'
                    ]
                ]
            ]
        ],
        'execute_allow_notificaton_settings' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.settingsDialog',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.settingsDialog.showDialog', ''],
                    ['LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.settingsDialog.changeablePreselection', ''],
                ],
                'default' => 3,
                'cols' => 2,
            ]
        ],
        'execute_notification_preselection' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.preselection',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.preselection.owners', ''],
                    ['LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.preselection.members', ''],
                    ['LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.preselection.editors', ''],
                ],
                'default' => 3,
                'cols' => 3,
            ]
        ]
    ],
    'palettes' => [
        'stage.edit' => [
            'showitem' => 'edit_allow_notificaton_settings, edit_notification_preselection,',
        ],
        'stage.publish' => [
            'showitem' => 'publish_allow_notificaton_settings, publish_notification_preselection,',
        ],
        'stage.execute' => [
            'showitem' => 'execute_allow_notificaton_settings, execute_notification_preselection,',
        ]
    ],
    'types' => [
        '0' => ['showitem' => 'title,description,
			--div--;LLL:EXT:lang/locallang_tca.xlf:sys_filemounts.tabs.users,adminusers,members,
			--div--;LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:tabs.notification_settings, stagechg_notification,
				--palette--;LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xml:sys_workspace.palette.stage.edit;stage.edit, edit_notification_defaults,
				--palette--;LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xml:sys_workspace.palette.stage.publish;stage.publish, publish_notification_defaults,
				--palette--;LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xml:sys_workspace.palette.stage.execute;stage.execute, execute_notification_defaults,
			--div--;LLL:EXT:lang/locallang_tca.xlf:sys_filemounts.tabs.mountpoints,db_mountpoints,file_mountpoints,
			--div--;LLL:EXT:lang/locallang_tca.xlf:sys_filemounts.tabs.publishing,publish_time,unpublish_time,
			--div--;LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_filemounts.tabs.staging,custom_stages,
			--div--;LLL:EXT:lang/locallang_tca.xlf:sys_filemounts.tabs.other,freeze,live_edit,swap_modes,publish_access']
    ]
];

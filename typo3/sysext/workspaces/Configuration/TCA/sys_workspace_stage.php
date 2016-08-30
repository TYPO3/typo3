<?php
return [
    'ctrl' => [
        'label' => 'title',
        'tstamp' => 'tstamp',
        'sortby' => 'sorting',
        'title' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace_stage',
        'adminOnly' => 1,
        'rootLevel' => 1,
        'hideTable' => true,
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
                'eval' => 'required,trim'
            ]
        ],
        'responsible_persons' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace_stage.responsible_persons',
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
        'default_mailcomment' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace_stage.default_mailcomment',
            'config' => [
                'type' => 'text',
                'rows' => 5,
                'cols' => 30
            ]
        ],
        'parentid' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace_stage.parentid',
            'config' => [
                'type' => 'passthrough'
            ]
        ],
        'parenttable' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace_stage.parenttable',
            'config' => [
                'type' => 'passthrough'
            ]
        ],
        // @deprecated not used anymore
        'notification_mode' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace_stage.notification_mode',
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
        'notification_defaults' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace_stage.notification_defaults',
            'displayCond' => 'FIELD:allow_notificaton_settings:BIT:1',
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
        'allow_notificaton_settings' => [
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
        'notification_preselection' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.preselection',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.preselection.owners', ''],
                    ['LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.preselection.members', ''],
                    ['LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.preselection.editors', ''],
                    ['LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.preselection.responsiblePersons', ''],
                ],
                'default' => 8,
                'cols' => 4,
            ]
        ]
    ],
    'palettes' => [
        'stage' => [
            'showitem' => 'allow_notificaton_settings, notification_preselection,',
        ]
    ],
    'types' => [
        '0' => ['showitem' => '
			--div--;LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:tabs.general,title,responsible_persons,
			--div--;LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:tabs.notification_settings,--palette--;;stage, notification_defaults, default_mailcomment']
    ]
];

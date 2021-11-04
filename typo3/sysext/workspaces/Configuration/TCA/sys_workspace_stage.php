<?php

return [
    'ctrl' => [
        'label' => 'title',
        'tstamp' => 'tstamp',
        'sortby' => 'sorting',
        'title' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace_stage',
        'adminOnly' => true,
        'rootLevel' => 1,
        'hideTable' => true,
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
                'eval' => 'required,trim',
            ],
        ],
        'responsible_persons' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace_stage.responsible_persons',
            'config' => [
                'type' => 'group',
                'allowed' => 'be_users,be_groups',
                'prepend_tname' => true,
                'size' => 3,
                'maxitems' => 100,
                'autoSizeMax' => 20,
            ],
        ],
        'default_mailcomment' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace_stage.default_mailcomment',
            'config' => [
                'type' => 'text',
                'rows' => 5,
                'cols' => 30,
            ],
        ],
        'parentid' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace_stage.parentid',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'parenttable' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace_stage.parenttable',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'notification_defaults' => [
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
        'allow_notificaton_settings' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.settingsDialog',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.settingsDialog.showDialog'],
                    ['LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.settingsDialog.changeablePreselection'],
                ],
                'default' => 3,
                'cols' => 1,
            ],
        ],
        'notification_preselection' => [
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.preselection',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.preselection.owners'],
                    ['LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.preselection.members'],
                    ['LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.preselection.editors'],
                    ['LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.preselection.responsiblePersons'],
                ],
                'default' => 8,
                'cols' => 1,
            ],
        ],
    ],
    'palettes' => [
        'stage' => [
            'showitem' => 'allow_notificaton_settings, notification_preselection,',
        ],
    ],
    'types' => [
        '0' => ['showitem' => '
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                title,responsible_persons,
            --div--;LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:tabs.notification_settings,
                --palette--;;stage, notification_defaults, default_mailcomment,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
        '],
    ],
];

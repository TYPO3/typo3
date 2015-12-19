<?php
return array(
    'ctrl' => array(
        'label' => 'title',
        'tstamp' => 'tstamp',
        'sortby' => 'sorting',
        'title' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace_stage',
        'adminOnly' => 1,
        'rootLevel' => 1,
        'hideTable' => true,
        'delete' => 'deleted',
        'typeicon_classes' => array(
            'default' => 'mimetypes-x-sys_workspace'
        ),
        'versioningWS_alwaysAllowLiveEdit' => true
    ),
    'columns' => array(
        'title' => array(
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.title',
            'config' => array(
                'type' => 'input',
                'size' => '20',
                'max' => '30',
                'eval' => 'required,trim'
            )
        ),
        'responsible_persons' => array(
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace_stage.responsible_persons',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'be_users,be_groups',
                'prepend_tname' => 1,
                'size' => '3',
                'maxitems' => '100',
                'autoSizeMax' => 20,
                'show_thumbs' => '1',
                'wizards' => array(
                    'suggest' => array(
                        'type' => 'suggest'
                    )
                )
            )
        ),
        'default_mailcomment' => array(
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace_stage.default_mailcomment',
            'config' => array(
                'type' => 'text',
                'rows' => 5,
                'cols' => 30
            )
        ),
        'parentid' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace_stage.parentid',
            'config' => array(
                'type' => 'passthrough'
            )
        ),
        'parenttable' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace_stage.parenttable',
            'config' => array(
                'type' => 'passthrough'
            )
        ),
        'notification_defaults' => array(
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace_stage.notification_defaults',
            'displayCond' => 'FIELD:allow_notificaton_settings:BIT:1',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'be_users,be_groups',
                'prepend_tname' => 1,
                'size' => '3',
                'maxitems' => '100',
                'autoSizeMax' => 20,
                'show_thumbs' => '1',
                'wizards' => array(
                    'suggest' => array(
                        'type' => 'suggest'
                    )
                )
            )
        ),
        'allow_notificaton_settings' => array(
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.settingsDialog',
            'config' => array(
                'type' => 'check',
                'items' => array(
                    array('LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.settingsDialog.showDialog', ''),
                    array('LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.settingsDialog.changeablePreselection', ''),
                ),
                'default' => 3,
                'cols' => 2,
            )
        ),
        'notification_preselection' => array(
            'label' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.preselection',
            'config' => array(
                'type' => 'check',
                'items' => array(
                    array('LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.preselection.owners', ''),
                    array('LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.preselection.members', ''),
                    array('LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.preselection.editors', ''),
                    array('LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:sys_workspace.preselection.responsiblePersons', ''),
                ),
                'default' => 8,
                'cols' => 4,
            )
        )
    ),
    'palettes' => array(
        'stage' => array(
            'showitem' => 'allow_notificaton_settings, notification_preselection,',
        )
    ),
    'types' => array(
        '0' => array('showitem' => '
			--div--;LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:tabs.general,title,responsible_persons,
			--div--;LLL:EXT:workspaces/Resources/Private/Language/locallang_db.xlf:tabs.notification_settings,--palette--;;stage, notification_defaults, default_mailcomment')
    )
);

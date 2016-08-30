<?php
return [
    'ctrl' => [
        'label' => 'title',
        'descriptionColumn' => 'description',
        'tstamp' => 'tstamp',
        'default_sortby' => 'ORDER BY title',
        'sortby' => 'sorting',
        'prependAtCopy' => 'LLL:EXT:lang/locallang_general.xlf:LGL.prependAtCopy',
        'title' => 'LLL:EXT:sys_action/Resources/Private/Language/locallang_tca.xlf:sys_action',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'adminOnly' => 1,
        'rootLevel' => -1,
        'setToDefaultOnCopy' => 'assign_to_groups',
        'enablecolumns' => [
            'disabled' => 'hidden'
        ],
        'typeicon_classes' => [
            'default' => 'mimetypes-x-sys_action'
        ],
        'type' => 'type'
    ],
    'interface' => [
        'showRecordFieldList' => 'hidden,title,type,description,assign_to_groups'
    ],
    'columns' => [
        'title' => [
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.title',
            'config' => [
                'type' => 'input',
                'size' => '25',
                'max' => '255',
                'eval' => 'trim,required'
            ]
        ],
        'description' => [
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.description',
            'config' => [
                'type' => 'text',
                'rows' => 10,
                'cols' => 48
            ]
        ],
        'hidden' => [
            'label' => 'LLL:EXT:sys_action/Resources/Private/Language/locallang_tca.xlf:sys_action.hidden',
            'config' => [
                'type' => 'check'
            ]
        ],
        'type' => [
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', '0'],
                    ['LLL:EXT:sys_action/Resources/Private/Language/locallang_tca.xlf:sys_action.type.1', '1'],
                    ['LLL:EXT:sys_action/Resources/Private/Language/locallang_tca.xlf:sys_action.type.2', '2'],
                    ['LLL:EXT:sys_action/Resources/Private/Language/locallang_tca.xlf:sys_action.type.3', '3'],
                    ['LLL:EXT:sys_action/Resources/Private/Language/locallang_tca.xlf:sys_action.type.4', '4'],
                    ['LLL:EXT:sys_action/Resources/Private/Language/locallang_tca.xlf:sys_action.type.5', '5']
                ]
            ]
        ],
        'assign_to_groups' => [
            'label' => 'LLL:EXT:sys_action/Resources/Private/Language/locallang_tca.xlf:sys_action.assign_to_groups',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'be_groups',
                'foreign_table_where' => 'ORDER BY be_groups.title',
                'MM' => 'sys_action_asgr_mm',
                'size' => '10',
                'minitems' => '0',
                'maxitems' => '200',
                'autoSizeMax' => '10'
            ]
        ],
        't1_userprefix' => [
            'label' => 'LLL:EXT:sys_action/Resources/Private/Language/locallang_tca.xlf:sys_action.t1_userprefix',
            'config' => [
                'type' => 'input',
                'size' => '25',
                'max' => '10',
                'eval' => 'trim'
            ]
        ],
        't1_allowed_groups' => [
            'label' => 'LLL:EXT:sys_action/Resources/Private/Language/locallang_tca.xlf:sys_action.t1_allowed_groups',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'be_groups',
                'foreign_table_where' => 'ORDER BY be_groups.title',
                'size' => '10',
                'maxitems' => '20',
                'autoSizeMax' => '10'
            ]
        ],
        't1_create_user_dir' => [
            'label' => 'LLL:EXT:sys_action/Resources/Private/Language/locallang_tca.xlf:sys_action.t1_create_user_dir',
            'config' => [
                'type' => 'check'
            ]
        ],
        't1_copy_of_user' => [
            'label' => 'LLL:EXT:sys_action/Resources/Private/Language/locallang_tca.xlf:sys_action.t1_copy_of_user',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'be_users',
                'size' => '1',
                'maxitems' => '1',
                'minitems' => '1',
                'show_thumbs' => '1',
                'wizards' => [
                    'suggest' => [
                        'type' => 'suggest'
                    ]
                ]
            ]
        ],
        't3_listPid' => [
            'label' => 'LLL:EXT:sys_action/Resources/Private/Language/locallang_tca.xlf:sys_action.t3_listPid',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages',
                'size' => '1',
                'maxitems' => '1',
                'minitems' => '1',
                'show_thumbs' => '1',
                'wizards' => [
                    'suggest' => [
                        'type' => 'suggest'
                    ]
                ]
            ]
        ],
        't3_tables' => [
            'label' => 'LLL:EXT:sys_action/Resources/Private/Language/locallang_tca.xlf:sys_action.t3_tables',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'special' => 'tables',
                'items' => [
                    ['', '']
                ]
            ]
        ],
        't4_recordsToEdit' => [
            'label' => 'LLL:EXT:sys_action/Resources/Private/Language/locallang_tca.xlf:sys_action.t4_recordsToEdit',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => '*',
                'prepend_tname' => 1,
                'size' => '5',
                'maxitems' => '50',
                'minitems' => '1',
                'show_thumbs' => '1',
                'wizards' => [
                    'suggest' => [
                        'type' => 'suggest'
                    ]
                ]
            ]
        ]
    ],
    'types' => [
        '0' => ['showitem' => '
			type,
			title,
			description,
			--div--;LLL:EXT:sys_action/Resources/Private/Language/locallang_tca.xlf:sys_action.tab.access,
				hidden,
				assign_to_groups
		'],
        '1' => ['showitem' => '
			type,
			title,
			description,
			--div--;LLL:EXT:sys_action/Resources/Private/Language/locallang_tca.xlf:sys_action.tab.create_user.settings,
				t1_userprefix,t1_copy_of_user,
				t1_allowed_groups,
				t1_create_user_dir,
			--div--;LLL:EXT:sys_action/Resources/Private/Language/locallang_tca.xlf:sys_action.tab.access,
				hidden,
				assign_to_groups
		'],
        '2' => ['showitem' => '
			type,
			title,
			description,
			--div--;LLL:EXT:sys_action/Resources/Private/Language/locallang_tca.xlf:sys_action.tab.access,
				hidden,
				assign_to_groups
		'],
        '3' => ['showitem' => '
			type,
			title,
			description,
			--div--;LLL:EXT:sys_action/Resources/Private/Language/locallang_tca.xlf:sys_action.tab.record_list.settings,
				t3_listPid,
				t3_tables,
			--div--;LLL:EXT:sys_action/Resources/Private/Language/locallang_tca.xlf:sys_action.tab.access,
				hidden,
				assign_to_groups
		'],
        '4' => ['showitem' => '
			type,
			title,
			description,
			--div--;LLL:EXT:sys_action/Resources/Private/Language/locallang_tca.xlf:sys_action.tab.edit_records.settings,
				t4_recordsToEdit,
			--div--;LLL:EXT:sys_action/Resources/Private/Language/locallang_tca.xlf:sys_action.tab.access,
				hidden,
				assign_to_groups
		'],
        '5' => ['showitem' => '
			type,
			title,
			description,
			--div--;LLL:EXT:sys_action/Resources/Private/Language/locallang_tca.xlf:sys_action.tab.new_record.settings,
				t3_listPid;LLL:EXT:sys_action/Resources/Private/Language/locallang_tca.xlf:sys_action.new_record.pid,
				t3_tables;LLL:EXT:sys_action/Resources/Private/Language/locallang_tca.xlf:sys_action.new_record.tablename,
			--div--;LLL:EXT:sys_action/Resources/Private/Language/locallang_tca.xlf:sys_action.tab.access,
				hidden,
				assign_to_groups
		']
    ]
];

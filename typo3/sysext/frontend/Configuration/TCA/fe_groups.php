<?php
return [
    'ctrl' => [
        'label' => 'title',
        'descriptionColumn' => 'description',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'prependAtCopy' => 'LLL:EXT:lang/locallang_general.xlf:LGL.prependAtCopy',
        'enablecolumns' => [
            'disabled' => 'hidden'
        ],
        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:fe_groups',
        'typeicon_classes' => [
            'default' => 'status-user-group-frontend'
        ],
        'useColumnsForDefaultValues' => 'lockToDomain',
        'searchFields' => 'title,description'
    ],
    'interface' => [
        'showRecordFieldList' => 'title,hidden,subgroup,lockToDomain,description'
    ],
    'columns' => [
        'hidden' => [
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.disable',
            'exclude' => 1,
            'config' => [
                'type' => 'check',
                'default' => '0'
            ]
        ],
        'title' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:fe_groups.title',
            'config' => [
                'type' => 'input',
                'size' => '20',
                'max' => '50',
                'eval' => 'trim,required'
            ]
        ],
        'subgroup' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:fe_groups.subgroup',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'fe_groups',
                'foreign_table_where' => 'AND NOT(fe_groups.uid = ###THIS_UID###) AND fe_groups.hidden=0 ORDER BY fe_groups.title',
                'enableMultiSelectFilterTextfield' => true,
                'size' => 6,
                'autoSizeMax' => 10,
                'minitems' => 0,
                'maxitems' => 20
            ]
        ],
        'lockToDomain' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:fe_groups.lockToDomain',
            'config' => [
                'type' => 'input',
                'size' => '20',
                'eval' => 'trim',
                'max' => '50'
            ]
        ],
        'description' => [
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.description',
            'config' => [
                'type' => 'text',
                'rows' => 5,
                'cols' => 48
            ]
        ],
        'TSconfig' => [
            'exclude' => 1,
            'label' => 'TSconfig:',
            'config' => [
                'type' => 'text',
                'cols' => '40',
                'rows' => '10',
                'softref' => 'TSconfig'
            ],
            'defaultExtras' => 'fixed-font : enable-tab'
        ]
    ],
    'types' => [
        '0' => ['showitem' => '
			hidden,title,description,subgroup,
			--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:fe_groups.tabs.options, lockToDomain, TSconfig,
			--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:fe_groups.tabs.extended
		']
    ]
];

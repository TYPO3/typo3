<?php
return [
    'ctrl' => [
        'label' => 'username',
        'default_sortby' => 'ORDER BY username',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'fe_cruser_id' => 'fe_cruser_id',
        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:fe_users',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'disable',
            'starttime' => 'starttime',
            'endtime' => 'endtime'
        ],
        'typeicon_classes' => [
            'default' => 'status-user-frontend'
        ],
        'useColumnsForDefaultValues' => 'usergroup,lockToDomain,disable,starttime,endtime',
        'searchFields' => 'username,name,first_name,last_name,middle_name,address,telephone,fax,email,title,zip,city,country,company'
    ],
    'interface' => [
        'showRecordFieldList' => 'username,password,usergroup,lockToDomain,name,first_name,middle_name,last_name,title,company,address,zip,city,country,email,www,telephone,fax,disable,starttime,endtime,lastlogin'
    ],
    'columns' => [
        'username' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:fe_users.username',
            'config' => [
                'type' => 'input',
                'size' => '20',
                'max' => '255',
                'eval' => 'nospace,trim,lower,uniqueInPid,required',
                'autocomplete' => false,
            ]
        ],
        'password' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:fe_users.password',
            'config' => [
                'type' => 'input',
                'size' => '10',
                'max' => '40',
                'eval' => 'trim,required,password',
                'autocomplete' => false,
            ]
        ],
        'usergroup' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:fe_users.usergroup',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'fe_groups',
                'foreign_table_where' => 'ORDER BY fe_groups.title',
                'enableMultiSelectFilterTextfield' => true,
                'size' => '6',
                'minitems' => '1',
                'maxitems' => '50'
            ]
        ],
        'lockToDomain' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:fe_users.lockToDomain',
            'config' => [
                'type' => 'input',
                'size' => '20',
                'eval' => 'trim',
                'max' => '50',
                'softref' => 'substitute'
            ]
        ],
        'name' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.name',
            'config' => [
                'type' => 'input',
                'size' => '40',
                'eval' => 'trim',
                'max' => '80'
            ]
        ],
        'first_name' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.first_name',
            'config' => [
                'type' => 'input',
                'size' => '25',
                'eval' => 'trim',
                'max' => '50'
            ]
        ],
        'middle_name' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.middle_name',
            'config' => [
                'type' => 'input',
                'size' => '25',
                'eval' => 'trim',
                'max' => '50'
            ]
        ],
        'last_name' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.last_name',
            'config' => [
                'type' => 'input',
                'size' => '25',
                'eval' => 'trim',
                'max' => '50'
            ]
        ],
        'address' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.address',
            'config' => [
                'type' => 'text',
                'cols' => '20',
                'rows' => '3'
            ]
        ],
        'telephone' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.phone',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'size' => '20',
                'max' => '20'
            ]
        ],
        'fax' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.fax',
            'config' => [
                'type' => 'input',
                'size' => '20',
                'eval' => 'trim',
                'max' => '20'
            ]
        ],
        'email' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.email',
            'config' => [
                'type' => 'input',
                'size' => '20',
                'eval' => 'trim',
                'max' => '255'
            ]
        ],
        'title' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.title_person',
            'config' => [
                'type' => 'input',
                'size' => '20',
                'eval' => 'trim',
                'max' => '40'
            ]
        ],
        'zip' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.zip',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'size' => '10',
                'max' => '10'
            ]
        ],
        'city' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.city',
            'config' => [
                'type' => 'input',
                'size' => '20',
                'eval' => 'trim',
                'max' => '50'
            ]
        ],
        'country' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.country',
            'config' => [
                'type' => 'input',
                'size' => '20',
                'eval' => 'trim',
                'max' => '40'
            ]
        ],
        'www' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.www',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'size' => '20',
                'max' => '80'
            ]
        ],
        'company' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.company',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'size' => '20',
                'max' => '80'
            ]
        ],
        'image' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.image',
            'config' => [
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
                'uploadfolder' => 'uploads/pics',
                'show_thumbs' => '1',
                'size' => '3',
                'maxitems' => '6',
                'minitems' => '0'
            ]
        ],
        'disable' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.disable',
            'config' => [
                'type' => 'check'
            ]
        ],
        'starttime' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'input',
                'size' => '13',
                'eval' => 'datetime',
                'default' => '0'
            ]
        ],
        'endtime' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'input',
                'size' => '13',
                'eval' => 'datetime',
                'default' => '0',
                'range' => [
                    'upper' => mktime(0, 0, 0, 12, 31, 2020)
                ]
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
        ],
        'lastlogin' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.lastlogin',
            'config' => [
                'type' => 'input',
                'readOnly' => '1',
                'size' => '12',
                'eval' => 'datetime',
                'default' => 0
            ]
        ]
    ],
    'types' => [
        '0' => [
            'showitem' => '
				disable, username, password, usergroup, lastlogin,
				--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:fe_users.tabs.personelData, company, --palette--;;1, name, --palette--;;2, address, zip, city, country, telephone, fax, email, www, image,
				--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:fe_users.tabs.options, lockToDomain, TSconfig,
				--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:fe_users.tabs.access, starttime, endtime,
				--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:fe_users.tabs.extended
			',
        ],
    ],
    'palettes' => [
        '1' => ['showitem' => 'title'],
        '2' => ['showitem' => 'first_name,--linebreak--,middle_name,--linebreak--,last_name']
    ]
];

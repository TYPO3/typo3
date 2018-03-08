<?php
return [
    'ctrl' => [
        'label' => 'username',
        'descriptionColumn' => 'description',
        'default_sortby' => 'username',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
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
        'searchFields' => 'username,name,first_name,last_name,middle_name,address,telephone,fax,email,title,zip,city,country,company,description'
    ],
    'interface' => [
        'showRecordFieldList' => 'username,password,usergroup,lockToDomain,name,first_name,middle_name,last_name,title,company,address,zip,city,country,email,www,telephone,fax,disable,starttime,endtime,lastlogin,description'
    ],
    'columns' => [
        'username' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:fe_users.username',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'max' => 255,
                'eval' => 'nospace,trim,lower,uniqueInPid,required',
                'autocomplete' => false,
            ]
        ],
        'password' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:fe_users.password',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'max' => 40,
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
                'size' => 6,
                'minitems' => 1,
                'maxitems' => 50
            ]
        ],
        'lockToDomain' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:fe_users.lockToDomain',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim',
                'max' => 50,
                'softref' => 'substitute'
            ]
        ],
        'name' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.name',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'eval' => 'trim',
                'max' => 160
            ]
        ],
        'first_name' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.first_name',
            'config' => [
                'type' => 'input',
                'size' => 25,
                'eval' => 'trim',
                'max' => 50
            ]
        ],
        'middle_name' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.middle_name',
            'config' => [
                'type' => 'input',
                'size' => 25,
                'eval' => 'trim',
                'max' => 50
            ]
        ],
        'last_name' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.last_name',
            'config' => [
                'type' => 'input',
                'size' => 25,
                'eval' => 'trim',
                'max' => 50
            ]
        ],
        'address' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.address',
            'config' => [
                'type' => 'text',
                'cols' => 20,
                'rows' => 3
            ]
        ],
        'telephone' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.phone',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'size' => 20,
                'max' => 30
            ]
        ],
        'fax' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.fax',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim',
                'max' => 30
            ]
        ],
        'email' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.email',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim',
                'max' => 255
            ]
        ],
        'title' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.title_person',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim',
                'max' => '40'
            ]
        ],
        'zip' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.zip',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'size' => 10,
                'max' => '10'
            ]
        ],
        'city' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.city',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim',
                'max' => 50
            ]
        ],
        'country' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.country',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim',
                'max' => 40
            ]
        ],
        'www' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.www',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'size' => 20,
                'max' => 80
            ]
        ],
        'company' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.company',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'size' => 20,
                'max' => 80
            ]
        ],
        'image' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.image',
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
                'image',
                [
                    'maxitems' => 6,
                    'minitems'=> 0
                ],
                $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
            )
        ],
        'disable' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.disable',
            'config' => [
                'type' => 'check'
            ]
        ],
        'starttime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime,int',
                'default' => 0
            ]
        ],
        'endtime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime,int',
                'default' => 0,
                'range' => [
                    'upper' => mktime(0, 0, 0, 1, 1, 2038)
                ]
            ]
        ],
        'description' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.description',
            'config' => [
                'type' => 'text',
                'rows' => 5,
                'cols' => 48
            ]
        ],
        'TSconfig' => [
            'exclude' => true,
            'label' => 'TSconfig:',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 10,
                'enableTabulator' => true,
                'fixedFont' => true,
            ],
        ],
        'lastlogin' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.lastlogin',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'readOnly' => true,
                'eval' => 'datetime,int',
                'default' => 0
            ]
        ]
    ],
    'types' => [
        '0' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    username,password,usergroup,lastlogin,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:fe_users.tabs.personelData,
                    company, title, name, --palette--;;2, address, zip, city, country, telephone, fax, email, www, image,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:fe_users.tabs.options,
                    lockToDomain, TSconfig,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    disable,--palette--;;timeRestriction,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    description,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
        ],
    ],
    'palettes' => [
        'timeRestriction' => ['showitem' => 'starttime, endtime'],
        '2' => ['showitem' => 'first_name,--linebreak--,middle_name,--linebreak--,last_name']
    ]
];

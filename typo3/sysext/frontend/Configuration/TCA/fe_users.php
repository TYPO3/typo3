<?php

return [
    'ctrl' => [
        'label' => 'username',
        'descriptionColumn' => 'description',
        'default_sortby' => 'username',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'groupName' => 'frontendaccess',
        'title' => 'frontend.db.fe_users:table.title',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'disable',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'typeicon_classes' => [
            'default' => 'status-user-frontend',
        ],
        'useColumnsForDefaultValues' => 'usergroup,disable,starttime,endtime',
    ],
    'columns' => [
        'username' => [
            'label' => 'core.db.accounts:username',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'max' => 255,
                'required' => true,
                'eval' => 'nospace,trim,lower,uniqueInPid',
                'autocomplete' => false,
            ],
        ],
        'password' => [
            'label' => 'core.db.accounts:password',
            'config' => [
                'type' => 'password',
                'passwordPolicy' => $GLOBALS['TYPO3_CONF_VARS']['FE']['passwordPolicy'] ?? '',
                'size' => 20,
                'required' => true,
                'fieldControl' => [
                    'passwordGenerator' => [
                        'renderType' => 'passwordGenerator',
                        'options' => [
                            'title' => 'core.core:labels.generatePassword',
                            'allowEdit' => true,
                            'passwordPolicy' => $GLOBALS['TYPO3_CONF_VARS']['FE']['passwordPolicy'] ?? '',
                        ],
                    ],
                ],
            ],
        ],
        'usergroup' => [
            'label' => 'core.db.accounts:group',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'fe_groups',
                'size' => 6,
                'minitems' => 1,
                'dbFieldLength' => 512,
            ],
        ],
        'name' => [
            'exclude' => true,
            'label' => 'core.db.accounts:name',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'eval' => 'trim',
                'max' => 160,
            ],
        ],
        'first_name' => [
            'exclude' => true,
            'label' => 'core.db.accounts:first_name',
            'config' => [
                'type' => 'input',
                'size' => 25,
                'eval' => 'trim',
                'max' => 50,
            ],
        ],
        'middle_name' => [
            'exclude' => true,
            'label' => 'core.db.accounts:middle_name',
            'config' => [
                'type' => 'input',
                'size' => 25,
                'eval' => 'trim',
                'max' => 50,
            ],
        ],
        'last_name' => [
            'exclude' => true,
            'label' => 'core.db.accounts:last_name',
            'config' => [
                'type' => 'input',
                'size' => 25,
                'eval' => 'trim',
                'max' => 50,
            ],
        ],
        'address' => [
            'exclude' => true,
            'label' => 'core.db.accounts:address',
            'config' => [
                'type' => 'text',
                'cols' => 20,
                'rows' => 3,
                'default' => '',
            ],
        ],
        'telephone' => [
            'exclude' => true,
            'label' => 'core.db.accounts:phone',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'size' => 20,
                'max' => 30,
            ],
        ],
        'fax' => [
            'exclude' => true,
            'label' => 'core.db.accounts:fax',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim',
                'max' => 30,
            ],
        ],
        'email' => [
            'exclude' => true,
            'label' => 'core.db.accounts:email',
            'config' => [
                'type' => 'email',
                'size' => 20,
            ],
        ],
        'title' => [
            'exclude' => true,
            'label' => 'core.db.accounts:title_person',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim',
                'max' => 40,
            ],
        ],
        'zip' => [
            'exclude' => true,
            'label' => 'core.db.accounts:zip',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'size' => 10,
                'max' => 10,
            ],
        ],
        'city' => [
            'exclude' => true,
            'label' => 'core.db.accounts:city',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim',
                'max' => 50,
            ],
        ],
        'country' => [
            'exclude' => true,
            'label' => 'core.db.accounts:country',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim',
                'max' => 40,
            ],
        ],
        'www' => [
            'exclude' => true,
            'label' => 'core.db.accounts:www',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'size' => 20,
                'max' => 80,
            ],
        ],
        'company' => [
            'exclude' => true,
            'label' => 'core.db.accounts:company',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
                'size' => 20,
                'max' => 80,
            ],
        ],
        'image' => [
            'exclude' => true,
            'label' => 'core.db.accounts:image',
            'config' => [
                'type' => 'file',
                'allowed' => 'common-image-types',
                'maxitems' => 6,
            ],
        ],
        'lastlogin' => [
            'exclude' => true,
            'label' => 'core.db.accounts:last_login',
            'config' => [
                'type' => 'datetime',
                'readOnly' => true,
                'default' => 0,
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => '
                --div--;core.form.tabs:general,
                    username,password,usergroup,lastlogin,
                --div--;core.form.tabs:personaldata,
                    company, title, name, --palette--;;2, address, zip, city, country, telephone, fax, email, www, image,
                --div--;core.form.tabs:access,
                    disable,--palette--;;timeRestriction,
                --div--;core.form.tabs:notes,
                    description,
                --div--;core.form.tabs:extended,
            ',
            'creationOptions' => [
                'enableDirectRecordTypeCreation' => false,
            ],
        ],
    ],
    'palettes' => [
        'timeRestriction' => ['showitem' => 'starttime, endtime'],
        '2' => ['showitem' => 'first_name,--linebreak--,middle_name,--linebreak--,last_name'],
    ],
];

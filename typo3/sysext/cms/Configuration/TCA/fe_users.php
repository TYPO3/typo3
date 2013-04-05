<?php
return array(
	'ctrl' => array(
		'label' => 'username',
		'default_sortby' => 'ORDER BY username',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'fe_cruser_id' => 'fe_cruser_id',
		'title' => 'LLL:EXT:cms/locallang_tca.xlf:fe_users',
		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'disable',
			'starttime' => 'starttime',
			'endtime' => 'endtime'
		),
		'typeicon_classes' => array(
			'default' => 'status-user-frontend'
		),
		'useColumnsForDefaultValues' => 'usergroup,lockToDomain,disable,starttime,endtime',
		'dividers2tabs' => 1,
		'searchFields' => 'username,name,first_name,last_name,middle_name,address,telephone,fax,email,title,zip,city,country,company'
	),
	'feInterface' => array(
		'fe_admin_fieldList' => 'username,password,usergroup,name,address,telephone,fax,email,title,zip,city,country,www,company'
	),
	'interface' => array(
		'showRecordFieldList' => 'username,password,usergroup,lockToDomain,name,first_name,middle_name,last_name,title,company,address,zip,city,country,email,www,telephone,fax,disable,starttime,endtime,lastlogin'
	),
	'columns' => array(
		'username' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:fe_users.username',
			'config' => array(
				'type' => 'input',
				'size' => '20',
				'max' => '50',
				'eval' => 'nospace,lower,uniqueInPid,required'
			)
		),
		'password' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:fe_users.password',
			'config' => array(
				'type' => 'input',
				'size' => '10',
				'max' => '40',
				'eval' => 'nospace,required,password'
			)
		),
		'usergroup' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:fe_users.usergroup',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'fe_groups',
				'foreign_table_where' => 'ORDER BY fe_groups.title',
				'size' => '6',
				'minitems' => '1',
				'maxitems' => '50'
			)
		),
		'lockToDomain' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:fe_users.lockToDomain',
			'config' => array(
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '50',
				'softref' => 'substitute'
			)
		),
		'name' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.name',
			'config' => array(
				'type' => 'input',
				'size' => '40',
				'eval' => 'trim',
				'max' => '80'
			)
		),
		'first_name' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.first_name',
			'config' => array(
				'type' => 'input',
				'size' => '25',
				'eval' => 'trim',
				'max' => '50'
			)
		),
		'middle_name' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.middle_name',
			'config' => array(
				'type' => 'input',
				'size' => '25',
				'eval' => 'trim',
				'max' => '50'
			)
		),
		'last_name' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.last_name',
			'config' => array(
				'type' => 'input',
				'size' => '25',
				'eval' => 'trim',
				'max' => '50'
			)
		),
		'address' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.address',
			'config' => array(
				'type' => 'text',
				'cols' => '20',
				'rows' => '3'
			)
		),
		'telephone' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.phone',
			'config' => array(
				'type' => 'input',
				'eval' => 'trim',
				'size' => '20',
				'max' => '20'
			)
		),
		'fax' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.fax',
			'config' => array(
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '20'
			)
		),
		'email' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.email',
			'config' => array(
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '80'
			)
		),
		'title' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.title_person',
			'config' => array(
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '40'
			)
		),
		'zip' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.zip',
			'config' => array(
				'type' => 'input',
				'eval' => 'trim',
				'size' => '10',
				'max' => '10'
			)
		),
		'city' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.city',
			'config' => array(
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '50'
			)
		),
		'country' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.country',
			'config' => array(
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '40'
			)
		),
		'www' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.www',
			'config' => array(
				'type' => 'input',
				'eval' => 'trim',
				'size' => '20',
				'max' => '80'
			)
		),
		'company' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.company',
			'config' => array(
				'type' => 'input',
				'eval' => 'trim',
				'size' => '20',
				'max' => '80'
			)
		),
		'image' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.image',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
				'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
				'uploadfolder' => 'uploads/pics',
				'show_thumbs' => '1',
				'size' => '3',
				'maxitems' => '6',
				'minitems' => '0'
			)
		),
		'disable' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.disable',
			'config' => array(
				'type' => 'check'
			)
		),
		'starttime' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.starttime',
			'config' => array(
				'type' => 'input',
				'size' => '13',
				'max' => '20',
				'eval' => 'datetime',
				'default' => '0'
			)
		),
		'endtime' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.endtime',
			'config' => array(
				'type' => 'input',
				'size' => '13',
				'max' => '20',
				'eval' => 'datetime',
				'default' => '0',
				'range' => array(
					'upper' => mktime(0, 0, 0, 12, 31, 2020)
				)
			)
		),
		'TSconfig' => array(
			'exclude' => 1,
			'label' => 'TSconfig:',
			'config' => array(
				'type' => 'text',
				'cols' => '40',
				'rows' => '10',
				'wizards' => array(
					'_PADDING' => 4,
					'0' => array(
						'title' => 'TSconfig QuickReference',
						'script' => 'wizard_tsconfig.php?mode=fe_users',
						'icon' => 'wizard_tsconfig.gif',
						'JSopenParams' => 'height=500,width=780,status=0,menubar=0,scrollbars=1'
					)
				),
				'softref' => 'TSconfig'
			),
			'defaultExtras' => 'fixed-font : enable-tab'
		),
		'lastlogin' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.lastlogin',
			'config' => array(
				'type' => 'input',
				'readOnly' => '1',
				'size' => '12',
				'eval' => 'datetime',
				'default' => 0
			)
		)
	),
	'types' => array(
		'0' => array('showitem' => '
			disable,username;;;;1-1-1, password, usergroup, lastlogin;;;;1-1-1,
			--div--;LLL:EXT:cms/locallang_tca.xlf:fe_users.tabs.personelData, company;;1;;1-1-1, name;;2;;2-2-2, address, zip, city, country, telephone, fax, email, www, image;;;;2-2-2,
			--div--;LLL:EXT:cms/locallang_tca.xlf:fe_users.tabs.options, lockToDomain;;;;1-1-1, TSconfig;;;;2-2-2,
			--div--;LLL:EXT:cms/locallang_tca.xlf:fe_users.tabs.access, starttime, endtime,
			--div--;LLL:EXT:cms/locallang_tca.xlf:fe_users.tabs.extended

		')
	),
	'palettes' => array(
		'1' => array('showitem' => 'title'),
		'2' => array('showitem' => 'first_name,--linebreak--,middle_name,--linebreak--,last_name')
	)
);
?>
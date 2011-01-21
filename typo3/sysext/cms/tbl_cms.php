<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Dynamic configuation of the system-related tables, typ. sys_* series
 *
 * $Id$
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */





// ******************************************************************
// fe_users
//
// FrontEnd users - login on the website
// ******************************************************************
$TCA['fe_users'] = array(
	'ctrl' => $TCA['fe_users']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'username,password,usergroup,lockToDomain,name,first_name,middle_name,last_name,title,company,address,zip,city,country,email,www,telephone,fax,disable,starttime,endtime,lastlogin',
	),
	'feInterface' => $TCA['fe_users']['feInterface'],
	'columns' => array(
		'username' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xml:fe_users.username',
			'config' => array(
				'type' => 'input',
				'size' => '20',
				'max' => '50',
				'eval' => 'nospace,lower,uniqueInPid,required'
			)
		),
		'password' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xml:fe_users.password',
			'config' => array(
				'type' => 'input',
				'size' => '10',
				'max' => '40',
				'eval' => 'nospace,required,password'
			)
		),
		'usergroup' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xml:fe_users.usergroup',
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
			'label' => 'LLL:EXT:cms/locallang_tca.xml:fe_users.lockToDomain',
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
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.name',
			'config' => array(
				'type' => 'input',
				'size' => '40',
				'eval' => 'trim',
				'max' => '80'
			)
		),
		'first_name' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.first_name',
			'config' => array(
				'type' => 'input',
				'size' => '25',
				'eval' => 'trim',
				'max' => '50'
			)
		),
		'middle_name' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.middle_name',
			'config' => array(
				'type' => 'input',
				'size' => '25',
				'eval' => 'trim',
				'max' => '50'
			)
		),
		'last_name' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.last_name',
			'config' => array(
				'type' => 'input',
				'size' => '25',
				'eval' => 'trim',
				'max' => '50'
			)
		),
		'address' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.address',
			'config' => array(
				'type' => 'text',
				'cols' => '20',
				'rows' => '3'
			)
		),
		'telephone' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.phone',
			'config' => array(
				'type' => 'input',
				'eval' => 'trim',
				'size' => '20',
				'max' => '20'
			)
		),
		'fax' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.fax',
			'config' => array(
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '20'
			)
		),
		'email' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.email',
			'config' => array(
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '80'
			)
		),
		'title' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.title_person',
			'config' => array(
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '40'
			)
		),
		'zip' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.zip',
			'config' => array(
				'type' => 'input',
				'eval' => 'trim',
				'size' => '10',
				'max' => '10'
			)
		),
		'city' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.city',
			'config' => array(
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '50'
			)
		),
		'country' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.country',
			'config' => array(
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '40'
			)
		),
		'www' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.www',
			'config' => array(
				'type' => 'input',
				'eval' => 'trim',
				'size' => '20',
				'max' => '80'
			)
		),
		'company' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.company',
			'config' => array(
				'type' => 'input',
				'eval' => 'trim',
				'size' => '20',
				'max' => '80'
			)
		),
		'image' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.image',
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
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.disable',
			'config' => array(
				'type' => 'check'
			)
		),
		'starttime' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.starttime',
			'config' => array(
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'default' => '0',
			)
		),
		'endtime' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.endtime',
			'config' => array(
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'default' => '0',
				'range' => array(
					'upper' => mktime(0,0,0,12,31,2020),
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
#						'type' => t3lib_extMgm::isLoaded('tsconfig_help')?'popup':'',
						'title'  => 'TSconfig QuickReference',
						'script' => 'wizard_tsconfig.php?mode=fe_users',
						'icon'   => 'wizard_tsconfig.gif',
						'JSopenParams' => 'height=500,width=780,status=0,menubar=0,scrollbars=1',
					)
				),
				'softref' => 'TSconfig'
			),
			'defaultExtras' => 'fixed-font : enable-tab',
		),
		'lastlogin' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.lastlogin',
			'config' => array(
				'type' => 'input',
				'readOnly' => '1',
				'size' => '12',
				'eval' => 'datetime',
				'default' => 0,
			)
		)
	),
	'types' => array(
		'0' => array('showitem' => '
			disable,username;;;;1-1-1, password, usergroup, lastlogin;;;;1-1-1,
			--div--;LLL:EXT:cms/locallang_tca.xml:fe_users.tabs.personelData, company;;1;;1-1-1, name;;2;;2-2-2, address, zip, city, country, telephone, fax, email, www, image;;;;2-2-2,
			--div--;LLL:EXT:cms/locallang_tca.xml:fe_users.tabs.options, lockToDomain;;;;1-1-1, TSconfig;;;;2-2-2,
			--div--;LLL:EXT:cms/locallang_tca.xml:fe_users.tabs.access, starttime, endtime,
			--div--;LLL:EXT:cms/locallang_tca.xml:fe_users.tabs.extended

		')
	),
	'palettes' => array(
		'1' => array('showitem' => 'title'),
		'2' => array('showitem' => 'first_name,--linebreak--,middle_name,--linebreak--,last_name')
	)
);





// ******************************************************************
// fe_groups
//
// FrontEnd usergroups - Membership of these determines access to elements
// ******************************************************************
$TCA['fe_groups'] = array(
	'ctrl' => $TCA['fe_groups']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'title,hidden,subgroup,lockToDomain,description'
	),
	'columns' => array(
		'hidden' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.disable',
			'exclude' => 1,
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),
		'title' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xml:fe_groups.title',
			'config' => array(
				'type' => 'input',
				'size' => '20',
				'max' => '50',
				'eval' => 'trim,required'
			)
		),
		'subgroup' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:fe_groups.subgroup',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'fe_groups',
				'foreign_table_where' => 'AND NOT(fe_groups.uid = ###THIS_UID###) AND fe_groups.hidden=0 ORDER BY fe_groups.title',
				'size' => 6,
				'autoSizeMax' => 10,
				'minitems' => 0,
				'maxitems' => 20
			)
		),
		'lockToDomain' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:fe_groups.lockToDomain',
			'config' => array(
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '50',
			)
		),
		'description' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.description',
			'config' => array(
				'type' => 'text',
				'rows' => 5,
				'cols' => 48
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
#						'type' => t3lib_extMgm::isLoaded('tsconfig_help')?'popup':'',
						'title' => 'TSconfig QuickReference',
						'script' => 'wizard_tsconfig.php?mode=fe_users',
						'icon' => 'wizard_tsconfig.gif',
						'JSopenParams' => 'height=500,width=780,status=0,menubar=0,scrollbars=1',
					)
				),
				'softref' => 'TSconfig'
			),
			'defaultExtras' => 'fixed-font : enable-tab',
		)
	),
	'types' => array(
		'0' => array('showitem' => '
			hidden;;;;1-1-1,title;;;;2-2-2,description,subgroup;;;;3-3-3,
			--div--;LLL:EXT:cms/locallang_tca.xml:fe_groups.tabs.options, lockToDomain;;;;1-1-1, TSconfig;;;;2-2-2,
			--div--;LLL:EXT:cms/locallang_tca.xml:fe_groups.tabs.extended
		')
	)
);




// ******************************************************************
// sys_domain
// ******************************************************************
$TCA['sys_domain'] = array(
	'ctrl' => $TCA['sys_domain']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'hidden,domainName,redirectTo'
	),
	'columns' => array(
		'domainName' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xml:sys_domain.domainName',
			'config' => array(
				'type' => 'input',
				'size' => '35',
				'max' => '80',
				'eval' => 'required,unique,lower,trim',
				'softref' => 'substitute'
			),
		),
		'redirectTo' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xml:sys_domain.redirectTo',
			'config' => array(
				'type' => 'input',
				'size' => '35',
				'max' => '120',
				'default' => '',
				'eval' => 'trim',
				'softref' => 'substitute'
			),
		),
		'redirectHttpStatusCode' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:sys_domain.redirectHttpStatusCode',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('LLL:EXT:cms/locallang_tca.xml:sys_domain.redirectHttpStatusCode.301', '301'),
					array('LLL:EXT:cms/locallang_tca.xml:sys_domain.redirectHttpStatusCode.302', '302'),
					array('LLL:EXT:cms/locallang_tca.xml:sys_domain.redirectHttpStatusCode.303', '303'),
					array('LLL:EXT:cms/locallang_tca.xml:sys_domain.redirectHttpStatusCode.307', '307'),
				),
				'size' => 1,
				'maxitems' => 1,
			),
		),
		'hidden' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.disable',
			'exclude' => 1,
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),
		'prepend_params' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xml:sys_domain.prepend_params',
			'exclude' => 1,
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),
		'forced' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xml:sys_domain.forced',
			'exclude' => 1,
			'config' => array(
				'type' => 'check',
				'default' => '1'
			)
		)
	),
	'types' => array(
		'1' => array('showitem' => 'hidden;;;;1-1-1,domainName;;1;;3-3-3,prepend_params,forced;;;;4-4-4')
	),
	'palettes' => array(
		'1' => array('showitem' => 'redirectTo, redirectHttpStatusCode')
	)
);





// ******************************************************************
// pages_language_overlay
// ******************************************************************
$TCA['pages_language_overlay'] = array(
	'ctrl' => $TCA['pages_language_overlay']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'title,hidden,starttime,endtime,keywords,description,abstract'
	),
	'columns' => array(
		'doktype' => $TCA['pages']['columns']['doktype'],
		'hidden' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.hidden',
			'config' => array(
				'type' => 'check',
				'default' => '0',
				'items' => array(
					'1' => array(
						'0' => 'LLL:EXT:cms/locallang_tca.xml:pages.hidden_checkbox_1_formlabel',
					),
				),
			)
		),
		'starttime' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.starttime',
			'config' => array(
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'default' => '0'
			)
		),
		'endtime' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.endtime',
			'config' => array(
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'default' => '0',
				'range' => array(
					'upper' => mktime(0,0,0,12,31,2020),
				)
			)
		),
		'title' => array(
			'l10n_mode' => 'prefixLangTitle',
			'label' => $TCA['pages']['columns']['title']['label'],
			'l10n_cat' => 'text',
			'config' => array(
				'type' => 'input',
				'size' => '50',
				'max' => '255',
				'eval' => 'trim,required',
			)
		),
		'subtitle' => array(
			'exclude' => 1,
			'l10n_cat' => 'text',
			'label' => $TCA['pages']['columns']['subtitle']['label'],
			'config' => array(
				'type' => 'input',
				'size' => '50',
				'max' => '255',
				'eval' => 'trim',
			)
		),
		'nav_title' => array(
			'exclude' => 1,
			'l10n_cat' => 'text',
			'label' => $TCA['pages']['columns']['nav_title']['label'],
			'config' => array(
				'type' => 'input',
				'size' => '50',
				'max' => '255',
				'eval' => 'trim',
			)
		),
		'keywords' => array(
			'exclude' => 1,
			'label' => $TCA['pages']['columns']['keywords']['label'],
			'config' => array(
				'type' => 'text',
				'cols' => '40',
				'rows' => '3'
			)
		),
		'description' => array(
			'exclude' => 1,
			'label' => $TCA['pages']['columns']['description']['label'],
			'config' => array(
				'type' => 'text',
				'cols' => '40',
				'rows' => '3'
			)
		),
		'abstract' => array(
			'exclude' => 1,
			'label' => $TCA['pages']['columns']['abstract']['label'],
			'config' => array(
				'type' => 'text',
				'cols' => '40',
				'rows' => '3'
			)
		),
		'author' => array(
			'exclude' => 1,
			'label' => $TCA['pages']['columns']['author']['label'],
			'config' => array(
				'type' => 'input',
				'size' => '23',
				'eval' => 'trim',
				'max' => '80',
			)
		),
		'author_email' => array(
			'exclude' => 1,
			'label' => $TCA['pages']['columns']['author_email']['label'],
			'config' => array(
				'type' => 'input',
				'size' => '23',
				'eval' => 'trim',
				'max' => '80',
				'softref' => 'email[subst]',
			)
		),
		'media' => array(
			'exclude' => 1,
			'label' => $TCA['pages']['columns']['media']['label'],
			'config' => array(
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => $TCA['pages']['columns']['media']['config']['allowed'],
				'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
				'uploadfolder' => 'uploads/media',
				'show_thumbs' => '1',
				'size' => '3',
				'maxitems' => '5',
				'minitems' => '0'
			)
		),
		'url' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.url',
			'config' => array(
				'type' => 'input',
				'size' => '23',
				'max' => '255',
				'eval' => 'trim',
				'softref' => 'url',
			)
		),
		'urltype' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.type',
			'config' => array(
				'type' => 'select',
				'items' => $TCA['pages']['columns']['urltype']['config']['items'],
				'default' => '1'
			)
		),
		'shortcut' => array (
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.shortcut_page',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'pages',
				'size' => '1',
				'maxitems' => '1',
				'minitems' => '0',
				'show_thumbs' => '1',
				'wizards' => array(
					'suggest' => array(
						'type' => 'suggest',
					),
				),
			),
		),
		'shortcut_mode' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.shortcut_mode',
			'config' => array (
				'type' => 'select',
				'items' => $TCA['pages']['columns']['shortcut_mode']['config']['items'],
				'default' => '0'
			)
		),
		'sys_language_uid' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value',0)
				)
			)
		),
		'tx_impexp_origuid' => array('config'=>array('type'=>'passthrough')),
		'l18n_diffsource' => array('config'=>array('type'=>'passthrough')),
		't3ver_label' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.versionLabel',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'max' => '255',
			)
		),
	),
	'types' => array(
		// normal
		(string) t3lib_pageSelect::DOKTYPE_DEFAULT => array(
			'showitem' =>
			'--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.standard;standard,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.title;title,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.visibility;hiddenonly,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.access;access,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.metadata,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.abstract;abstract,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.metatags;metatags,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.editorial;editorial,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.resources,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.media;media,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.extended,
		'),
		// external URL
		(string) t3lib_pageSelect::DOKTYPE_LINK => array(
			'showitem' =>
			'--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.external;external,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.title;title,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.visibility;hiddenonly,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.access;access,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.metadata,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.abstract;abstract,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.editorial;editorial,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.resources,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.media;media,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.extended,
		'),
		// shortcut
		(string) t3lib_pageSelect::DOKTYPE_SHORTCUT => array(
			'showitem' =>
			'--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.shortcut;shortcut,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.shortcutpage;shortcutpage,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.title;title,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.visibility;hiddenonly,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.access;access,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.metadata,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.abstract;abstract,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.editorial;editorial,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.resources,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.media;media,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.extended,
				'),
		// not in menu
		(string) t3lib_pageSelect::DOKTYPE_HIDE_IN_MENU => array(
			'showitem' =>
			'--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.standard;standard,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.title;title,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.visibility;hiddenonly,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.access;access,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.metadata,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.abstract;abstract,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.metatags;metatags,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.editorial;editorial,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.resources,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.media;media,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.extended,
		'),
		// mount page
		(string) t3lib_pageSelect::DOKTYPE_MOUNTPOINT => array(
			'showitem' =>
			'--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.standard;standard,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.title;title,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.visibility;hiddenonly,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.access;access,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.metadata,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.abstract;abstract,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.editorial;editorial,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.resources,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.media;media,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.extended,
		'),
		// spacer
		(string) t3lib_pageSelect::DOKTYPE_SPACER => array(
			'showitem' =>
			'--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.standard;standard,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.title;titleonly,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.visibility;hiddenonly,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.access;access,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.extended,
			'),
		// sysfolder
		(string) t3lib_pageSelect::DOKTYPE_SYSFOLDER => array(
			'showitem' =>
			'--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.standard;standard,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.title;titleonly,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.visibility;hiddenonly,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.resources,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.media;media,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.extended,
		'),
		// trash
		(string) t3lib_pageSelect::DOKTYPE_RECYCLER => array(
			'showitem' =>
			'--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.standard;standard,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.title;titleonly,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.visibility;hiddenonly,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.extended,
		'),
	),
	'palettes' => array(
		'5' => array('showitem' => 'author,author_email', 'canNotCollapse' => true),
		'standard' => array(
			'showitem' => 'doktype;LLL:EXT:cms/locallang_tca.xml:pages.doktype_formlabel, sys_language_uid',
			'canNotCollapse' => 1,
		),
		'shortcut' => array(
			'showitem' => 'doktype;LLL:EXT:cms/locallang_tca.xml:pages.doktype_formlabel, sys_language_uid, shortcut_mode;LLL:EXT:cms/locallang_tca.xml:pages.shortcut_mode_formlabel',
			'canNotCollapse' => 1,
		),
		'shortcutpage' => array(
			'showitem' => 'shortcut;LLL:EXT:cms/locallang_tca.xml:pages.shortcut_formlabel',
			'canNotCollapse' => 1,
		),
		'external' => array(
			'showitem' => 'doktype;LLL:EXT:cms/locallang_tca.xml:pages.doktype_formlabel, sys_language_uid, urltype;LLL:EXT:cms/locallang_tca.xml:pages.urltype_formlabel, url;LLL:EXT:cms/locallang_tca.xml:pages.url_formlabel',
			'canNotCollapse' => 1,
		),
		'title' => array(
			'showitem' => 'title;LLL:EXT:cms/locallang_tca.xml:pages.title_formlabel, --linebreak--, nav_title;LLL:EXT:cms/locallang_tca.xml:pages.nav_title_formlabel, --linebreak--, subtitle;LLL:EXT:cms/locallang_tca.xml:pages.subtitle_formlabel',
			'canNotCollapse' => 1,
		),
		'titleonly' => array(
			'showitem' => 'title;LLL:EXT:cms/locallang_tca.xml:pages.title_formlabel',
			'canNotCollapse' => 1,
		),
		'hiddenonly' => array(
			'showitem' => 'hidden;LLL:EXT:cms/locallang_tca.xml:pages.hidden_formlabel',
			'canNotCollapse' => 1,
		),
		'access' => array(
			'showitem' => 'starttime;LLL:EXT:cms/locallang_tca.xml:pages.starttime_formlabel, endtime;LLL:EXT:cms/locallang_tca.xml:pages.endtime_formlabel',
			'canNotCollapse' => 1,
		),
		'abstract' => array(
			'showitem' => 'abstract;LLL:EXT:cms/locallang_tca.xml:pages.abstract_formlabel',
			'canNotCollapse' => 1,
		),
		'metatags' => array(
			'showitem' => 'keywords;LLL:EXT:cms/locallang_tca.xml:pages.keywords_formlabel, --linebreak--, description;LLL:EXT:cms/locallang_tca.xml:pages.description_formlabel',
			'canNotCollapse' => 1,
		),
		'editorial' => array(
			'showitem' => 'author;LLL:EXT:cms/locallang_tca.xml:pages.author_formlabel, author_email;LLL:EXT:cms/locallang_tca.xml:pages.author_email_formlabel',
			'canNotCollapse' => 1,
		),
		'language' => array(
			'showitem' => 'l18n_cfg;LLL:EXT:cms/locallang_tca.xml:pages.l18n_cfg_formlabel',
			'canNotCollapse' => 1,
		),
		'media' => array(
			'showitem' => 'media;LLL:EXT:cms/locallang_tca.xml:pages.media_formlabel',
			'canNotCollapse' => 1,
		)
	)
);



// ******************************************************************
// sys_template
// ******************************************************************
$TCA['sys_template'] = array(
	'ctrl' => $TCA['sys_template']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'title,clear,root,basedOn,nextLevel,resources,sitetitle,description,hidden,starttime,endtime'
	),
	'columns' => array(
		'title' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xml:sys_template.title',
			'config' => array(
				'type' => 'input',
				'size' => '25',
				'max' => '256',
				'eval' => 'required'
			)
		),
		'hidden' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.disable',
			'exclude' => 1,
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),
		'starttime' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.starttime',
			'exclude' => 1,
			'config' => array(
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'default' => '0'
			)
		),
		'endtime' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.endtime',
			'exclude' => 1,
			'config' => array(
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'default' => '0',
				'range' => array(
					'upper' => mktime(0,0,0,12,31,2020),
				)
			)
		),
		'root' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xml:sys_template.root',
			'config' => array(
				'type' => 'check'
			)
		),
		'clear' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xml:sys_template.clear',
			'config' => array(
				'type' => 'check',
				'items' => array(
					array('Constants', ''),
					array('Setup', '')
				),
				'cols' => 2
			)
		),
		'sitetitle' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xml:sys_template.sitetitle',
			'config' => array(
				'type' => 'input',
				'size' => '25',
				'max' => '256'
			)
		),
		'constants' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xml:sys_template.constants',
			'config' => array(
				'type' => 'text',
				'cols' => '48',
				'rows' => '10',
				'wrap' => 'OFF',
				'softref' => 'TStemplate,email[subst],url[subst]'
			),
			'defaultExtras' => 'fixed-font : enable-tab',
		),
		'resources' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xml:sys_template.resources',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'].',html,htm,ttf,pfb,pfm,txt,css,tmpl,inc,ico,js,xml',
				'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
				'uploadfolder' => 'uploads/tf',
				'show_thumbs' => '1',
				'size' => '7',
				'maxitems' => '100',
				'minitems' => '0'
			)
		),
		'nextLevel' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xml:sys_template.nextLevel',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'sys_template',
				'show_thumbs' => '1',
				'size' => '1',
				'maxitems' => '1',
				'minitems' => '0',
				'default' => '',
				'wizards' => array(
					'suggest' => array(
						'type' => 'suggest',
					),
				),
			)
		),
		'include_static_file' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xml:sys_template.include_static_file',
			'config' => array(
				'type' => 'select',
				'size' => 10,
				'maxitems' => 100,
				'items' => array(
				),
				'softref' => 'ext_fileref'
			)
		),
		'basedOn' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xml:sys_template.basedOn',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'sys_template',
				'show_thumbs' => '1',
				'size' => '3',
				'maxitems' => '50',
				'autoSizeMax' => 10,
				'minitems' => '0',
				'default' => '',
				'wizards' => array(
					'_PADDING' => 4,
					'_VERTICAL' => 1,
					'suggest' => array(
						'type' => 'suggest',
					),
					'edit' => array(
						'type' => 'popup',
						'title' => 'Edit template',
						'script' => 'wizard_edit.php',
						'popup_onlyOpenIfSelected' => 1,
						'icon' => 'edit2.gif',
						'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
					),
					'add' => array(
						'type' => 'script',
						'title' => 'LLL:EXT:cms/locallang_tca.xml:sys_template.basedOn_add',
						'icon' => 'add.gif',
						'params' => array(
							'table'=>'sys_template',
							'pid' => '###CURRENT_PID###',
							'setValue' => 'prepend'
						),
						'script' => 'wizard_add.php',
					)
				)
			)
		),
		'includeStaticAfterBasedOn' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xml:sys_template.includeStaticAfterBasedOn',
			'exclude' => 1,
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),
		'config' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xml:sys_template.config',
			'config' => array(
				'type' => 'text',
				'rows' => 10,
				'cols' => 48,
				'wizards' => array(
					'_PADDING' => 4,
					'0' => array(
#						'type' => t3lib_extMgm::isLoaded('tsconfig_help')?'popup':'',
						'title' => 'TSref online',
						'script' => 'wizard_tsconfig.php?mode=tsref',
						'icon' => 'wizard_tsconfig.gif',
						'JSopenParams' => 'height=500,width=780,status=0,menubar=0,scrollbars=1',
					)
				),
				'wrap' => 'OFF',
				'softref' => 'TStemplate,email[subst],url[subst]'
			),
			'defaultExtras' => 'fixed-font : enable-tab',
		),
		'editorcfg' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xml:sys_template.editorcfg',
			'config' => array(
				'type' => 'text',
				'rows' => 8,
				'cols' => 48,
				'wrap' => 'OFF'
			),
			'defaultExtras' => 'fixed-font : enable-tab',
		),
		'description' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xml:sys_template.description',
			'config' => array(
				'type' => 'text',
				'rows' => 5,
				'cols' => 48
			)
		),
		'static_file_mode' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xml:sys_template.static_file_mode',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('LLL:EXT:cms/locallang_tca.xml:sys_template.static_file_mode.0', '0'),
					array('LLL:EXT:cms/locallang_tca.xml:sys_template.static_file_mode.1', '1'),
					array('LLL:EXT:cms/locallang_tca.xml:sys_template.static_file_mode.2', '2'),
				),
				'default' => '0'
			)
		),
		'tx_impexp_origuid' => array('config' => array('type' => 'passthrough')),
		't3ver_label' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.versionLabel',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'max'  => '255',
			)
		),
	),
	'types' => array(
		'1' => array('showitem' => '
			hidden,title;;1;;2-2-2, sitetitle, constants;;;;3-3-3, config, description;;;;4-4-4,
			--div--;LLL:EXT:cms/locallang_tca.xml:sys_template.tabs.options, clear, root, nextLevel, editorcfg;;;;5-5-5,
			--div--;LLL:EXT:cms/locallang_tca.xml:sys_template.tabs.include, includeStaticAfterBasedOn,6-6-6, include_static_file, basedOn, static_file_mode,
			--div--;LLL:EXT:cms/locallang_tca.xml:sys_template.tabs.files, resources,
			--div--;LLL:EXT:cms/locallang_tca.xml:sys_template.tabs.access, starttime, endtime'
		)
	)
);

// ******************************************************************
// backend_layout
// ******************************************************************
/**
 * @todo add lll
 */
$TCA['backend_layout'] = array(
	'ctrl' => $TCA['backend_layout']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'title,config,description,hidden,icon'
	),
	'columns' => array(
		'title' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xml:backend_layout.title',
			'config' => array(
				'type' => 'input',
				'size' => '25',
				'max' => '256',
				'eval' => 'required'
			)
		),
		'description' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xml:backend_layout.description',
			'config' => array(
				'type' => 'text',
				'rows' => '5',
				'cols' => '25',
			)
		),
		'config' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xml:backend_layout.config',
			'config' => array(
				'type' => 'text',
				'rows' => '5',
				'cols' => '25',
				'wizards' => Array(
					'_PADDING' => 4,
					0 => Array(
						'title' => 'LLL:EXT:cms/locallang_tca.xml:backend_layout.wizard',
						'type' => 'popup',
						'icon' => t3lib_extMgm::extRelPath('cms').'layout/wizard_backend_layout.png',
						'script' => t3lib_extMgm::extRelPath('cms').'layout/wizard_backend_layout.php',
						'JSopenParams' => 'height=800,width=800,status=0,menubar=0,scrollbars=0',
					),
				),
			)
		),
		'hidden' => array(
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.disable',
			'exclude' => 1,
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),
		'icon' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xml:backend_layout.icon',
			'exclude' => 1,
			'config' => array(
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => 'jpg,gif,png',
				'uploadfolder' => 'uploads/media',
				'show_thumbs' => 1,
				'size' => 1,
				'maxitems' => 1
			)
		),
	),
	'types' => array(
		'1' => array('showitem' => '
			hidden,title;;1;;2-2-2, icon, description, config'
		)
	)
);


?>
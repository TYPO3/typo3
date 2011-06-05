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
 * Contains the dynamic configuation of the fields in the core tables of TYPO3: be_users, be_groups and sys_filemounts
 *
 * Revised for TYPO3 3.6 July/2003 by Kasper Skårhøj
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @see tables.php, tables.sql
 */


/**
 * Backend users - Those who login into the TYPO3 administration backend
 */
$TCA['be_users'] = array(
	'ctrl' => $TCA['be_users']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'username,usergroup,db_mountpoints,file_mountpoints,admin,options,fileoper_perms,userMods,lockToDomain,realName,email,disable,starttime,endtime,lastlogin'
	),
	'columns' => array(
		'username' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_users.username',
			'config' => array(
				'type' => 'input',
				'size' => '20',
				'max' => '50',
				'eval' => 'nospace,lower,unique,required'
			)
		),
		'password' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_users.password',
			'config' => array(
				'type' => 'input',
				'size' => '20',
				'max' => '40',
				'eval' => 'required,md5,password'
			)
		),
		'usergroup' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_users.usergroup',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'be_groups',
				'foreign_table_where' => 'ORDER BY be_groups.title',
				'size' => '5',
				'maxitems' => '20',
				#				'renderMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['accessListRenderMode'],
				'iconsInOptionTags' => 1,
				'wizards' => array(
					'_PADDING' => 1,
					'_VERTICAL' => 1,
					'edit' => array(
						'type' => 'popup',
						'title' => 'LLL:EXT:lang/locallang_tca.xml:be_users.usergroup_edit_title',
						'script' => 'wizard_edit.php',
						'popup_onlyOpenIfSelected' => 1,
						'icon' => 'edit2.gif',
						'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
					),
					'add' => array(
						'type' => 'script',
						'title' => 'LLL:EXT:lang/locallang_tca.xml:be_users.usergroup_add_title',
						'icon' => 'add.gif',
						'params' => array(
							'table' => 'be_groups',
							'pid' => '0',
							'setValue' => 'prepend'
						),
						'script' => 'wizard_add.php',
					),
					'list' => array(
						'type' => 'script',
						'title' => 'LLL:EXT:lang/locallang_tca.xml:be_users.usergroup_list_title',
						'icon' => 'list.gif',
						'params' => array(
							'table' => 'be_groups',
							'pid' => '0',
						),
						'script' => 'wizard_list.php',
					)
				)
			)
		),
		'lockToDomain' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:lockToDomain',
			'config' => array(
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '50',
				'softref' => 'substitute'
			)
		),
		'db_mountpoints' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_users.options_db_mounts',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'pages',
				'size' => '3',
				'maxitems' => '10',
				'autoSizeMax' => 10,
				'show_thumbs' => '1',
				'wizards' => array(
					'suggest' => array(
						'type' => 'suggest',
					),
				),
			)
		),
		'file_mountpoints' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_users.options_file_mounts',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'sys_filemounts',
				'foreign_table_where' => ' AND sys_filemounts.pid=0 ORDER BY sys_filemounts.title',
				'size' => '3',
				'maxitems' => '10',
				'autoSizeMax' => 10,
				'iconsInOptionTags' => 1,
				'wizards' => array(
					'_PADDING' => 1,
					'_VERTICAL' => 1,
					'edit' => array(
						'type' => 'popup',
						'title' => 'LLL:EXT:lang/locallang_tca.xml:file_mountpoints_edit_title',
						'script' => 'wizard_edit.php',
						'icon' => 'edit2.gif',
						'popup_onlyOpenIfSelected' => 1,
						'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
					),
					'add' => array(
						'type' => 'script',
						'title' => 'LLL:EXT:lang/locallang_tca.xml:file_mountpoints_add_title',
						'icon' => 'add.gif',
						'params' => array(
							'table' => 'sys_filemounts',
							'pid' => '0',
							'setValue' => 'prepend'
						),
						'script' => 'wizard_add.php',
					),
					'list' => array(
						'type' => 'script',
						'title' => 'LLL:EXT:lang/locallang_tca.xml:file_mountpoints_list_title',
						'icon' => 'list.gif',
						'params' => array(
							'table' => 'sys_filemounts',
							'pid' => '0',
						),
						'script' => 'wizard_list.php',
					)
				)
			)
		),
		'email' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.email',
			'config' => array(
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '80',
				'softref' => 'email[subst]'
			)
		),
		'realName' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.name',
			'config' => array(
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '80'
			)
		),
		'disable' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.disable',
			'config' => array(
				'type' => 'check'
			)
		),
		'disableIPlock' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_users.disableIPlock',
			'config' => array(
				'type' => 'check'
			)
		),
		'admin' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_users.admin',
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),
		'options' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_users.options',
			'config' => array(
				'type' => 'check',
				'items' => array(
					array('LLL:EXT:lang/locallang_tca.xml:be_users.options_db_mounts', 0),
					array('LLL:EXT:lang/locallang_tca.xml:be_users.options_file_mounts', 0)
				),
				'default' => '3'
			)
		),
		'fileoper_perms' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_users.fileoper_perms',
			'config' => array(
				'type' => 'check',
				'items' => array(
					array('LLL:EXT:lang/locallang_tca.xml:be_users.fileoper_perms_general', 0),
					array('LLL:EXT:lang/locallang_tca.xml:be_users.fileoper_perms_unzip', 0),
					array('LLL:EXT:lang/locallang_tca.xml:be_users.fileoper_perms_diroper_perms', 0),
					array('LLL:EXT:lang/locallang_tca.xml:be_users.fileoper_perms_diroper_perms_copy', 0),
					array('LLL:EXT:lang/locallang_tca.xml:be_users.fileoper_perms_diroper_perms_delete', 0),
				),
				'default' => '0'
			)
		),
		'workspace_perms' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:workspace_perms',
			'config' => array(
				'type' => 'check',
				'items' => array(
					array('LLL:EXT:lang/locallang_tca.xml:workspace_perms_live', 0),
					array('LLL:EXT:lang/locallang_tca.xml:workspace_perms_draft', 0),
				),
				'default' => 3
			)
		),
		'starttime' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.starttime',
			'config' => array(
				'type' => 'input',
				'size' => '13',
				'max' => '20',
				'eval' => 'datetime',
				'default' => '0',
			)
		),
		'endtime' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.endtime',
			'config' => array(
				'type' => 'input',
				'size' => '13',
				'max' => '20',
				'eval' => 'datetime',
				'default' => '0',
				'range' => array(
					'upper' => mktime(0, 0, 0, 12, 31, 2020),
				)
			)
		),
		'lang' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_users.lang',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('English', ''),
					array('Albanian', 'sq'),
					array('Arabic', 'ar'),
					array('Basque', 'eu'),
					array('Bosnian', 'ba'),
					array('Brazilian Portuguese', 'br'),
					array('Bulgarian', 'bg'),
					array('Catalan', 'ca'),
					array('Chinese (Simpl.)', 'ch'),
					array('Chinese (Trad.)', 'hk'),
					array('Croatian', 'hr'),
					array('Czech', 'cz'),
					array('Danish', 'dk'),
					array('Dutch', 'nl'),
					array('Esperanto', 'eo'),
					array('Estonian', 'et'),
					array('Faroese', 'fo'),
					array('Finnish', 'fi'),
					array('French', 'fr'),
					array('French (Canada)', 'qc'),
					array('Galician', 'ga'),
					array('Georgian', 'ge'),
					array('German', 'de'),
					array('Greek', 'gr'),
					array('Greenlandic', 'gl'),
					array('Hebrew', 'he'),
					array('Hindi', 'hi'),
					array('Hungarian', 'hu'),
					array('Icelandic', 'is'),
					array('Italian', 'it'),
					array('Japanese', 'jp'),
					array('Khmer', 'km'),
					array('Korean', 'kr'),
					array('Latvian', 'lv'),
					array('Lithuanian', 'lt'),
					array('Malay', 'my'),
					array('Norwegian', 'no'),
					array('Persian', 'fa'),
					array('Polish', 'pl'),
					array('Portuguese', 'pt'),
					array('Romanian', 'ro'),
					array('Russian', 'ru'),
					array('Serbian', 'sr'),
					array('Slovak', 'sk'),
					array('Slovenian', 'si'),
					array('Spanish', 'es'),
					array('Swedish', 'se'),
					array('Thai', 'th'),
					array('Turkish', 'tr'),
					array('Ukrainian', 'ua'),
					array('Vietnamese', 'vn'),
				)
			)
		),
		'userMods' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:userMods',
			'config' => array(
				'type' => 'select',
				'special' => 'modListUser',
				'size' => '5',
				'autoSizeMax' => 50,
				'maxitems' => '100',
				'renderMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['accessListRenderMode'],
				'iconsInOptionTags' => 1,
			)
		),
		'allowed_languages' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:allowed_languages',
			'config' => array(
				'type' => 'select',
				'special' => 'languages',
				'maxitems' => '1000',
				'renderMode' => 'checkbox',
			)
		),
		'TSconfig' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:TSconfig',
			'config' => array(
				'type' => 'text',
				'cols' => '40',
				'rows' => '5',
				'wizards' => array(
					'_PADDING' => 4,
					'0' => array(
						'type' => t3lib_extMgm::isLoaded('tsconfig_help') ? 'popup' : '',
						'title' => 'LLL:EXT:lang/locallang_tca.xml:TSconfig_title',
						'script' => 'wizard_tsconfig.php?mode=beuser',
						'icon' => 'wizard_tsconfig.gif',
						'JSopenParams' => 'height=500,width=780,status=0,menubar=0,scrollbars=1',
					)
				),
				'softref' => 'TSconfig'
			),
			'defaultExtras' => 'fixed-font : enable-tab',
		),
		'createdByAction' => array(
			'config' => array(
				'type' => 'passthrough'
			)
		),
		'lastlogin' => array(
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.lastlogin',
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
		'0' => array('showitem' => 'disable;;;;1-1-1, username;;;;2-2-2, password, usergroup;;;;3-3-3, admin;;;;1-1-1, realName;;;;3-3-3, email, lang, lastlogin;;;;1-1-1,
			--div--;LLL:EXT:lang/locallang_tca.xml:be_users.tabs.rights, userMods;;;;2-2-2, allowed_languages,
			--div--;LLL:EXT:lang/locallang_tca.xml:be_users.tabs.mounts_and_workspaces, workspace_perms;;;;1-1-1, db_mountpoints;;;;2-2-2, options, file_mountpoints;;;;3-3-3, fileoper_perms,
			--div--;LLL:EXT:lang/locallang_tca.xml:be_users.tabs.options, lockToDomain;;;;1-1-1, disableIPlock, TSconfig;;;;2-2-2,
			--div--;LLL:EXT:lang/locallang_tca.xml:be_users.tabs.access, starttime;;;;1-1-1,endtime,
			--div--;LLL:EXT:lang/locallang_tca.xml:be_users.tabs.extended'
		),
		'1' => array('showitem' => 'disable;;;;1-1-1, username;;;;2-2-2, password, usergroup;;;;3-3-3, admin;;;;1-1-1, realName;;;;3-3-3, email, lang, lastlogin;;;;1-1-1,
			--div--;LLL:EXT:lang/locallang_tca.xml:be_users.tabs.options, disableIPlock;;;;1-1-1, TSconfig;;;;2-2-2, db_mountpoints;;;;3-3-3, file_mountpoints;;;;4-4-4,
			--div--;LLL:EXT:lang/locallang_tca.xml:be_users.tabs.access, starttime;;;;1-1-1,endtime,
			--div--;LLL:EXT:lang/locallang_tca.xml:be_users.tabs.extended'
		)
	),
);


/**
 * Backend usergroups - Much permission criterias are based on membership of backend groups.
 */
$TCA['be_groups'] = array(
	'ctrl' => $TCA['be_groups']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'title, db_mountpoints, file_mountpoints, fileoper_perms, inc_access_lists, tables_select, tables_modify, pagetypes_select, non_exclude_fields, groupMods, lockToDomain, description'
	),
	'columns' => array(
		'title' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_groups.title',
			'config' => array(
				'type' => 'input',
				'size' => '25',
				'max' => '50',
				'eval' => 'trim,required'
			)
		),
		'db_mountpoints' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:db_mountpoints',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'pages',
				'size' => '3',
				'maxitems' => 20,
				'autoSizeMax' => 10,
				'show_thumbs' => '1',
				'wizards' => array(
					'suggest' => array(
						'type' => 'suggest',
					),
				),
			)
		),
		'file_mountpoints' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:file_mountpoints',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'sys_filemounts',
				'foreign_table_where' => ' AND sys_filemounts.pid=0 ORDER BY sys_filemounts.title',
				'size' => '3',
				'maxitems' => 20,
				'autoSizeMax' => 10,
				'iconsInOptionTags' => 1,
				'wizards' => array(
					'_PADDING' => 1,
					'_VERTICAL' => 1,
					'edit' => array(
						'type' => 'popup',
						'title' => 'LLL:EXT:lang/locallang_tca.xml:file_mountpoints_edit_title',
						'script' => 'wizard_edit.php',
						'popup_onlyOpenIfSelected' => 1,
						'icon' => 'edit2.gif',
						'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
					),
					'add' => array(
						'type' => 'script',
						'title' => 'LLL:EXT:lang/locallang_tca.xml:file_mountpoints_add_title',
						'icon' => 'add.gif',
						'params' => array(
							'table' => 'sys_filemounts',
							'pid' => '0',
							'setValue' => 'prepend'
						),
						'script' => 'wizard_add.php',
					),
					'list' => array(
						'type' => 'script',
						'title' => 'LLL:EXT:lang/locallang_tca.xml:file_mountpoints_list_title',
						'icon' => 'list.gif',
						'params' => array(
							'table' => 'sys_filemounts',
							'pid' => '0',
						),
						'script' => 'wizard_list.php',
					)
				)
			)
		),
		'fileoper_perms' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_groups.fileoper_perms',
			'config' => array(
				'type' => 'check',
				'items' => array(
					array('LLL:EXT:lang/locallang_tca.xml:be_groups.fileoper_perms_general', 0),
					array('LLL:EXT:lang/locallang_tca.xml:be_groups.fileoper_perms_unzip', 0),
					array('LLL:EXT:lang/locallang_tca.xml:be_groups.fileoper_perms_diroper_perms', 0),
					array('LLL:EXT:lang/locallang_tca.xml:be_groups.fileoper_perms_diroper_perms_copy', 0),
					array('LLL:EXT:lang/locallang_tca.xml:be_groups.fileoper_perms_diroper_perms_delete', 0),
				),
				'default' => '7'
			)
		),
		'workspace_perms' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:workspace_perms',
			'config' => array(
				'type' => 'check',
				'items' => array(
					array('LLL:EXT:lang/locallang_tca.xml:workspace_perms_live', 0),
					array('LLL:EXT:lang/locallang_tca.xml:workspace_perms_draft', 0),
				),
				'default' => 0
			)
		),
		'pagetypes_select' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_groups.pagetypes_select',
			'config' => array(
				'type' => 'select',
				'special' => 'pagetypes',
				'size' => '5',
				'autoSizeMax' => 50,
				'maxitems' => 20,
				'renderMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['accessListRenderMode'],
				'iconsInOptionTags' => 1,
			)
		),
		'tables_modify' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_groups.tables_modify',
			'config' => array(
				'type' => 'select',
				'special' => 'tables',
				'size' => '5',
				'autoSizeMax' => 50,
				'maxitems' => 100,
				'renderMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['accessListRenderMode'],
				'iconsInOptionTags' => 1,
			)
		),
		'tables_select' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_groups.tables_select',
			'config' => array(
				'type' => 'select',
				'special' => 'tables',
				'size' => '5',
				'autoSizeMax' => 50,
				'maxitems' => 100,
				'renderMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['accessListRenderMode'],
				'iconsInOptionTags' => 1,
			)
		),
		'non_exclude_fields' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_groups.non_exclude_fields',
			'config' => array(
				'type' => 'select',
				'special' => 'exclude',
				'size' => '25',
				'maxitems' => 1000,
				'autoSizeMax' => 50,
				'renderMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['accessListRenderMode'],
				'itemListStyle' => 'width:500px',
			)
		),
		'explicit_allowdeny' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_groups.explicit_allowdeny',
			'config' => array(
				'type' => 'select',
				'special' => 'explicitValues',
				'maxitems' => 1000,
				'renderMode' => 'checkbox',
			)
		),
		'allowed_languages' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:allowed_languages',
			'config' => array(
				'type' => 'select',
				'special' => 'languages',
				'maxitems' => 1000,
				'renderMode' => 'checkbox',
			)
		),
		'custom_options' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_groups.custom_options',
			'config' => array(
				'type' => 'select',
				'special' => 'custom',
				'maxitems' => 1000,
				'renderMode' => 'checkbox',
			)
		),
		'hidden' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.disable',
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),
		'lockToDomain' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:lockToDomain',
			'config' => array(
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '50',
				'softref' => 'substitute'
			)
		),
		'groupMods' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:userMods',
			'config' => array(
				'type' => 'select',
				'special' => 'modListGroup',
				'size' => '5',
				'autoSizeMax' => 50,
				'maxitems' => 100,
				'renderMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['accessListRenderMode'],
				'iconsInOptionTags' => 1,
			)
		),
		'inc_access_lists' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_groups.inc_access_lists',
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),
		'description' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.description',
			'config' => array(
				'type' => 'text',
				'rows' => 5,
				'cols' => 30
			)
		),
		'TSconfig' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:TSconfig',
			'config' => array(
				'type' => 'text',
				'cols' => '40',
				'rows' => '5',
				'wizards' => array(
					'_PADDING' => 4,
					'0' => array(
						'type' => t3lib_extMgm::isLoaded('tsconfig_help') ? 'popup' : '',
						'title' => 'LLL:EXT:lang/locallang_tca.xml:TSconfig_title',
						'script' => 'wizard_tsconfig.php?mode=beuser',
						'icon' => 'wizard_tsconfig.gif',
						'JSopenParams' => 'height=500,width=780,status=0,menubar=0,scrollbars=1',
					)
				),
				'softref' => 'TSconfig'
			),
			'defaultExtras' => 'fixed-font : enable-tab',
		),
		'hide_in_lists' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_groups.hide_in_lists',
			'config' => array(
				'type' => 'check',
				'default' => 0
			)
		),
		'subgroup' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_groups.subgroup',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'be_groups',
				'foreign_table_where' => 'AND NOT(be_groups.uid = ###THIS_UID###) AND be_groups.hidden=0 ORDER BY be_groups.title',
				'size' => '5',
				'autoSizeMax' => 50,
				'maxitems' => 20,
				'iconsInOptionTags' => 1,
			)
		)
	),
	'types' => array(
		'0' => array('showitem' => 'hidden;;;;1-1-1, title;;;;2-2-2, description, subgroup;;;;3-3-3,
			--div--;LLL:EXT:lang/locallang_tca.xml:be_groups.tabs.base_rights, inc_access_lists;;;;1-1-1,
			--div--;LLL:EXT:lang/locallang_tca.xml:be_groups.tabs.mounts_and_workspaces, workspace_perms;;;;1-1-1, db_mountpoints;;;;2-2-2, file_mountpoints;;;;3-3-3, fileoper_perms,
			--div--;LLL:EXT:lang/locallang_tca.xml:be_groups.tabs.options, lockToDomain;;;;1-1-1, hide_in_lists;;;;2-2-2, TSconfig;;;;3-3-3,
			--div--;LLL:EXT:lang/locallang_tca.xml:be_groups.tabs.extended'
		),
		'1' => array('showitem' => 'hidden;;;;1-1-1, title;;;;2-2-2, description, subgroup;;;;3-3-3,
			--div--;LLL:EXT:lang/locallang_tca.xml:be_groups.tabs.base_rights, inc_access_lists;;;;1-1-1, groupMods, tables_select, tables_modify, pagetypes_select, non_exclude_fields, explicit_allowdeny , allowed_languages;;;;2-2-2, custom_options;;;;3-3-3,
			--div--;LLL:EXT:lang/locallang_tca.xml:be_groups.tabs.mounts_and_workspaces, workspace_perms;;;;1-1-1, db_mountpoints;;;;2-2-2, file_mountpoints;;;;3-3-3, fileoper_perms,
			--div--;LLL:EXT:lang/locallang_tca.xml:be_groups.tabs.options, lockToDomain;;;;1-1-1, hide_in_lists;;;;2-2-2, TSconfig;;;;3-3-3,
			--div--;LLL:EXT:lang/locallang_tca.xml:be_groups.tabs.extended'
		)
	)
);


/**
 * System filemounts - Defines filepaths on the server which can be mounted for users so they can upload and manage files online by eg. the Filelist module
 */
$TCA['sys_filemounts'] = array(
	'ctrl' => $TCA['sys_filemounts']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'title,hidden,path,base'
	),
	'columns' => array(
		'title' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:sys_filemounts.title',
			'config' => array(
				'type' => 'input',
				'size' => '20',
				'max' => '30',
				'eval' => 'required,trim'
			)
		),
		'path' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:sys_filemounts.path',
			'config' => array(
				'type' => 'input',
				'size' => '40',
				'max' => '120',
				'eval' => 'required,trim',
				'softref' => 'substitute'
			)
		),
		'hidden' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.disable',
			'config' => array(
				'type' => 'check'
			)
		),
		'base' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xml:sys_filemounts.base',
			'config' => array(
				'type' => 'radio',
				'items' => array(
					array('LLL:EXT:lang/locallang_tca.xml:sys_filemounts.base_absolute', 0),
					array('LLL:EXT:lang/locallang_tca.xml:sys_filemounts.base_relative', 1)
				),
				'default' => 0
			)
		)
	),
	'types' => array(
		'0' => array('showitem' => 'hidden;;;;1-1-1,title;;;;3-3-3,path,base')
	)
);


/**
 * System languages - Defines possible languages used for translation of records in the system
 */
$TCA['sys_language'] = array(
	'ctrl' => $TCA['sys_language']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'hidden,title'
	),
	'columns' => array(
		'title' => array(
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
			'config' => array(
				'type' => 'input',
				'size' => '35',
				'max' => '80',
				'eval' => 'trim,required'
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
		'static_lang_isocode' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_tca.php:sys_language.isocode',
			'displayCond' => 'EXT:static_info_tables:LOADED:true',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0),
				),
				'foreign_table' => 'static_languages',
				'foreign_table_where' => 'AND static_languages.pid=0 ORDER BY static_languages.lg_name_en',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'flag' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.php:sys_language.flag',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0, ''),
				),
				'selicon_cols' => 16,
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			)
		)
	),
	'types' => array(
		'1' => array('showitem' => 'hidden;;;;1-1-1,title;;;;2-2-2,static_lang_isocode,flag')
	)
);


/**
 * System News (displayed on Login screen)
 */
$TCA['sys_news'] = array(
	'ctrl' => $TCA['sys_news']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'hidden,title,content,starttime,endtime'
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
		'starttime' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.starttime',
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
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.endtime',
			'config' => array(
				'type' => 'input',
				'size' => '13',
				'max' => '20',
				'eval' => 'datetime',
				'default' => '0'
			)
		),
		'title' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.title',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'max' => '255',
				'eval' => 'required'
			)
		),
		'content' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.text',
			'config' => array(
				'type' => 'text',
				'cols' => '48',
				'rows' => '5',
				'wizards' => array(
					'_PADDING' => 4,
					'_VALIGN' => 'middle',
					'RTE' => array(
						'notNewRecords' => 1,
						'RTEonly' => 1,
						'type' => 'script',
						'title' => 'LLL:EXT:cms/locallang_ttc.php:bodytext.W.RTE',
						'icon' => 'wizard_rte2.gif',
						'script' => 'wizard_rte.php'
					),
				),
			),
		),
	),
	'types' => array(
		'1' => array('showitem' => '
			hidden, title, content;;9;richtext:rte_transform[flag=rte_enabled|mode=ts_css];3-3-3,
			--div--;LLL:EXT:lang/locallang_tca.xml:sys_news.tabs.access, starttime, endtime'
		)
	)
);

?>
<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2005 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Contains the dynamic configuation of the fields in the core tables of TYPO3: be_users, be_groups, sys_filemounts and sys_workspace
 *
 * $Id$
 * Revised for TYPO3 3.6 July/2003 by Kasper Skaarhoj
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @see tables.php, tables.sql
 */






/**
 * Backend users - Those who login into the TYPO3 administration backend
 */
$TCA['be_users'] = Array (
	'ctrl' => $TCA['be_users']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'username,usergroup,db_mountpoints,file_mountpoints,admin,options,fileoper_perms,userMods,lockToDomain,realName,email,disable,starttime,endtime'
	),
	'columns' => Array (
		'username' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_users.username',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'max' => '50',
				'eval' => 'nospace,lower,unique,required'
			)
		),
		'password' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_users.password',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'max' => '40',
				'eval' => 'required,md5,password'
			)
		),
		'usergroup' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_users.usergroup',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'be_groups',
				'foreign_table_where' => 'ORDER BY be_groups.title',
				'size' => '5',
				'maxitems' => '20',
#				'renderMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['accessListRenderMode'],
				'iconsInOptionTags' => 1,
				'wizards' => Array(
					'_PADDING' => 1,
					'_VERTICAL' => 1,
					'edit' => Array(
						'type' => 'popup',
						'title' => 'LLL:EXT:lang/locallang_tca.xml:be_users.usergroup_edit_title',
						'script' => 'wizard_edit.php',
						'popup_onlyOpenIfSelected' => 1,
						'icon' => 'edit2.gif',
						'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
					),
					'add' => Array(
						'type' => 'script',
						'title' => 'LLL:EXT:lang/locallang_tca.xml:be_users.usergroup_add_title',
						'icon' => 'add.gif',
						'params' => Array(
							'table'=>'be_groups',
							'pid' => '0',
							'setValue' => 'prepend'
						),
						'script' => 'wizard_add.php',
					),
					'list' => Array(
						'type' => 'script',
						'title' => 'LLL:EXT:lang/locallang_tca.xml:be_users.usergroup_list_title',
						'icon' => 'list.gif',
						'params' => Array(
							'table'=>'be_groups',
							'pid' => '0',
						),
						'script' => 'wizard_list.php',
					)
				)
			)
		),
		'lockToDomain' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:lockToDomain',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '50',
				'checkbox' => '',
				'softref' => 'substitute'
			)
		),
		'db_mountpoints' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_users.db_mountpoints',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
					'allowed' => 'pages',
				'size' => '3',
				'maxitems' => '10',
				'autoSizeMax' => 10,
				'show_thumbs' => '1'
			)
		),
		'file_mountpoints' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_users.file_mountpoints',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'sys_filemounts',
				'foreign_table_where' => ' AND sys_filemounts.pid=0 ORDER BY sys_filemounts.title',
				'size' => '3',
				'maxitems' => '10',
				'autoSizeMax' => 10,
				'renderMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['accessListRenderMode'],
				'iconsInOptionTags' => 1,
				'wizards' => Array(
					'_PADDING' => 1,
					'_VERTICAL' => 1,
					'edit' => Array(
						'type' => 'popup',
						'title' => 'Edit filemount',
						'title' => 'LLL:EXT:lang/locallang_tca.xml:file_mountpoints_edit_title',
						'script' => 'wizard_edit.php',
						'icon' => 'edit2.gif',
						'popup_onlyOpenIfSelected' => 1,
						'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
					),
					'add' => Array(
						'type' => 'script',
						'title' => 'LLL:EXT:lang/locallang_tca.xml:file_mountpoints_add_title',
						'icon' => 'add.gif',
						'params' => Array(
							'table'=>'sys_filemounts',
							'pid' => '0',
							'setValue' => 'prepend'
						),
						'script' => 'wizard_add.php',
					),
					'list' => Array(
						'type' => 'script',
						'title' => 'LLL:EXT:lang/locallang_tca.xml:file_mountpoints_list_title',
						'icon' => 'list.gif',
						'params' => Array(
							'table'=>'sys_filemounts',
							'pid' => '0',
						),
						'script' => 'wizard_list.php',
					)
				)
			)
		),
		'email' => Array (
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.email',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '80',
				'softref' => 'email[subst]'
			)
		),
		'realName' => Array (
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.name',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '80'
			)
		),
		'disable' => Array (
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.disable',
			'config' => Array (
				'type' => 'check'
			)
		),
		'disableIPlock' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_users.disableIPlock',
			'config' => Array (
				'type' => 'check'
			)
		),
		'admin' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_users.admin',
			'config' => Array (
				'type' => 'check',
				'default' => '0'
			)
		),
		'options' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_users.options',
			'config' => Array (
				'type' => 'check',
				'items' => Array (
					Array('LLL:EXT:lang/locallang_tca.xml:be_users.options_db_mounts', 0),
					Array('LLL:EXT:lang/locallang_tca.xml:be_users.options_file_mounts', 0)
				),
				'default' => '3'
			)
		),
		'fileoper_perms' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_users.fileoper_perms',
			'config' => Array (
				'type' => 'check',
				'items' => Array (
					Array('LLL:EXT:lang/locallang_tca.xml:be_users.fileoper_perms_general', 0),
					Array('LLL:EXT:lang/locallang_tca.xml:be_users.fileoper_perms_unzip', 0),
					Array('LLL:EXT:lang/locallang_tca.xml:be_users.fileoper_perms_diroper_perms', 0),
					Array('LLL:EXT:lang/locallang_tca.xml:be_users.fileoper_perms_diroper_perms_copy', 0),
					Array('LLL:EXT:lang/locallang_tca.xml:be_users.fileoper_perms_diroper_perms_delete', 0),
				),
				'default' => '7'
			)
		),
		'workspace_perms' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:workspace_perms',
			'config' => Array (
				'type' => 'check',
				'items' => Array (
					Array('LLL:EXT:lang/locallang_tca.xml:workspace_perms_live', 0),
					Array('LLL:EXT:lang/locallang_tca.xml:workspace_perms_draft', 0),
					Array('LLL:EXT:lang/locallang_tca.xml:workspace_perms_custom', 0),
				),
				'default' => 3
			)
		),
		'starttime' => Array (
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.starttime',
			'config' => Array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'default' => '0',
				'checkbox' => '0'
			)
		),
		'endtime' => Array (
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.endtime',
			'config' => Array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'checkbox' => '0',
				'default' => '0',
				'range' => Array (
					'upper' => mktime(0,0,0,12,31,2020),
				)
			)
		),
		'lang' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_users.lang',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('English', ''),
					Array('Albanian', 'sq'),
					Array('Arabic', 'ar'),
					Array('Basque', 'eu'),
					Array('Bosnian', 'ba'),
					Array('Brazilian Portuguese', 'br'),
					Array('Bulgarian', 'bg'),
					Array('Catalan', 'ca'),
					Array('Chinese (Simpl.)', 'ch'),
					Array('Chinese (Trad.)', 'hk'),
					Array('Croatian', 'hr'),
					Array('Czech', 'cz'),
					Array('Danish', 'dk'),
					Array('Dutch', 'nl'),
					Array('Esperanto', 'eo'),
					Array('Estonian', 'et'),
					Array('Faroese', 'fo'),
					Array('Finnish', 'fi'),
					Array('French', 'fr'),
					Array('Galician', 'ga'),
					Array('Georgian', 'ge'),
					Array('German', 'de'),
					Array('Greek', 'gr'),
					Array('Greenlandic', 'gl'),
					Array('Hebrew', 'he'),
					Array('Hindi', 'hi'),
					Array('Hungarian', 'hu'),
					Array('Icelandic', 'is'),
					Array('Italian', 'it'),
					Array('Japanese', 'jp'),
					Array('Korean', 'kr'),
					Array('Latvian', 'lv'),
					Array('Lithuanian', 'lt'),
					Array('Malay', 'my'),
					Array('Norwegian', 'no'),
					Array('Persian', 'fa'),
					Array('Polish', 'pl'),
					Array('Portuguese', 'pt'),
					Array('Romanian', 'ro'),
					Array('Russian', 'ru'),
					Array('Serbian', 'sr'),
					Array('Slovak', 'sk'),
					Array('Slovenian', 'si'),
					Array('Spanish', 'es'),
					Array('Swedish', 'se'),
					Array('Thai', 'th'),
					Array('Turkish', 'tr'),
					Array('Ukrainian', 'ua'),
					Array('Vietnamese', 'vn'),
				)
			)
		),
		'userMods' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:userMods',
			'config' => Array (
				'type' => 'select',
				'special' => 'modListUser',
				'size' => '5',
				'autoSizeMax' => 50,
				'maxitems' => '100',
				'renderMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['accessListRenderMode'],
				'iconsInOptionTags' => 1,
			)
		),
		'allowed_languages' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:allowed_languages',
			'config' => Array (
				'type' => 'select',
				'special' => 'languages',
				'maxitems' => '1000',
				'renderMode' => 'checkbox',
			)
		),
		'TSconfig' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:TSconfig',
			'config' => Array (
				'type' => 'text',
				'cols' => '40',
				'rows' => '5',
				'wizards' => Array(
					'_PADDING' => 4,
					'0' => Array(
						'type' => t3lib_extMgm::isLoaded('tsconfig_help')?'popup':'',
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
		'createdByAction' => Array('config'=>array('type'=>'passthrough'))
	),
	'types' => Array (
		'0' => Array('showitem' => 'username;;;;2-2-2, password, usergroup, lockToDomain, disableIPlock, admin;;;;5-5-5, realName;;;;3-3-3, email, lang, userMods;;;;4-4-4, allowed_languages, workspace_perms, options, db_mountpoints, file_mountpoints, fileoper_perms, --div--, TSconfig;;;;5-5-5'),
		'1' => Array('showitem' => 'username;;;;2-2-2, password, usergroup, disableIPlock, admin;;;;5-5-5, realName;;;;3-3-3, email, lang, options;;;;4-4-4, allowed_languages, db_mountpoints, file_mountpoints, fileoper_perms, --div--, TSconfig;;;;5-5-5')
	),
	'palettes' => Array (
		'1' => Array('showitem' => 'disable, starttime, endtime')
	)
);



/**
 * Backend usergroups - Much permission criterias are based on membership of backend groups.
 */
$TCA['be_groups'] = Array (
	'ctrl' => $TCA['be_groups']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'title,db_mountpoints,file_mountpoints,inc_access_lists,tables_select,tables_modify,pagetypes_select,non_exclude_fields,groupMods,lockToDomain,description'
	),
	'columns' => Array (
		'title' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_groups.title',
			'config' => Array (
				'type' => 'input',
				'size' => '25',
				'max' => '50',
				'eval' => 'trim,required'
			)
		),
		'db_mountpoints' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:db_mountpoints',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
					'allowed' => 'pages',
				'size' => '3',
				'maxitems' => 20,
				'autoSizeMax' => 10,
				'show_thumbs' => '1'
			)
		),
		'file_mountpoints' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:file_mountpoints',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'sys_filemounts',
				'foreign_table_where' => ' AND sys_filemounts.pid=0 ORDER BY sys_filemounts.title',
				'size' => '3',
				'maxitems' => 20,
				'autoSizeMax' => 10,
				'renderMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['accessListRenderMode'],
				'iconsInOptionTags' => 1,
				'wizards' => Array(
					'_PADDING' => 1,
					'_VERTICAL' => 1,
					'edit' => Array(
						'type' => 'popup',
						'title' => 'LLL:EXT:lang/locallang_tca.xml:file_mountpoints_edit_title',
						'script' => 'wizard_edit.php',
						'popup_onlyOpenIfSelected' => 1,
						'icon' => 'edit2.gif',
						'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
					),
					'add' => Array(
						'type' => 'script',
						'title' => 'LLL:EXT:lang/locallang_tca.xml:file_mountpoints_add_title',
						'icon' => 'add.gif',
						'params' => Array(
							'table'=>'sys_filemounts',
							'pid' => '0',
							'setValue' => 'prepend'
						),
						'script' => 'wizard_add.php',
					),
					'list' => Array(
						'type' => 'script',
						'title' => 'LLL:EXT:lang/locallang_tca.xml:file_mountpoints_list_title',
						'icon' => 'list.gif',
						'params' => Array(
							'table'=>'sys_filemounts',
							'pid' => '0',
						),
						'script' => 'wizard_list.php',
					)
				)
			)
		),
		'workspace_perms' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:workspace_perms',
			'config' => Array (
				'type' => 'check',
				'items' => Array (
					Array('LLL:EXT:lang/locallang_tca.xml:workspace_perms_live', 0),
					Array('LLL:EXT:lang/locallang_tca.xml:workspace_perms_draft', 0),
					Array('LLL:EXT:lang/locallang_tca.xml:workspace_perms_custom', 0),
				),
				'default' => 0
			)
		),
		'pagetypes_select' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_groups.pagetypes_select',
			'config' => Array (
				'type' => 'select',
				'special' => 'pagetypes',
				'size' => '5',
				'autoSizeMax' => 50,
				'maxitems' => 20,
				'renderMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['accessListRenderMode'],
				'iconsInOptionTags' => 1,
			)
		),
		'tables_modify' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_groups.tables_modify',
			'config' => Array (
				'type' => 'select',
				'special' => 'tables',
				'size' => '5',
				'autoSizeMax' => 50,
				'maxitems' => 100,
				'renderMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['accessListRenderMode'],
				'iconsInOptionTags' => 1,
			)
		),
		'tables_select' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_groups.tables_select',
			'config' => Array (
				'type' => 'select',
				'special' => 'tables',
				'size' => '5',
				'autoSizeMax' => 50,
				'maxitems' => 100,
				'renderMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['accessListRenderMode'],
				'iconsInOptionTags' => 1,
			)
		),
		'non_exclude_fields' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_groups.non_exclude_fields',
			'config' => Array (
				'type' => 'select',
				'special' => 'exclude',
				'size' => '25',
				'maxitems' => 1000,
				'autoSizeMax' => 50,
				'renderMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['accessListRenderMode'],
			)
		),
		'explicit_allowdeny' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_groups.explicit_allowdeny',
			'config' => Array (
				'type' => 'select',
				'special' => 'explicitValues',
				'maxitems' => 1000,
				'renderMode' => 'checkbox',
			)
		),
		'allowed_languages' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:allowed_languages',
			'config' => Array (
				'type' => 'select',
				'special' => 'languages',
				'maxitems' => 1000,
				'renderMode' => 'checkbox',
			)
		),
		'custom_options' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_groups.custom_options',
			'config' => Array (
				'type' => 'select',
				'special' => 'custom',
				'maxitems' => 1000,
				'renderMode' => 'checkbox',
			)
		),
		'hidden' => Array (
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.disable',
			'config' => Array (
				'type' => 'check',
				'default' => '0'
			)
		),
		'lockToDomain' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:lockToDomain',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '50',
				'checkbox' => '',
				'softref' => 'substitute'
			)
		),
		'groupMods' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:userMods',
			'config' => Array (
				'type' => 'select',
				'special' => 'modListGroup',
				'size' => '5',
				'autoSizeMax' => 50,
				'maxitems' => 100,
				'renderMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['accessListRenderMode'],
				'iconsInOptionTags' => 1,
			)
		),
		'inc_access_lists' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_groups.inc_access_lists',
			'config' => Array (
				'type' => 'check',
				'default' => '0'
			)
		),
		'description' => Array (
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.description',
			'config' => Array (
				'type' => 'text',
				'rows' => 5,
				'cols' => 30
			)
		),
		'TSconfig' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:TSconfig',
			'config' => Array (
				'type' => 'text',
				'cols' => '40',
				'rows' => '5',
				'wizards' => Array(
					'_PADDING' => 4,
					'0' => Array(
						'type' => t3lib_extMgm::isLoaded('tsconfig_help')?'popup':'',
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
		'hide_in_lists' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_groups.hide_in_lists',
			'config' => Array (
				'type' => 'check',
				'default' => 0
			)
		),
		'subgroup' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:be_groups.subgroup',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'be_groups',
				'foreign_table_where' => 'AND NOT(be_groups.uid = ###THIS_UID###) AND be_groups.hidden=0 ORDER BY be_groups.title',
				'size' => '5',
				'autoSizeMax' => 50,
				'maxitems' => 20,
				'renderMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['accessListRenderMode'],
				'iconsInOptionTags' => 1,
			)
		)
	),
	'types' => Array (
		'0' => Array('showitem' => 'hidden;;;;1-1-1,title;;;;2-2-2, lockToDomain, --div--, inc_access_lists;;;;3-3-3, db_mountpoints;;;;4-4-4,file_mountpoints,workspace_perms,hide_in_lists,subgroup,description, --div--, TSconfig;;;;5-5-5'),
		'1' => Array('showitem' => 'hidden;;;;1-1-1,title;;;;2-2-2, lockToDomain, --div--, inc_access_lists;;;;3-3-3, groupMods, tables_select, tables_modify, pagetypes_select, non_exclude_fields, explicit_allowdeny, allowed_languages, custom_options, --div--, db_mountpoints;;;;4-4-4,file_mountpoints,workspace_perms,hide_in_lists,subgroup,description, --div--, TSconfig;;;;5-5-5')
	)
);



/**
 * System filemounts - Defines filepaths on the server which can be mounted for users so they can upload and manage files online by eg. the Filelist module
 */
$TCA['sys_filemounts'] = Array (
	'ctrl' => $TCA['sys_filemounts']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'title,hidden,path,base'
	),
	'columns' => Array (
		'title' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:sys_filemounts.title',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'max' => '30',
				'eval' => 'required,trim'
			)
		),
		'path' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:sys_filemounts.path',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '120',
				'eval' => 'required,trim',
				'softref' => 'substitute'
			)
		),
		'hidden' => Array (
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.disable',
			'config' => Array (
				'type' => 'check'
			)
		),
		'base' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:sys_filemounts.base',
			'config' => Array (
				'type' => 'radio',
				'items' => Array (
					Array('LLL:EXT:lang/locallang_tca.xml:sys_filemounts.base_absolute', 0),
					Array('LLL:EXT:lang/locallang_tca.xml:sys_filemounts.base_relative', 1)
				),
				'default' => 0
			)
		)
	),
	'types' => Array (
		'0' => Array('showitem' => 'hidden;;;;1-1-1,title;;;;3-3-3,path,base')
	)
);



/**
 * System workspaces - Defines the offline workspaces available to users in TYPO3.
 */
$TCA['sys_workspace'] = Array (
	'ctrl' => $TCA['sys_workspace']['ctrl'],
	'columns' => Array (
		'title' => Array (
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.title',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'max' => '30',
				'eval' => 'required,trim,unique'
			)
		),
		'description' => Array (
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.description',
			'config' => Array (
				'type' => 'text',
				'rows' => 5,
				'cols' => 30
			)
		),
		'adminusers' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:sys_workspace.adminusers',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'be_users',
				'size' => '3',
				'maxitems' => '10',
				'autoSizeMax' => 10,
				'show_thumbs' => '1'
			)
		),
		'members' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:sys_workspace.members',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'be_users,be_groups',
				'prepend_tname' => 1,
				'size' => '3',
				'maxitems' => '100',
				'autoSizeMax' => 10,
				'show_thumbs' => '1'
			)
		),
		'reviewers' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:sys_workspace.reviewers',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'be_users,be_groups',
				'prepend_tname' => 1,
				'size' => '3',
				'maxitems' => '100',
				'autoSizeMax' => 10,
				'show_thumbs' => '1'
			)
		),
		'db_mountpoints' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:db_mountpoints',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
					'allowed' => 'pages',
				'size' => '3',
				'maxitems' => '10',
				'autoSizeMax' => 10,
				'show_thumbs' => '1'
			)
		),
		'file_mountpoints' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:file_mountpoints',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'sys_filemounts',
				'foreign_table_where' => ' AND sys_filemounts.pid=0 ORDER BY sys_filemounts.title',
				'size' => '3',
				'maxitems' => '10',
				'autoSizeMax' => 10,
				'renderMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['accessListRenderMode'],
				'iconsInOptionTags' => 1,
			)
		),
		'publish_time' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:sys_workspace.publish_time',
			'config' => Array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'datetime',
				'default' => '0',
				'checkbox' => '0'
			)
		),
		'unpublish_time' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:sys_workspace.unpublish_time',
			'config' => Array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'datetime',
				'checkbox' => '0',
				'default' => '0',
				'range' => Array (
					'upper' => mktime(0,0,0,12,31,2020),
				)
			)
		),
		'freeze' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:sys_workspace.freeze',
			'config' => Array (
				'type' => 'check',
				'default' => '0'
			)
		),
		'live_edit' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:sys_workspace.live_edit',
			'config' => Array (
				'type' => 'check',
				'default' => '0'
			)
		),
		'review_stage_edit' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:sys_workspace.review_stage_edit',
			'config' => Array (
				'type' => 'check',
				'default' => '0'
			)
		),
		'disable_autocreate' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:sys_workspace.disable_autocreate',
			'config' => Array (
				'type' => 'check',
				'default' => '0'
			)
		),
		'swap_modes' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:sys_workspace.swap_modes',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('',0),
					Array('Swap-Into-Workspace on Auto-publish',1),
					Array('Disable Swap-Into-Workspace',2)
				),
			)
		),
		'vtypes' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:sys_workspace.vtypes',
			'config' => Array (
				'type' => 'check',
				'items' => Array (
					Array('Element',0),
					Array('Page',0),
					Array('Branch',0)
				),
			)
		),
		'publish_access' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:sys_workspace.publish_access',
			'config' => Array (
				'type' => 'check',
				'items' => Array (
					Array('Publish only content in publish stage',0),
					Array('Only workspace owner can publish',0),
				),
			)
		),
		'stagechg_notification' => Array (
			'label' => 'LLL:EXT:lang/locallang_tca.xml:sys_workspace.stagechg_notification',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('',0),
					Array('Notify users on next stage only',1),
					Array('Notify all users on any change',10)
				),
			)
		),
	),
	'types' => Array (
		'0' => Array('showitem' => 'title,description,--div--;Users,adminusers,members,reviewers,stagechg_notification,--div--;Mountpoints,db_mountpoints,file_mountpoints,--div--;Publishing,publish_time,unpublish_time,--div--;Other,freeze,live_edit,review_stage_edit,disable_autocreate,swap_modes,vtypes,publish_access')
	)
);



/**
 * System languages - Defines possible languages used for translation of records in the system
 */
$TCA['sys_language'] = Array (
	'ctrl' => $TCA['sys_language']['ctrl'],
	'interface' => Array (
		'showRecordFieldList' => 'hidden,title'
	),
	'columns' => Array (
		'title' => Array (
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
			'config' => Array (
				'type' => 'input',
				'size' => '35',
				'max' => '80',
				'eval' => 'trim,required'
			)
		),
		'hidden' => Array (
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.disable',
			'exclude' => 1,
			'config' => Array (
				'type' => 'check',
				'default' => '0'
			)
		),
		'static_lang_isocode' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_tca.php:sys_language.isocode',
			'displayCond' => 'EXT:static_info_tables:LOADED:true',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('',0),
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
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('',0),
				),
				'fileFolder' => 'typo3/gfx/flags/',	// Only shows if "t3lib/" is in the PATH_site...
				'fileFolder_extList' => 'png,jpg,jpeg,gif',
				'fileFolder_recursions' => 0,
				'selicon_cols' => 8,
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
	        )
		)
	),
	'types' => Array (
		'1' => Array('showitem' => 'hidden;;;;1-1-1,title;;;;2-2-2,static_lang_isocode,flag')
	)
);

?>
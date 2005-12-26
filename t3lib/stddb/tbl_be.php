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
 * Contains the dynamic configuation of the fields in the core tables of TYPO3: be_users, be_groups and sys_filemounts
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
			'label' => 'Username:',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'max' => '20',
				'eval' => 'nospace,lower,unique,required'
			)
		),
		'password' => Array (
			'label' => 'Password:',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'max' => '40',
				'eval' => 'required,md5,password'
			)
		),
		'usergroup' => Array (
			'label' => 'Group:',
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
						'title' => 'Edit usergroup',
						'script' => 'wizard_edit.php',
						'popup_onlyOpenIfSelected' => 1,
						'icon' => 'edit2.gif',
						'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
					),
					'add' => Array(
						'type' => 'script',
						'title' => 'Create new group',
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
						'title' => 'List groups',
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
			'label' => 'Lock to domain:',
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
			'label' => 'DB Mounts:',
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
			'label' => 'File Mounts:',
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
						'script' => 'wizard_edit.php',
						'icon' => 'edit2.gif',
						'popup_onlyOpenIfSelected' => 1,
						'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
					),
					'add' => Array(
						'type' => 'script',
						'title' => 'Create new filemount',
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
						'title' => 'List filemounts',
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
			'label' => 'Email:',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '80',
				'softref' => 'email[subst]'
			)
		),
		'realName' => Array (
			'label' => 'Name:',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '80'
			)
		),
		'disable' => Array (
			'label' => 'Disable:',
			'config' => Array (
				'type' => 'check'
			)
		),
		'disableIPlock' => Array (
			'label' => 'Disable IP lock for user:',
			'config' => Array (
				'type' => 'check'
			)
		),
		'admin' => Array (
			'label' => 'Admin(!):',
			'config' => Array (
				'type' => 'check',
				'default' => '0'
			)
		),
		'options' => Array (
			'label' => 'Mount from groups:',
			'config' => Array (
				'type' => 'check',
				'items' => Array (
					Array('DB Mounts', 0),
					Array('File Mounts', 0)
				),
				'default' => '3'
			)
		),
		'fileoper_perms' => Array (
			'label' => 'Fileoperation permissions:',
			'config' => Array (
				'type' => 'check',
				'items' => Array (
					Array('Files: Upload,Copy,Move,Delete,Rename,New,Edit', 0),
					Array('Files: Unzip', 0),
					Array('Directory: Move,Delete,Rename,New', 0),
					Array('Directory: Copy', 0),
					Array('Directory: Delete recursively (rm -Rf)', 0)
				),
				'default' => '7'
			)
		),
		'workspace_perms' => Array (
			'label' => 'Workspace permissions:',
			'config' => Array (
				'type' => 'check',
				'items' => Array (
					Array('Edit Live (Online)', 0),
					Array('Edit Draft (Offline)', 0),
					Array('Create new workspace projects', 0),
				),
				'default' => 3
			)
		),
		'starttime' => Array (
			'label' => 'Start:',
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
			'label' => 'Stop:',
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
			'label' => 'Default Language:',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('English', ''),
					Array('Arabic', 'ar'),
					Array('Bahasa Malaysia', 'my'),
					Array('Basque', 'eu'),
					Array('Bosnian', 'ba'),
					Array('Brazilian Portuguese', 'br'),
					Array('Bulgarian', 'bg'),
					Array('Catalan', 'ca'),
					Array('Chinese (Simpl)', 'ch'),
					Array('Chinese (Trad)', 'hk'),
					Array('Croatian', 'hr'),
					Array('Czech', 'cz'),
					Array('Danish', 'dk'),
					Array('Dutch', 'nl'),
					Array('Estonian', 'et'),
					Array('Esperanto', 'eo'),
					Array('Finnish', 'fi'),
					Array('French', 'fr'),
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
					Array('Norwegian', 'no'),
					Array('Polish', 'pl'),
					Array('Portuguese', 'pt'),
					Array('Romanian', 'ro'),
					Array('Russian', 'ru'),
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
			'label' => 'Modules:',
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
			'label' => 'Limit to languages:',
			'config' => Array (
				'type' => 'select',
				'special' => 'languages',
				'maxitems' => '1000',
				'renderMode' => 'checkbox',
			)
		),
		'TSconfig' => Array (
			'label' => 'TSconfig:',
			'config' => Array (
				'type' => 'text',
				'cols' => '40',
				'rows' => '5',
				'wizards' => Array(
					'_PADDING' => 4,
					'0' => Array(
						'type' => t3lib_extMgm::isLoaded('tsconfig_help')?'popup':'',
						'title' => 'TSconfig QuickReference',
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
		'1' => Array('showitem' => 'username;;;;2-2-2, password, usergroup, disableIPlock, admin;;;;5-5-5, realName;;;;3-3-3, email, lang, options;;;;4-4-4, db_mountpoints, file_mountpoints, fileoper_perms, --div--, TSconfig;;;;5-5-5')
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
			'label' => 'Grouptitle:',
			'config' => Array (
				'type' => 'input',
				'size' => '25',
				'max' => '20',
				'eval' => 'trim,required'
			)
		),
		'db_mountpoints' => Array (
			'label' => 'DB Mounts:',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
					'allowed' => 'pages',
				'size' => '3',
				'maxitems' => '20',
				'autoSizeMax' => 10,
				'show_thumbs' => '1'
			)
		),
		'file_mountpoints' => Array (
			'label' => 'File Mounts:',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'sys_filemounts',
				'foreign_table_where' => ' AND sys_filemounts.pid=0 ORDER BY sys_filemounts.title',
				'size' => '3',
				'maxitems' => '20',
				'autoSizeMax' => 10,
				'renderMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['accessListRenderMode'],
				'iconsInOptionTags' => 1,
				'wizards' => Array(
					'_PADDING' => 1,
					'_VERTICAL' => 1,
					'edit' => Array(
						'type' => 'popup',
						'title' => 'Edit filemount',
						'script' => 'wizard_edit.php',
						'popup_onlyOpenIfSelected' => 1,
						'icon' => 'edit2.gif',
						'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
					),
					'add' => Array(
						'type' => 'script',
						'title' => 'Create new filemount',
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
						'title' => 'List filemounts',
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
			'label' => 'Workspace permissions:',
			'config' => Array (
				'type' => 'check',
				'items' => Array (
					Array('Edit Live (Online)', 0),
					Array('Edit Draft (Offline)', 0),
					Array('Create new workspace projects', 0),
				),
				'default' => 0
			)
		),
		'pagetypes_select' => Array (
			'label' => 'Page types:',
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
			'label' => 'Tables (modify):',
			'config' => Array (
				'type' => 'select',
				'special' => 'tables',
				'size' => '5',
				'autoSizeMax' => 50,
				'maxitems' => 20,
				'renderMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['accessListRenderMode'],
				'iconsInOptionTags' => 1,
			)
		),
		'tables_select' => Array (
			'label' => 'Tables (listing):',
			'config' => Array (
				'type' => 'select',
				'special' => 'tables',
				'size' => '5',
				'autoSizeMax' => 50,
				'maxitems' => 20,
				'renderMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['accessListRenderMode'],
				'iconsInOptionTags' => 1,
			)
		),
		'non_exclude_fields' => Array (
			'label' => 'Allowed excludefields:',
			'config' => Array (
				'type' => 'select',
				'special' => 'exclude',
				'size' => '25',
				'maxitems' => '300',
				'autoSizeMax' => 50,
				'renderMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['accessListRenderMode'],
			)
		),
		'explicit_allowdeny' => Array (
			'label' => 'Explicitly allow/deny field values:',
			'config' => Array (
				'type' => 'select',
				'special' => 'explicitValues',
				'maxitems' => '1000',
				'renderMode' => 'checkbox',
			)
		),
		'allowed_languages' => Array (
			'label' => 'Limit to languages:',
			'config' => Array (
				'type' => 'select',
				'special' => 'languages',
				'maxitems' => '1000',
				'renderMode' => 'checkbox',
			)
		),
		'custom_options' => Array (
			'label' => 'Custom module options:',
			'config' => Array (
				'type' => 'select',
				'special' => 'custom',
				'maxitems' => '1000',
				'renderMode' => 'checkbox',
			)
		),
		'hidden' => Array (
			'label' => 'Disable:',
			'config' => Array (
				'type' => 'check',
				'default' => '0'
			)
		),
		'lockToDomain' => Array (
			'label' => 'Lock to domain:',
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
			'label' => 'Modules:',
			'config' => Array (
				'type' => 'select',
				'special' => 'modListGroup',
				'size' => '5',
				'autoSizeMax' => 50,
				'maxitems' => '100',
				'renderMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['accessListRenderMode'],
				'iconsInOptionTags' => 1,
			)
		),
		'inc_access_lists' => Array (
			'label' => 'Include Access Lists:',
			'config' => Array (
				'type' => 'check',
				'default' => '0'
			)
		),
		'description' => Array (
			'label' => 'Description:',
			'config' => Array (
				'type' => 'text',
				'rows' => 5,
				'cols' => 30
			)
		),
		'TSconfig' => Array (
			'label' => 'TSconfig:',
			'config' => Array (
				'type' => 'text',
				'cols' => '40',
				'rows' => '5',
				'wizards' => Array(
					'_PADDING' => 4,
					'0' => Array(
						'type' => t3lib_extMgm::isLoaded('tsconfig_help')?'popup':'',
						'title' => 'TSconfig QuickReference',
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
			'label' => 'Hide in lists:',
			'config' => Array (
				'type' => 'check',
				'default' => 0
			)
		),
		'subgroup' => Array (
			'label' => 'Sub Groups:',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'be_groups',
				'foreign_table_where' => 'AND be_groups.uid != ###THIS_UID### AND NOT be_groups.hidden ORDER BY be_groups.title',
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
			'label' => 'LABEL:',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'max' => '30',
				'eval' => 'required,trim'
			)
		),
		'path' => Array (
			'label' => 'PATH:',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'max' => '120',
				'eval' => 'required,trim',
				'softref' => 'substitute'
			)
		),
		'hidden' => Array (
			'label' => 'Disable:',
			'config' => Array (
				'type' => 'check'
			)
		),
		'base' => Array (
			'label' => 'BASE',
			'config' => Array (
				'type' => 'radio',
				'items' => Array (
					Array('absolute (root) / ', 0),
					Array('relative ../fileadmin/', 1)
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
			'label' => 'Title:',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'max' => '30',
				'eval' => 'required,trim'
			)
		),
		'description' => Array (
			'label' => 'Description:',
			'config' => Array (
				'type' => 'text',
				'rows' => 5,
				'cols' => 30
			)
		),
		'adminusers' => Array (
			'label' => 'Owners:',
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
			'label' => 'Members:',
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
			'label' => 'Reviewers:',
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
			'label' => 'DB Mounts:',
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
			'label' => 'File Mounts:',
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
			'label' => 'Publish:',
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
			'label' => 'Un-publish:',
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
			'label' => 'Freeze Editing',
			'config' => Array (
				'type' => 'check',
				'default' => '0'
			)
		),
		'live_edit' => Array (
			'label' => 'Allow "live" editing of records from tables without versioning',
			'config' => Array (
				'type' => 'check',
				'default' => '0'
			)
		),
		'disable_autocreate' => Array (
			'label' => 'Disable auto-versioning when editing',
			'config' => Array (
				'type' => 'check',
				'default' => '0'
			)
		),
		'swap_modes' => Array (
			'label' => 'Swap modes',
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
			'label' => 'Disable Versioning Types',
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
			'label' => 'Publish access:',
			'config' => Array (
				'type' => 'check',
				'items' => Array (
					Array('Publish only content in publish stage',0),
					Array('Only workspace owner can publish',0),
				),
			)
		),
		'stagechg_notification' => Array (
			'label' => 'Stage change notification by email:',
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
		'0' => Array('showitem' => 'title,description,--div--;Users,adminusers,members,reviewers,stagechg_notification,--div--;Mountpoints,db_mountpoints,file_mountpoints,--div--;Publishing,publish_time,unpublish_time,--div--;Other,freeze,live_edit,disable_autocreate,swap_modes,vtypes,publish_access')
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
				'fileFolder' => 't3lib/gfx/flags/',	// Only shows if "t3lib/" is in the PATH_site...
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
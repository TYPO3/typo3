<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2003 Kasper Skaarhoj (kasper@typo3.com)
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
 * @author	Kasper Skaarhoj <kasper@typo3.com>
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
				'checkbox' => ''
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
				'type' => 'group',
				'internal_type' => 'db',
					'allowed' => 'sys_filemounts',
				'size' => '3',
				'maxitems' => '10',
				'autoSizeMax' => 10,
				'show_thumbs' => '1',
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
				'max' => '80'
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
					'lower' => mktime(0,0,0,date('m')-1,date('d'),date('Y'))
				)
			)
		),
		'lang' => Array (
			'label' => 'Default Language:',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('English', ''),
					Array('Danish', 'dk'),
					Array('German', 'de'),
					Array('Norwegian', 'no'),
					Array('Italian', 'it'),
					Array('French', 'fr'),
					Array('Spanish', 'es'),
					Array('Dutch', 'nl'),
					Array('Czech', 'cz'),
					Array('Polish', 'pl'),
					Array('Slovenian', 'si'),
					Array('Finnish', 'fi'),
					Array('Turkish', 'tr'),
					Array('Swedish', 'se'),
					Array('Portuguese', 'pt'),
					Array('Russian', 'ru'),
					Array('Romanian', 'ro'),
					Array('Chinese (Simpl)', 'ch'),
					Array('Slovak', 'sk'),
					Array('Lithuanian', 'lt'),
					Array('Icelandic', 'is'),
					Array('Croatian', 'hr'),
					Array('Hungarian', 'hu'),
					Array('Greenlandic', 'gl'),
					Array('Thai', 'th'),
					Array('Greek', 'gr'),
					Array('Chinese (Trad)', 'hk'),
					Array('Basque', 'eu'),
					Array('Bulgarian', 'bg'),
					Array('Brazilian Portuguese', 'br'),
					Array('Estonian', 'et'),
					Array('Arabic', 'ar'),
					Array('Hebrew', 'he'),
					Array('Ukrainian', 'ua'),
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
				'maxitems' => '15'
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
				)
			)
		),
		'createdByAction' => Array('config'=>array('type'=>'passthrough'))
	),
	'types' => Array (
		'0' => Array('showitem' => 'username;;;;2-2-2, password, usergroup, lockToDomain, admin;;;;5-5-5, realName;;;;3-3-3, email, lang, userMods;;;;4-4-4, options, db_mountpoints, file_mountpoints, fileoper_perms, --div--, TSconfig;;;;5-5-5'),
		'1' => Array('showitem' => 'username;;;;2-2-2, password, usergroup, admin;;;;5-5-5, realName;;;;3-3-3, email, lang, options;;;;4-4-4, db_mountpoints, file_mountpoints, fileoper_perms, --div--, TSconfig;;;;5-5-5')
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
				'maxitems' => '10',
				'autoSizeMax' => 10,
				'show_thumbs' => '1'
			)
		),
		'file_mountpoints' => Array (
			'label' => 'File Mounts:',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
					'allowed' => 'sys_filemounts',
				'size' => '3',
				'maxitems' => '10',
				'autoSizeMax' => 10,
				'show_thumbs' => '1',
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
		'pagetypes_select' => Array (
			'label' => 'Page types:',
			'config' => Array (
				'type' => 'select',
				'special' => 'pagetypes',
				'size' => '5',
				'autoSizeMax' => 50,
				'maxitems' => '20'
			)
		),
		'tables_modify' => Array (
			'label' => 'Tables (modify):',
			'config' => Array (
				'type' => 'select',
				'special' => 'tables',
				'size' => '5',
				'autoSizeMax' => 50,
				'maxitems' => '20'
			)
		),
		'tables_select' => Array (	
			'label' => 'Tables (listing):',
			'config' => Array (
				'type' => 'select',
				'special' => 'tables',
				'size' => '5',
				'autoSizeMax' => 50,
				'maxitems' => '20'
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
				'checkbox' => ''
			)
		),
		'groupMods' => Array (	
			'label' => 'Modules:',
			'config' => Array (
				'type' => 'select',
				'special' => 'modListGroup',
				'size' => '5',
				'autoSizeMax' => 50,
				'maxitems' => '15'
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
				)
			)
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
				'foreign_table_where' => 'ORDER BY be_groups.title',
				'size' => '5',
				'autoSizeMax' => 50,
				'maxitems' => '20'
			)
		)
	),
	'types' => Array (
		'0' => Array('showitem' => 'hidden;;;;1-1-1,title;;;;2-2-2, lockToDomain, --div--, inc_access_lists;;;;3-3-3, db_mountpoints;;;;4-4-4,file_mountpoints,hide_in_lists,subgroup,description, --div--, TSconfig;;;;5-5-5'),
		'1' => Array('showitem' => 'hidden;;;;1-1-1,title;;;;2-2-2, lockToDomain, --div--, inc_access_lists;;;;3-3-3, groupMods, tables_select, tables_modify, pagetypes_select, non_exclude_fields, --div--, db_mountpoints;;;;4-4-4,file_mountpoints,hide_in_lists,subgroup,description, --div--, TSconfig;;;;5-5-5')
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
				'eval' => 'required,trim'
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
?>
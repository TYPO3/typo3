<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2009 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Contains the initialization of global TYPO3 variables among which $TCA is the most significant.
 *
 * The list in order of apperance is: $PAGES_TYPES, $ICON_TYPES, $LANG_GENERAL_LABELS, $TCA, $TBE_MODULES, $TBE_STYLES, $FILEICONS
 * These variables are first of all used in the backend but to some degree in the frontend as well. (See references)
 * See the document "Inside TYPO3" for a description of each variable in addition to the comment associated with each.
 *
 * This file is included from "typo3/init.php" (backend) and "index_ts.php" (frontend) as the first file of a three-fold inclusion session (see references):
 * 1) First this script is included (unless the constant "TYPO3_tables_script" instructs another filename to substitute it, see t3lib/config_default.php); This should initialize the variables shown above.
 * 2) Then either the "typo3conf/temp_CACHED_??????_ext_tables.php" cache file OR "stddb/load_ext_tables.php" is included in order to let extensions add/modify these variables as they desire.
 * 3) Finally if the constant "TYPO3_extTableDef_script" defines a file name from typo3conf/ it is included, also for overriding values (the old-school way before extensions came in). See config_default.php
 *
 * Configuration in this file should NOT be edited directly. If you would like to alter
 * or extend this information, please make an extension which does so.
 * Thus you preserve backwards compatibility.
 *
 *
 * $Id$
 * Revised for TYPO3 3.6 July/2003 by Kasper Skaarhoj
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @see tslib_fe::includeTCA(), typo3/init.php, t3lib/stddb/load_ext_tables.php
 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=262&cHash=4f12caa011
 */


/**
 * $PAGES_TYPES defines the various types of pages (field: doktype) the system can handle and what restrictions may apply to them.
 * Here you can set the icon and especially you can define which tables are allowed on a certain pagetype (doktype)
 * NOTE: The 'default' entry in the $PAGES_TYPES-array is the 'base' for all types, and for every type the entries simply overrides the entries in the 'default' type!
 */
$PAGES_TYPES = array(
	'254' => array(		//  Doktype 254 is a 'sysFolder' - a general purpose storage folder for whatever you like. In CMS context it's NOT a viewable page. Can contain any element.
		'type' => 'sys',
		'icon' => 'sysf.gif',
		'allowedTables' => '*'
	),
	'255' => array(		// Doktype 255 is a recycle-bin.
		'type' => 'sys',
		'icon' => 'recycler.gif',
		'allowedTables' => '*'
	),
	'default' => array(
		'type' => 'web',
		'icon' => 'pages.gif',
		'allowedTables' => 'pages',
		'onlyAllowedTables' => '0'
	)
);


/**
 * With $ICON_TYPES you can assign alternative icons to pages records based on another field than 'doktype'
 * Each key is a value from the "module" field of page records and the value is an array with a key/value pair, eg. "icon" => "modules_shop.gif"
 *
 * @see t3lib_iconWorks::getIcon(), typo3/sysext/cms/ext_tables.php
 */
$ICON_TYPES = array();


/**
 * Commonly used language labels which can be used in the $TCA array and elsewhere.
 * Obsolete - just use the values of each entry directly.
 * @todo turn into an object with magic getters and setter so we can make use of the deprecation logging
 * @deprecated since TYPO3 3.6
 */
$LANG_GENERAL_LABELS = array(
	'endtime' => 'LLL:EXT:lang/locallang_general.php:LGL.endtime',
	'hidden' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
	'starttime' => 'LLL:EXT:lang/locallang_general.php:LGL.starttime',
	'fe_group' => 'LLL:EXT:lang/locallang_general.php:LGL.fe_group',
	'hide_at_login' => 'LLL:EXT:lang/locallang_general.php:LGL.hide_at_login',
	'any_login' => 'LLL:EXT:lang/locallang_general.php:LGL.any_login',
	'usergroups' => 'LLL:EXT:lang/locallang_general.php:LGL.usergroups',
);












/**
 * $TCA:
 * This array configures TYPO3 to work with the tables from the database by assigning meta information about data types, relations etc.
 * The global variable $TCA will contain the information needed to recognize and render each table in the backend
 * See documentation 'Inside TYPO3' for the syntax and list of required tables/fields!
 *
 * The tables configured in this document (and backed up by "tbl_be.php") is the required minimum set of tables/field that any TYPO3 system MUST have. These tables are therefore a part of the TYPO3 core.
 * The SQL definitions of these tables (and some more which are not defined in $TCA) is found in the file "tables.sql"
 * Only the "pages" table is defined fully in this file - the others are only defined for the "ctrl" part and the columns are defined in detail in the associated file, "tbl_be.php"
 *
 * NOTE: The (default) icon for a table is defined 1) as a giffile named 'gfx/i/[tablename].gif' or 2) as the value of [table][ctrl][iconfile]
 * NOTE: [table][ctrl][rootLevel] goes NOT for pages. Apart from that if rootLevel is true, records can ONLY be created on rootLevel. If it's false records can ONLY be created OUTSIDE rootLevel
 */
$TCA = array();

/**
 * Table "pages":
 * The mandatory pages table. The backbone of the TYPO3 page tree structure.
 * All other records configured in $TCA must have a field, "pid", which relates the record to a page record's "uid" field.
 * Must be COMPLETELY configured in tables.php
 */
$TCA['pages'] = array(
	'ctrl' => array(
		'label' => 'title',
		'tstamp' => 'tstamp',
		'sortby' => 'sorting',
		'title' => 'LLL:EXT:lang/locallang_tca.php:pages',
		'type' => 'doktype',
		'versioningWS' => 2,
		'origUid' => 't3_origuid',
		'delete' => 'deleted',
		'crdate' => 'crdate',
		'hideAtCopy' => 1,
		'prependAtCopy' => 'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy',
		'cruser_id' => 'cruser_id',
		'editlock' => 'editlock',
		'useColumnsForDefaultValues' => 'doktype',
		'typeicon_column' => 'doktype',
		'typeicon_classes' => array(
			'1' => 'apps-pagetree-page-default',
			'1-hideinmenu' => 'apps-pagetree-page-not-in-menu',
			'1-root' => 'apps-pagetree-page-domain',
			'3' => 'apps-pagetree-page-shortcut-external',
			'3-hideinmenu' => 'apps-pagetree-page-shortcut-external-hideinmenu',
			'3-root' => 'apps-pagetree-page-domain',
			'4' => 'apps-pagetree-page-shortcut',
			'4-hideinmenu' => 'apps-pagetree-page-shortcut-hideinmenu',
			'4-root' => 'apps-pagetree-page-domain',
			'6' => 'apps-pagetree-page-backend-user',
			'6-hideinmenu' => 'apps-pagetree-page-backend-user-hideinmenu',
			'6-root' => 'apps-pagetree-page-domain',
			'7' => 'apps-pagetree-page-mountpoint',
			'7-hideinmenu' => 'apps-pagetree-page-mountpoint-hideinmenu',
			'7-root' => 'apps-pagetree-page-domain',
			'199' => 'apps-pagetree-spacer',
			'199-hideinmenu' => 'apps-pagetree-spacer-hideinmenu',
			'254' => 'apps-pagetree-folder-default',
			'254-hideinmenu' => 'apps-pagetree-folder-default-hideinmenu',
			'255' => 'apps-pagetree-dust-bin',
			'255-hideinmenu' => 'apps-pagetree-dust-bin-hideinmenu',
			'default' => 'apps-pagetree-page-default',
			
		),
		'typeicons' => array(
			'1' => 'pages.gif',
			'254' => 'sysf.gif',
			'255' => 'recycler.gif',
		)
	),
	'interface' => array(
		'showRecordFieldList' => 'doktype,title',
		'maxDBListItems' => 30,
		'maxSingleDBListItems' => 50
	),
	'columns' => array(
		'doktype' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.type',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('LLL:EXT:lang/locallang_tca.php:doktype.I.0', '1', 'i/pages.gif'),
					array('LLL:EXT:lang/locallang_tca.php:doktype.I.1', '254', 'i/sysf.gif'),
					array('LLL:EXT:lang/locallang_tca.php:doktype.I.2', '255', 'i/recycler.gif')
				),
				'default' => '1',
				'iconsInOptionTags' => 1,
				'noIconsBelowSelect' => 1,
			)
		),
		'title' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.php:title',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'max' => '255',
				'eval' => 'required'
			)
		),
		'TSconfig' => array(
			'exclude' => 1,
			'label' => 'TSconfig:',
			'config' => array(
				'type' => 'text',
				'cols' => '40',
				'rows' => '5',
				'wizards' => array(
					'_PADDING' => 4,
					'0' => array(
						'type' => t3lib_extMgm::isLoaded('tsconfig_help')?'popup':'',
						'title' => 'TSconfig QuickReference',
						'script' => 'wizard_tsconfig.php?mode=page',
						'icon' => 'wizard_tsconfig.gif',
						'JSopenParams' => 'height=500,width=780,status=0,menubar=0,scrollbars=1',
					)
				),
				'softref' => 'TSconfig'
			),
			'defaultExtras' => 'fixed-font : enable-tab',
		),
		'php_tree_stop' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_tca.php:php_tree_stop',
			'config' => array(
				'type' => 'check'
			)
		),
		'is_siteroot' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_tca.php:is_siteroot',
			'config' => array(
				'type' => 'check'
			)
		),
		'storage_pid' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_tca.php:storage_pid',
			'config' => array(
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
			)
		),
		'tx_impexp_origuid' => array('config'=>array('type'=>'passthrough')),
		't3ver_label' => array(
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.versionLabel',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'max' => '255',
			)
		),
		'editlock' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_tca.php:editlock',
			'config' => array(
				'type' => 'check'
			)
		),
	),
	'types' => array(
		'1' => array('showitem' => 'doktype, title, TSconfig;;6;nowrap, storage_pid;;7'),
		'254' => array('showitem' => 'doktype, title;LLL:EXT:lang/locallang_general.php:LGL.title, TSconfig;;6;nowrap, storage_pid;;7'),
		'255' => array('showitem' => 'doktype, title, TSconfig;;6;nowrap, storage_pid;;7')
	),
	'palettes' => array(
		'6' => array('showitem' => 'php_tree_stop, editlock'),
		'7' => array('showitem' => 'is_siteroot')
	)
);

/**
 * Table "be_users":
 * Backend Users for TYPO3.
 * This is only the 'header' part (ctrl). The full configuration is found in t3lib/stddb/tbl_be.php
 */
$TCA['be_users'] = array(
	'ctrl' => array(
		'label' => 'username',
		'tstamp' => 'tstamp',
		'title' => 'LLL:EXT:lang/locallang_tca.php:be_users',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'delete' => 'deleted',
		'adminOnly' => 1,	// Only admin users can edit
		'rootLevel' => 1,
		'default_sortby' => 'ORDER BY admin, username',
		'enablecolumns' => array(
			'disabled' => 'disable',
			'starttime' => 'starttime',
			'endtime' => 'endtime'
		),
		'type' => 'admin',
		'typeicon_column' => 'admin',
		'typeicons' => array(
			'0' => 'be_users.gif',
			'1' => 'be_users_admin.gif'
		),
		'typeicon_classes' => array(
			'0' => 'status-user-backend',
			'1' => 'status-user-admin',
			'default' => 'status-user-backend',
		), 
		'mainpalette' => '1',
		'useColumnsForDefaultValues' => 'usergroup,lockToDomain,options,db_mountpoints,file_mountpoints,fileoper_perms,userMods',
		'dividers2tabs' => true,
		'dynamicConfigFile' => 'T3LIB:tbl_be.php',
		'versioningWS_alwaysAllowLiveEdit' => TRUE
	)
);

/**
 * Table "be_groups":
 * Backend Usergroups for TYPO3.
 * This is only the 'header' part (ctrl). The full configuration is found in t3lib/stddb/tbl_be.php
 */
$TCA['be_groups'] = array(
	'ctrl' => array(
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'delete' => 'deleted',
		'default_sortby' => 'ORDER BY title',
		'prependAtCopy' => 'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy',
		'adminOnly' => 1,
		'rootLevel' => 1,
		'type' => 'inc_access_lists',
		'typeicon_column' => 'inc_access_lists',
		'typeicons' => array(
			'1' => 'be_groups_lists.gif'
		),
		'typeicon_classes' => array(
			'default' => 'status-user-group-backend',
		), 
		'enablecolumns' => array(
			'disabled' => 'hidden'
		),
		'title' => 'LLL:EXT:lang/locallang_tca.php:be_groups',
		'useColumnsForDefaultValues' => 'lockToDomain, fileoper_perms',
		'dividers2tabs' => true,
		'dynamicConfigFile' => 'T3LIB:tbl_be.php',
		'versioningWS_alwaysAllowLiveEdit' => TRUE
	)
);

/**
 * Table "sys_filemounts":
 * Defines filepaths on the server which can be mounted for users so they can upload and manage files online by eg. the Filelist module
 * This is only the 'header' part (ctrl). The full configuration is found in t3lib/stddb/tbl_be.php
 */
$TCA['sys_filemounts'] = array(
	'ctrl' => array(
		'label' => 'title',
		'tstamp' => 'tstamp',
		'sortby' => 'sorting',
		'prependAtCopy' => 'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy',
		'title' => 'LLL:EXT:lang/locallang_tca.php:sys_filemounts',
		'adminOnly' => 1,
		'rootLevel' => 1,
		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden'
		),
		'iconfile' => '_icon_ftp.gif',
		'useColumnsForDefaultValues' => 'path,base',
		'dynamicConfigFile' => 'T3LIB:tbl_be.php',
		'versioningWS_alwaysAllowLiveEdit' => TRUE
	)
);


/**
 * Table "sys_languages":
 * Defines possible languages used for translation of records in the system
 * This is only the 'header' part (ctrl). The full configuration is found in t3lib/stddb/tbl_be.php
 */
$TCA['sys_language'] = array(
	'ctrl' => array(
		'label' => 'title',
		'tstamp' => 'tstamp',
		'default_sortby' => 'ORDER BY title',
		'title' => 'LLL:EXT:lang/locallang_tca.php:sys_language',
		'adminOnly' => 1,
		'rootLevel' => 1,
		'enablecolumns' => array(
			'disabled' => 'hidden'
		),
		'typeicon_classes' => array(
			'default' => 'mimetypes-x-content-language',
		), 
		'dynamicConfigFile' => 'T3LIB:tbl_be.php',
		'versioningWS_alwaysAllowLiveEdit' => TRUE
	)
);












/**
 * $TBE_MODULES contains the structure of the backend modules as they are arranged in main- and sub-modules.
 * Every entry in this array represents a menu item on either first (key) or second level (value from list) in the left menu in the TYPO3 backend
 * For information about adding modules to TYPO3 you should consult the documentation found in "Inside TYPO3"
 */
$TBE_MODULES = array(
	'web' => 'list,info,perm,func',
	'file' => 'list',
	'user' => 'ws',
	'tools' => 'em',
	'help' => 'about,cshmanual'
);


/**
 * $TBE_STYLES configures backend styles and colors; Basically this contains all the values that can be used to create new skins for TYPO3.
 * For information about making skins to TYPO3 you should consult the documentation found in "Inside TYPO3"
 */
$TBE_STYLES = array(
	'colorschemes' => array(
		'0' => '#E4E0DB,#CBC7C3,#EDE9E5',
	),
	'borderschemes' => array(
		'0' => array('border:solid 1px black;',5)
	)
);


/**
 * Setting up $TCA_DESCR - Context Sensitive Help (CSH)
 * For information about using the CSH API in TYPO3 you should consult the documentation found in "Inside TYPO3"
 */
t3lib_extMgm::addLLrefForTCAdescr('pages','EXT:lang/locallang_csh_pages.xml');
t3lib_extMgm::addLLrefForTCAdescr('be_users','EXT:lang/locallang_csh_be_users.xml');
t3lib_extMgm::addLLrefForTCAdescr('be_groups','EXT:lang/locallang_csh_be_groups.xml');
t3lib_extMgm::addLLrefForTCAdescr('sys_filemounts','EXT:lang/locallang_csh_sysfilem.xml');
t3lib_extMgm::addLLrefForTCAdescr('sys_language','EXT:lang/locallang_csh_syslang.xml');
t3lib_extMgm::addLLrefForTCAdescr('sys_workspace','EXT:lang/locallang_csh_sysws.xml');
t3lib_extMgm::addLLrefForTCAdescr('xMOD_csh_corebe','EXT:lang/locallang_csh_corebe.xml');	// General Core
t3lib_extMgm::addLLrefForTCAdescr('_MOD_tools_em','EXT:lang/locallang_csh_em.xml');		// Extension manager
t3lib_extMgm::addLLrefForTCAdescr('_MOD_web_info','EXT:lang/locallang_csh_web_info.xml');		// Web > Info
t3lib_extMgm::addLLrefForTCAdescr('_MOD_web_func','EXT:lang/locallang_csh_web_func.xml');		// Web > Func


/**
 * $FILEICONS defines icons for the various file-formats
 */
$FILEICONS = array(
	'txt' => 'txt.gif',
	'pdf' => 'pdf.gif',
	'doc' => 'doc.gif',
	'ai' => 'ai.gif',
	'bmp' => 'bmp.gif',
	'tif' => 'tif.gif',
	'htm' => 'htm.gif',
	'html' => 'html.gif',
	'pcd' => 'pcd.gif',
	'gif' => 'gif.gif',
	'jpg' => 'jpg.gif',
	'jpeg' => 'jpg.gif',
	'mpg' => 'mpg.gif',
	'mpeg' => 'mpeg.gif',
	'exe' => 'exe.gif',
	'com' => 'exe.gif',
	'zip' => 'zip.gif',
	'tgz' => 'zip.gif',
	'gz' => 'zip.gif',
	'php3' => 'php3.gif',
	'php4' => 'php3.gif',
	'php5' => 'php3.gif',
	'php6' => 'php3.gif',
	'php' => 'php3.gif',
	'ppt' => 'ppt.gif',
	'ttf' => 'ttf.gif',
	'pcx' => 'pcx.gif',
	'png' => 'png.gif',
	'tga' => 'tga.gif',
	'class' => 'java.gif',
	'sxc' => 'sxc.gif',
	'sxw' => 'sxw.gif',
	'xls' => 'xls.gif',
	'swf' => 'swf.gif',
	'swa' => 'flash.gif',
	'dcr' => 'flash.gif',
	'wav' => 'wav.gif',
	'mp3' => 'mp3.gif',
	'avi' => 'avi.gif',
	'au' => 'au.gif',
	'mov' => 'mov.gif',
	'3ds' => '3ds.gif',
	'csv' => 'csv.gif',
	'ico' => 'ico.gif',
	'max' => 'max.gif',
	'ps' => 'ps.gif',
	'tmpl' => 'tmpl.gif',
	'xls' => 'xls.gif',
	'fh3' => 'fh3.gif',
	'inc' => 'inc.gif',
	'mid' => 'mid.gif',
	'psd' => 'psd.gif',
	'xml' => 'xml.gif',
	'rtf' => 'rtf.gif',
	't3x' => 't3x.gif',
	't3d' => 't3d.gif',
	'cdr' => 'cdr.gif',
	'dtd' => 'dtd.gif',
	'sgml' => 'sgml.gif',
	'ani' => 'ani.gif',
	'css' => 'css.gif',
	'eps' => 'eps.gif',
	'js' => 'js.gif',
	'wrl' => 'wrl.gif',
	'default' => 'default.gif'
);

/**
 * backend sprite icon-names
 */
$GLOBALS['TBE_STYLES']['spriteIconApi']['coreSpriteImageName'] = array(
	'actions-move-down',
	'actions-edit-add',
	'actions-move-to-bottom',
	'actions-document-move',
	'actions-system-list-open',
	'actions-document-export-t3d',
	'actions-version-swap-workspace',
	'actions-move-left',
	'actions-view-go-forward',
	'actions-view-paging-next',
	'actions-system-cache-clear-impact-low',
	'actions-document-close',
	'actions-system-shortcut-new',
	'actions-version-page-open',
	'actions-edit-copy',
	'actions-insert-record',
	'actions-selection-delete',
	'actions-system-extension-uninstall',
	'actions-page-open',
	'actions-view-go-back',
	'actions-system-tree-search-open',
	'actions-document-save-view',
	'actions-system-extension-update',
	'actions-system-backend-user-emulate',
	'actions-system-cache-clear-impact-high',
	'actions-view-paging-first-disabled',
	'actions-document-save-new',
	'actions-edit-unhide',
	'actions-document-new',
	'actions-page-move',
	'actions-document-save-close',
	'actions-document-import-t3d',
	'actions-edit-localize-status-high',
	'actions-move-move',
	'actions-edit-upload',
	'actions-document-info',
	'actions-system-cache-clear',
	'actions-edit-rename',
	'actions-edit-cut',
	'actions-view-paging-previous',
	'actions-version-document-remove',
	'actions-system-help-open',
	'actions-view-paging-last-disabled',
	'actions-move-up',
	'actions-insert-reference',
	'actions-edit-undelete-edit',
	'actions-document-duplicates-select',
	'actions-system-extension-install',
	'actions-document-save',
	'actions-edit-restore',
	'actions-system-backend-user-switch',
	'actions-document-paste-after',
	'actions-system-typoscript-documentation',
	'actions-document-open-read-only',
	'actions-system-typoscript-documentation-open',
	'actions-edit-pick-date',
	'actions-view-paging-previous-disabled',
	'actions-edit-cut-release',
	'actions-system-extension-import',
	'actions-view-go-down',
	'actions-edit-undo',
	'actions-document-open',
	'actions-view-list-expand',
	'actions-view-paging-first',
	'actions-document-localize',
	'actions-view-table-expand',
	'actions-system-refresh',
	'actions-system-extension-documentation',
	'actions-system-cache-clear-rte',
	'actions-page-new',
	'actions-view-paging-next-disabled',
	'actions-view-paging-last',
	'actions-document-paste-into',
	'actions-document-history-open',
	'actions-view-table-collapse',
	'actions-document-export-csv',
	'actions-document-select',
	'actions-version-swap-version',
	'actions-move-to-top',
	'actions-view-list-collapse',
	'actions-edit-copy-release',
	'actions-template-new',
	'actions-system-extension-download',
	'actions-system-cache-clear-impact-medium',
	'actions-view-go-up',
	'actions-edit-hide',
	'actions-document-view',
	'actions-edit-localize-status-low',
	'actions-system-options-view',
	'actions-edit-delete',
	'actions-move-right',
	'actions-window-open',
	'apps-filetree-root',
	'apps-pagetree-page-frontend-users',
	'apps-filetree-folder-temp',
	'apps-pagetree-page-advanced-hideinmenu',
	'apps-pagetree-page-shortcut-hideinmenu',
	'apps-pagetree-backend-user',
	'apps-pagetree-page-frontend-user-hideinmenu',
	'apps-pagetree-page-default',
	'apps-pagetree-drag-place-denied',
	'apps-filetree-folder-user',
	'apps-pagetree-drag-new-inside',
	'apps-pagetree-drag-move-into',
	'apps-filetree-folder-add',
	'apps-pagetree-page-domain',
	'apps-toolbar-menu-workspace',
	'apps-pagetree-drag-new-between',
	'apps-pagetree-page-shortcut-external',
	'apps-pagetree-drag-move-between',
	'apps-pagetree-page-backend-users',
	'apps-filetree-folder-locked',
	'apps-pagetree-backend-user-hideinmenu',
	'apps-pagetree-page-mountpoint',
	'apps-pagetree-root',
	'apps-pagetree-page-frontend-user',
	'apps-pagetree-page-advanced',
	'apps-pagetree-page-frontend-users-hideinmenu',
	'apps-filetree-mount',
	'apps-toolbar-menu-shortcut',
	'apps-pagetree-page-no-icon-found-hideinmenu',
	'apps-filetree-folder-default',
	'apps-pagetree-page-shortcut',
	'apps-clipboard-images',
	'apps-pagetree-page-no-icon-found',
	'apps-filetree-folder-media',
	'apps-pagetree-spacer',
	'apps-pagetree-page-mountpoint-hideinmenu',
	'apps-pagetree-page-backend-users-hideinmenu',
	'apps-pagetree-folder-default',
	'apps-pagetree-page-not-in-menu',
	'apps-clipboard-list',
	'apps-filetree-folder-news',
	'apps-filetree-folder-list',
	'mimetypes-x-content-link',
	'mimetypes-text-css',
	'mimetypes-media-video',
	'mimetypes-x-content-script',
	'mimetypes-x-content-plugin',
	'mimetypes-media-audio',
	'mimetypes-other-other',
	'mimetypes-pdf',
	'mimetypes-x-content-divider',
	'mimetypes-powerpoint',
	'mimetypes-x-content-table',
	'mimetypes-x-content-text-picture',
	'mimetypes-x-content-login',
	'mimetypes-x-content-header',
	'mimetypes-x-content-form-search',
	'mimetypes-excel',
	'mimetypes-x-content-template',
	'mimetypes-x-content-multimedia',
	'mimetypes-text-csv',
	'mimetypes-x-content-list-files',
	'mimetypes-x-content-text',
	'mimetypes-text-text',
	'mimetypes-text-php',
	'mimetypes-x-content-list-bullets',
	'mimetypes-media-image',
	'mimetypes-x-content-form',
	'mimetypes-x-content-image',
	'mimetypes-x-content-menu',
	'mimetypes-compressed',
	'mimetypes-x-content-html',
	'mimetypes-text-html',
	'mimetypes-media-flash',
	'mimetypes-x-content-template-static',
	'mimetypes-text-js',
	'places-folder-closed',
	'places-folder-opened',
	'status-version-17',
	'status-version-44',
	'status-version-40',
	'status-version-47',
	'status-version-5',
	'status-overlay-deleted',
	'status-version-3',
	'status-overlay-includes-subpages',
	'status-version-21',
	'status-dialog-ok',
	'status-version-18',
	'status-version-46',
	'status-version-38',
	'status-status-checked',
	'status-version-19',
	'status-dialog-error',
	'status-version-14',
	'status-version-39',
	'status-version-no-version',
	'status-user-group-backend',
	'status-status-edit-read-only',
	'status-version-11',
	'status-user-group-frontend',
	'status-status-permission-granted',
	'status-warning-lock',
	'status-version-45',
	'status-overlay-hidden',
	'status-version-4',
	'status-version-28',
	'status-version-24',
	'status-version-15',
	'status-overlay-icon-missing',
	'status-version-33',
	'status-dialog-information',
	'status-version-22',
	'status-user-frontend',
	'status-version-9',
	'status-version-50',
	'status-status-icon-missing',
	'status-version-29',
	'status-version-13',
	'status-version-6',
	'status-user-backend',
	'status-version-41',
	'status-version-16',
	'status-version-37',
	'status-system-extension-required',
	'status-overlay-scheduled',
	'status-version-34',
	'status-status-reference-soft',
	'status-status-reference-hard',
	'status-version-43',
	'status-version-2',
	'status-version-35',
	'status-version-49',
	'status-version-1',
	'status-overlay-locked',
	'status-version-32',
	'status-version-36',
	'status-version-48',
	'status-version-30',
	'status-version-8',
	'status-status-locked',
	'status-warning-in-use',
	'status-status-workspace-draft',
	'status-user-admin',
	'status-version-7',
	'status-version-10',
	'status-version-27',
	'status-version-23',
	'status-version-42',
	'status-dialog-notification',
	'status-version-20',
	'status-status-current',
	'status-version-25',
	'status-overlay-translated',
	'status-version-31',
	'status-version-12',
	'status-dialog-warning',
	'status-status-permission-denied',
	'status-overlay-access-restricted',
	'status-version-26'
);

$GLOBALS['TBE_STYLES']['spriteIconApi']['spriteIconRecordOverlayPriorities'] = array('hidden', 'starttime', 'endtime', 'futureendtime', 'fe_group', 'protectedSection');
$GLOBALS['TBE_STYLES']['spriteIconApi']['spriteIconRecordOverlayNames'] = array(
	'hidden'           => 'status-overlay-hidden',
	'fe_group'         => 'status-overlay-access-restricted',
	'starttime'        => 'status-overlay-scheduled-start',
	'endtime'          => 'status-overlay-scheduled-end',
	'futureendtime'    => 'status-overlay-scheduled-future-end',
	'readonly'         => 'status-overlay-locked',
	'deleted'          => 'status-overlay-deleted',
	'missing'          => 'status-overlay-missing',
	'translated'       => 'status-overlay-translated',
	'protectedSection' => 'status-overlay-includes-subpages',
);

?>

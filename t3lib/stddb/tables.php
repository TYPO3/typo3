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
 * Contains the initialization of global TYPO3 variables among which $TCA is the most significant.
 *
 * The list in order of apperance is: $PAGES_TYPES, $ICON_TYPES, $TCA, $TBE_MODULES, $TBE_STYLES, $FILEICONS
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
 * Revised for TYPO3 3.6 July/2003 by Kasper Skårhøj
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @see tslib_fe::includeTCA(), typo3/init.php, t3lib/stddb/load_ext_tables.php
 */


/**
 * $PAGES_TYPES defines the various types of pages (field: doktype) the system can handle and what restrictions may apply to them.
 * Here you can set the icon and especially you can define which tables are allowed on a certain pagetype (doktype)
 * NOTE: The 'default' entry in the $PAGES_TYPES-array is the 'base' for all types, and for every type the entries simply overrides the entries in the 'default' type!
 *
 * NOTE: usage of 'icon' is deprecated since TYPO3 4.4, use t3lib_SpriteManager::addTcaTypeIcon() instead
 */
$PAGES_TYPES = array(
	(string) t3lib_pageSelect::DOKTYPE_LINK => array(
	),
	(string) t3lib_pageSelect::DOKTYPE_SHORTCUT => array(
	),
	(string) t3lib_pageSelect::DOKTYPE_HIDE_IN_MENU => array(
	),
	(string) t3lib_pageSelect::DOKTYPE_BE_USER_SECTION => array(
		'type' => 'web',
		'allowedTables' => '*'
	),
	(string) t3lib_pageSelect::DOKTYPE_MOUNTPOINT => array(
	),
	(string) t3lib_pageSelect::DOKTYPE_SPACER => array( // TypoScript: Limit is 200. When the doktype is 200 or above, the page WILL NOT be regarded as a 'page' by TypoScript. Rather is it a system-type page
		'type' => 'sys',
	),
	(string) t3lib_pageSelect::DOKTYPE_SYSFOLDER => array( //  Doktype 254 is a 'Folder' - a general purpose storage folder for whatever you like. In CMS context it's NOT a viewable page. Can contain any element.
		'type' => 'sys',
		'allowedTables' => '*'
	),
	(string) t3lib_pageSelect::DOKTYPE_RECYCLER => array( // Doktype 255 is a recycle-bin.
		'type' => 'sys',
		'allowedTables' => '*'
	),
	'default' => array(
		'type' => 'web',
		'allowedTables' => 'pages',
		'onlyAllowedTables' => '0'
	)
);


/**
 * With $ICON_TYPES you can assign alternative icons to pages records based on another field than 'doktype'
 * Each key is a value from the "module" field of page records and the value is an array with a key/value pair, eg. "icon" => "modules_shop.gif"
 *
 * @see t3lib_iconWorks::getIcon(), typo3/sysext/cms/ext_tables.php
 * @deprecated since TYPO3 4.4, use t3lib_SpriteManager::addTcaTypeIcon instead
 */
$ICON_TYPES = array();


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
		'useColumnsForDefaultValues' => 'doktype,fe_group,hidden',
		'dividers2tabs' => 1,
		'enablecolumns' => array(
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
			'fe_group' => 'fe_group',
		),
		'transForeignTable' => 'pages_language_overlay',
		'typeicon_column' => 'doktype',
		'typeicon_classes' => array(
			'1' => 'apps-pagetree-page-default',
			'1-hideinmenu' => 'apps-pagetree-page-not-in-menu',
			'1-root' => 'apps-pagetree-page-domain',
			'3' => 'apps-pagetree-page-shortcut-external',
			'3-hideinmenu' => 'apps-pagetree-page-shortcut-external-hideinmenu',
			'3-root' => 'apps-pagetree-page-shortcut-external-root',
			'4' => 'apps-pagetree-page-shortcut',
			'4-hideinmenu' => 'apps-pagetree-page-shortcut-hideinmenu',
			'4-root' => 'apps-pagetree-page-shortcut-root',
			'6' => 'apps-pagetree-page-backend-users',
			'6-hideinmenu' => 'apps-pagetree-page-backend-users-hideinmenu',
			'6-root' => 'apps-pagetree-page-backend-users-root',
			'7' => 'apps-pagetree-page-mountpoint',
			'7-hideinmenu' => 'apps-pagetree-page-mountpoint-hideinmenu',
			'7-root' => 'apps-pagetree-page-mountpoint-root',
			'199' => 'apps-pagetree-spacer',
			'199-hideinmenu' => 'apps-pagetree-spacer',
			'199-root' => 'apps-pagetree-page-domain',
			'254' => 'apps-pagetree-folder-default',
			'254-hideinmenu' => 'apps-pagetree-folder-default',
			'254-root' => 'apps-pagetree-page-domain',
			'255' => 'apps-pagetree-page-recycler',
			'255-hideinmenu' => 'apps-pagetree-page-recycler',
			'contains-shop' => 'apps-pagetree-folder-contains-shop',
			'contains-approve' => 'apps-pagetree-folder-contains-approve',
			'contains-fe_users' => 'apps-pagetree-folder-contains-fe_users',
			'contains-board' => 'apps-pagetree-folder-contains-board',
			'contains-news' => 'apps-pagetree-folder-contains-news',
			'default' => 'apps-pagetree-page-default',
		),
		'typeicons' => array(
			'1' => 'pages.gif',
			'254' => 'sysf.gif',
			'255' => 'recycler.gif',
		),
		'dynamicConfigFile' => 'T3LIB:tbl_pages.php',
	)
);

// Initialize the additional configuration of the table 'pages':
t3lib_div::loadTCA('pages');

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
		'adminOnly' => 1, // Only admin users can edit
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
			'default' => 'mimetypes-x-sys_language',
		),
		'dynamicConfigFile' => 'T3LIB:tbl_be.php',
		'versioningWS_alwaysAllowLiveEdit' => TRUE
	)
);


/**
 * Table "sys_news":
 * Holds news records to be displayed in the login screen
 * This is only the 'header' part (ctrl). The full configuration is found
 * in t3lib/stddb/tbl_be.php
 */
$TCA['sys_news'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:lang/locallang_tca.xml:sys_news',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'adminOnly' => TRUE,
		'rootLevel' => TRUE,
		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime'
		),
		'default_sortby' => 'crdate DESC',
		'typeicon_classes' => array(
			'default' => 'mimetypes-x-sys_news',
		),
		'dynamicConfigFile' => 'T3LIB:tbl_be.php',
		'dividers2tabs' => TRUE
	)
);


/**
 * $TBE_MODULES contains the structure of the backend modules as they are arranged in main- and sub-modules.
 * Every entry in this array represents a menu item on either first (key) or second level (value from list) in the left menu in the TYPO3 backend
 * For information about adding modules to TYPO3 you should consult the documentation found in "Inside TYPO3"
 */
$TBE_MODULES = array(
	'web' => 'list',
	'file' => '',
	'user' => '',
	'tools' => '',
	'help' => '',
);

	// register the pagetree core navigation component
t3lib_extMgm::addCoreNavigationComponent('web', 'typo3-pagetree', array(
	'TYPO3.Components.PageTree'
));

/**
 * $TBE_STYLES configures backend styles and colors; Basically this contains all the values that can be used to create new skins for TYPO3.
 * For information about making skins to TYPO3 you should consult the documentation found in "Inside TYPO3"
 */
$TBE_STYLES = array(
	'colorschemes' => array(
		'0' => '#E4E0DB,#CBC7C3,#EDE9E5',
	),
	'borderschemes' => array(
		'0' => array('border:solid 1px black;', 5)
	)
);


/**
 * Setting up $TCA_DESCR - Context Sensitive Help (CSH)
 * For information about using the CSH API in TYPO3 you should consult the documentation found in "Inside TYPO3"
 */
t3lib_extMgm::addLLrefForTCAdescr('pages', 'EXT:lang/locallang_csh_pages.xml');
t3lib_extMgm::addLLrefForTCAdescr('be_users', 'EXT:lang/locallang_csh_be_users.xml');
t3lib_extMgm::addLLrefForTCAdescr('be_groups', 'EXT:lang/locallang_csh_be_groups.xml');
t3lib_extMgm::addLLrefForTCAdescr('sys_filemounts', 'EXT:lang/locallang_csh_sysfilem.xml');
t3lib_extMgm::addLLrefForTCAdescr('sys_language', 'EXT:lang/locallang_csh_syslang.xml');
t3lib_extMgm::addLLrefForTCAdescr('sys_news', 'EXT:lang/locallang_csh_sysnews.xml');
t3lib_extMgm::addLLrefForTCAdescr('sys_workspace', 'EXT:lang/locallang_csh_sysws.xml');
t3lib_extMgm::addLLrefForTCAdescr('xMOD_csh_corebe', 'EXT:lang/locallang_csh_corebe.xml'); // General Core
t3lib_extMgm::addLLrefForTCAdescr('_MOD_tools_em', 'EXT:lang/locallang_csh_em.xml'); // Extension manager
t3lib_extMgm::addLLrefForTCAdescr('_MOD_web_info', 'EXT:lang/locallang_csh_web_info.xml'); // Web > Info
t3lib_extMgm::addLLrefForTCAdescr('_MOD_web_func', 'EXT:lang/locallang_csh_web_func.xml'); // Web > Func

// Labels for TYPO3 4.5 and greater.  These labels override the ones set above, while still falling back to the original labels if no translation is available.
$GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']['EXT:lang/locallang_csh_pages.xml'][] = 'EXT:lang/4.5/locallang_csh_pages.xml';
$GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']['EXT:lang/locallang_csh_corebe.xml'][] = 'EXT:lang/4.5/locallang_csh_corebe.xml';

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
$GLOBALS['TBE_STYLES']['spriteIconApi']['coreSpriteImageNames'] = array(
	'actions-document-close',
	'actions-document-duplicates-select',
	'actions-document-edit-access',
	'actions-document-export-csv',
	'actions-document-export-t3d',
	'actions-document-history-open',
	'actions-document-import-t3d',
	'actions-document-info',
	'actions-document-localize',
	'actions-document-move',
	'actions-document-new',
	'actions-document-open',
	'actions-document-open-read-only',
	'actions-document-paste-after',
	'actions-document-paste-into',
	'actions-document-save',
	'actions-document-save-close',
	'actions-document-save-new',
	'actions-document-save-view',
	'actions-document-select',
	'actions-document-synchronize',
	'actions-document-view',
	'actions-edit-add',
	'actions-edit-copy',
	'actions-edit-copy-release',
	'actions-edit-cut',
	'actions-edit-cut-release',
	'actions-edit-delete',
	'actions-edit-hide',
	'actions-edit-insert-default',
	'actions-edit-localize-status-high',
	'actions-edit-localize-status-low',
	'actions-edit-pick-date',
	'actions-edit-rename',
	'actions-edit-restore',
	'actions-edit-undelete-edit',
	'actions-edit-undo',
	'actions-edit-unhide',
	'actions-edit-upload',
	'actions-input-clear',
	'actions-insert-record',
	'actions-insert-reference',
	'actions-move-down',
	'actions-move-left',
	'actions-move-move',
	'actions-move-right',
	'actions-move-to-bottom',
	'actions-move-to-top',
	'actions-move-up',
	'actions-page-move',
	'actions-page-new',
	'actions-page-open',
	'actions-selection-delete',
	'actions-system-backend-user-emulate',
	'actions-system-backend-user-switch',
	'actions-system-cache-clear',
	'actions-system-cache-clear-impact-high',
	'actions-system-cache-clear-impact-low',
	'actions-system-cache-clear-impact-medium',
	'actions-system-cache-clear-rte',
	'actions-system-extension-documentation',
	'actions-system-extension-download',
	'actions-system-extension-import',
	'actions-system-extension-install',
	'actions-system-extension-uninstall',
	'actions-system-extension-update',
	'actions-system-help-open',
	'actions-system-list-open',
	'actions-system-options-view',
	'actions-system-pagemodule-open',
	'actions-system-refresh',
	'actions-system-shortcut-new',
	'actions-system-tree-search-open',
	'actions-system-typoscript-documentation',
	'actions-system-typoscript-documentation-open',
	'actions-template-new',
	'actions-version-document-remove',
	'actions-version-page-open',
	'actions-version-swap-version',
	'actions-version-swap-workspace',
	'actions-version-workspace-preview',
	'actions-version-workspace-sendtostage',
	'actions-view-go-back',
	'actions-view-go-down',
	'actions-view-go-forward',
	'actions-view-go-up',
	'actions-view-list-collapse',
	'actions-view-list-expand',
	'actions-view-paging-first',
	'actions-view-paging-first-disabled',
	'actions-view-paging-last',
	'actions-view-paging-last-disabled',
	'actions-view-paging-next',
	'actions-view-paging-next-disabled',
	'actions-view-paging-previous',
	'actions-view-paging-previous-disabled',
	'actions-view-table-collapse',
	'actions-view-table-expand',
	'actions-window-open',
	'apps-clipboard-images',
	'apps-clipboard-list',
	'apps-filetree-folder-add',
	'apps-filetree-folder-default',
	'apps-filetree-folder-list',
	'apps-filetree-folder-locked',
	'apps-filetree-folder-media',
	'apps-filetree-folder-news',
	'apps-filetree-folder-opened',
	'apps-filetree-folder-recycler',
	'apps-filetree-folder-temp',
	'apps-filetree-folder-user',
	'apps-filetree-mount',
	'apps-filetree-root',
	'apps-pagetree-backend-user',
	'apps-pagetree-backend-user-hideinmenu',
	'apps-pagetree-drag-copy-above',
	'apps-pagetree-drag-copy-below',
	'apps-pagetree-drag-move-above',
	'apps-pagetree-drag-move-below',
	'apps-pagetree-drag-move-between',
	'apps-pagetree-drag-move-into',
	'apps-pagetree-drag-new-between',
	'apps-pagetree-drag-new-inside',
	'apps-pagetree-drag-place-denied',
	'apps-pagetree-folder-contains-approve',
	'apps-pagetree-folder-contains-board',
	'apps-pagetree-folder-contains-fe_users',
	'apps-pagetree-folder-contains-news',
	'apps-pagetree-folder-contains-shop',
	'apps-pagetree-folder-default',
	'apps-pagetree-page-advanced',
	'apps-pagetree-page-advanced-hideinmenu',
	'apps-pagetree-page-advanced-root',
	'apps-pagetree-page-backend-users',
	'apps-pagetree-page-backend-users-hideinmenu',
	'apps-pagetree-page-backend-users-root',
	'apps-pagetree-page-default',
	'apps-pagetree-page-domain',
	'apps-pagetree-page-frontend-user',
	'apps-pagetree-page-frontend-user-hideinmenu',
	'apps-pagetree-page-frontend-user-root',
	'apps-pagetree-page-frontend-users',
	'apps-pagetree-page-frontend-users-hideinmenu',
	'apps-pagetree-page-frontend-users-root',
	'apps-pagetree-page-mountpoint',
	'apps-pagetree-page-mountpoint-hideinmenu',
	'apps-pagetree-page-mountpoint-root',
	'apps-pagetree-page-no-icon-found',
	'apps-pagetree-page-no-icon-found-hideinmenu',
	'apps-pagetree-page-no-icon-found-root',
	'apps-pagetree-page-not-in-menu',
	'apps-pagetree-page-recycler',
	'apps-pagetree-page-shortcut',
	'apps-pagetree-page-shortcut-external',
	'apps-pagetree-page-shortcut-external-hideinmenu',
	'apps-pagetree-page-shortcut-external-root',
	'apps-pagetree-page-shortcut-hideinmenu',
	'apps-pagetree-page-shortcut-root',
	'apps-pagetree-root',
	'apps-pagetree-spacer',
	'apps-toolbar-menu-actions',
	'apps-toolbar-menu-cache',
	'apps-toolbar-menu-opendocs',
	'apps-toolbar-menu-search',
	'apps-toolbar-menu-shortcut',
	'apps-toolbar-menu-workspace',
	'mimetypes-compressed',
	'mimetypes-excel',
	'mimetypes-media-audio',
	'mimetypes-media-flash',
	'mimetypes-media-image',
	'mimetypes-media-video',
	'mimetypes-other-other',
	'mimetypes-pdf',
	'mimetypes-powerpoint',
	'mimetypes-text-css',
	'mimetypes-text-csv',
	'mimetypes-text-html',
	'mimetypes-text-js',
	'mimetypes-text-php',
	'mimetypes-text-text',
	'mimetypes-x-content-divider',
	'mimetypes-x-content-domain',
	'mimetypes-x-content-form',
	'mimetypes-x-content-form-search',
	'mimetypes-x-content-header',
	'mimetypes-x-content-html',
	'mimetypes-x-content-image',
	'mimetypes-x-content-link',
	'mimetypes-x-content-list-bullets',
	'mimetypes-x-content-list-files',
	'mimetypes-x-content-login',
	'mimetypes-x-content-menu',
	'mimetypes-x-content-multimedia',
	'mimetypes-x-content-page-language-overlay',
	'mimetypes-x-content-plugin',
	'mimetypes-x-content-script',
	'mimetypes-x-content-table',
	'mimetypes-x-content-template',
	'mimetypes-x-content-template-extension',
	'mimetypes-x-content-template-static',
	'mimetypes-x-content-text',
	'mimetypes-x-content-text-picture',
	'mimetypes-x-sys_action',
	'mimetypes-x-sys_language',
	'mimetypes-x-sys_news',
	'mimetypes-x-sys_workspace',
	'mimetypes-x_belayout',
	'status-dialog-error',
	'status-dialog-information',
	'status-dialog-notification',
	'status-dialog-ok',
	'status-dialog-warning',
	'status-overlay-access-restricted',
	'status-overlay-deleted',
	'status-overlay-hidden',
	'status-overlay-icon-missing',
	'status-overlay-includes-subpages',
	'status-overlay-locked',
	'status-overlay-scheduled',
	'status-overlay-scheduled-future-end',
	'status-overlay-translated',
	'status-status-checked',
	'status-status-current',
	'status-status-edit-read-only',
	'status-status-icon-missing',
	'status-status-locked',
	'status-status-permission-denied',
	'status-status-permission-granted',
	'status-status-reference-hard',
	'status-status-reference-soft',
	'status-status-workspace-draft',
	'status-system-extension-required',
	'status-user-admin',
	'status-user-backend',
	'status-user-frontend',
	'status-user-group-backend',
	'status-user-group-frontend',
	'status-version-1',
	'status-version-2',
	'status-version-3',
	'status-version-4',
	'status-version-5',
	'status-version-6',
	'status-version-7',
	'status-version-8',
	'status-version-9',
	'status-version-10',
	'status-version-11',
	'status-version-12',
	'status-version-13',
	'status-version-14',
	'status-version-15',
	'status-version-16',
	'status-version-17',
	'status-version-18',
	'status-version-19',
	'status-version-20',
	'status-version-21',
	'status-version-22',
	'status-version-23',
	'status-version-24',
	'status-version-25',
	'status-version-26',
	'status-version-27',
	'status-version-28',
	'status-version-29',
	'status-version-30',
	'status-version-31',
	'status-version-32',
	'status-version-33',
	'status-version-34',
	'status-version-35',
	'status-version-36',
	'status-version-37',
	'status-version-38',
	'status-version-39',
	'status-version-40',
	'status-version-41',
	'status-version-42',
	'status-version-43',
	'status-version-44',
	'status-version-45',
	'status-version-46',
	'status-version-47',
	'status-version-48',
	'status-version-49',
	'status-version-50',
	'status-version-no-version',
	'status-warning-in-use',
	'status-warning-lock'
);




$GLOBALS['TBE_STYLES']['spriteIconApi']['spriteIconRecordOverlayPriorities'] = array(
	'deleted',
	'hidden',
	'starttime',
	'endtime',
	'futureendtime',
	'fe_group',
	'protectedSection'
);
$GLOBALS['TBE_STYLES']['spriteIconApi']['spriteIconRecordOverlayNames'] = array(
	'hidden' => 'status-overlay-hidden',
	'fe_group' => 'status-overlay-access-restricted',
	'starttime' => 'status-overlay-scheduled',
	'endtime' => 'status-overlay-scheduled',
	'futureendtime' => 'status-overlay-scheduled-future-end',
	'readonly' => 'status-overlay-locked',
	'deleted' => 'status-overlay-deleted',
	'missing' => 'status-overlay-missing',
	'translated' => 'status-overlay-translated',
	'protectedSection' => 'status-overlay-includes-subpages',
);

?>

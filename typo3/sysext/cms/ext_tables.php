<?php
# TYPO3 CVS ID: $Id$
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE=='BE')	{
	t3lib_extMgm::addModule('web','layout','top',t3lib_extMgm::extPath($_EXTKEY).'layout/');
	t3lib_extMgm::addLLrefForTCAdescr('_MOD_web_layout','EXT:cms/locallang_csh_weblayout.xml');
	t3lib_extMgm::addLLrefForTCAdescr('_MOD_web_info','EXT:cms/locallang_csh_webinfo.xml');

	t3lib_extMgm::insertModuleFunction(
		'web_info',
		'tx_cms_webinfo_page',
		t3lib_extMgm::extPath($_EXTKEY).'web_info/class.tx_cms_webinfo.php',
		'LLL:EXT:cms/locallang_tca.php:mod_tx_cms_webinfo_page'
	);
	t3lib_extMgm::insertModuleFunction(
		'web_info',
		'tx_cms_webinfo_lang',
		t3lib_extMgm::extPath($_EXTKEY).'web_info/class.tx_cms_webinfo_lang.php',
		'LLL:EXT:cms/locallang_tca.php:mod_tx_cms_webinfo_lang'
	);
}


// ******************************************************************
// Extend 'pages'-table
// ******************************************************************

if (TYPO3_MODE=='BE')	{
	// Setting ICON_TYPES (obsolete by the removal of the plugin_mgm extension)
	$ICON_TYPES = Array();
}

	// Adding pages_types:
		// t3lib_div::array_merge() MUST be used!
	$PAGES_TYPES = t3lib_div::array_merge(array(
		'3' => Array(
			'icon' => 'pages_link.gif'
		),
		'4' => Array(
			'icon' => 'pages_shortcut.gif'
		),
		'5' => Array(
			'icon' => 'pages_notinmenu.gif'
		),
		'7' => Array(
			'icon' => 'pages_mountpoint.gif'
		),
		'6' => Array(
			'type' => 'web',
			'icon' => 'be_users_section.gif',
			'allowedTables' => '*'
		),
		'199' => Array(		// TypoScript: Limit is 200. When the doktype is 200 or above, the page WILL NOT be regarded as a 'page' by TypoScript. Rather is it a system-type page
			'type' => 'sys',
			'icon' => 'spacer_icon.gif',
		)
	),$PAGES_TYPES);

	// Add allowed records to pages:
	t3lib_extMgm::allowTableOnStandardPages('pages_language_overlay,tt_content,sys_template,sys_domain');

	// Merging in CMS doktypes:
	array_splice(
		$TCA['pages']['columns']['doktype']['config']['items'],
		1,
		0,
		Array(
			Array('LLL:EXT:cms/locallang_tca.php:pages.doktype.I.0', '2'),
			Array('LLL:EXT:lang/locallang_general.php:LGL.external', '3'),
			Array('LLL:EXT:cms/locallang_tca.php:pages.doktype.I.2', '4'),
			Array('LLL:EXT:cms/locallang_tca.php:pages.doktype.I.3', '5'),
			Array('LLL:EXT:cms/locallang_tca.php:pages.doktype.I.4', '6'),
			Array('LLL:EXT:cms/locallang_tca.php:pages.doktype.I.5', '7'),
			Array('-----', '--div--'),
			Array('LLL:EXT:cms/locallang_tca.php:pages.doktype.I.7', '199')
		)
	);

	// Setting enablecolumns:
	$TCA['pages']['ctrl']['enablecolumns'] = Array (
		'disabled' => 'hidden',
		'starttime' => 'starttime',
		'endtime' => 'endtime',
		'fe_group' => 'fe_group',
	);

	// Adding default value columns:
	$TCA['pages']['ctrl']['useColumnsForDefaultValues'].=',fe_group,hidden';

	// Adding new columns:
	$TCA['pages']['columns'] = array_merge($TCA['pages']['columns'],Array(
		'hidden' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.php:pages.hidden',
			'config' => Array (
				'type' => 'check',
				'default' => '1'
			)
		),
		'starttime' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.starttime',
			'config' => Array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'checkbox' => '0',
				'default' => '0'
			)
		),
		'endtime' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.endtime',
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
		'layout' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.layout',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('LLL:EXT:lang/locallang_general.php:LGL.normal', '0'),
					Array('LLL:EXT:cms/locallang_tca.php:pages.layout.I.1', '1'),
					Array('LLL:EXT:cms/locallang_tca.php:pages.layout.I.2', '2'),
					Array('LLL:EXT:cms/locallang_tca.php:pages.layout.I.3', '3')
				),
				'default' => '0'
			)
		),
		'fe_group' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.fe_group',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
					Array('LLL:EXT:lang/locallang_general.php:LGL.hide_at_login', -1),
					Array('LLL:EXT:lang/locallang_general.php:LGL.any_login', -2),
					Array('LLL:EXT:lang/locallang_general.php:LGL.usergroups', '--div--')
				),
				'foreign_table' => 'fe_groups'
			)
		),
		'extendToSubpages' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.php:pages.extendToSubpages',
			'config' => Array (
				'type' => 'check'
			)
		),
		'nav_title' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.php:pages.nav_title',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'max' => '256',
				'checkbox' => '',
				'eval' => 'trim'
			)
		),
		'nav_hide' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.php:pages.nav_hide',
			'config' => Array (
				'type' => 'check'
			)
		),
		'subtitle' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.php:pages.subtitle',
			'config' => Array (
				'type' => 'input',
				'size' => '30',
				'max' => '256',
				'eval' => ''
			)
		),
		'target' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.php:pages.target',
			'config' => Array (
				'type' => 'input',
				'size' => '7',
				'max' => '20',
				'eval' => 'trim',
				'checkbox' => ''
			)
		),
		'alias' => Array (
			'label' => 'LLL:EXT:cms/locallang_tca.php:pages.alias',
			'config' => Array (
				'type' => 'input',
				'size' => '10',
				'max' => '20',
				'eval' => 'nospace,alphanum_x,lower,unique',
				'softref' => 'notify'
			)
		),
		'url' => Array (
			'label' => 'LLL:EXT:cms/locallang_tca.php:pages.url',
			'config' => Array (
				'type' => 'input',
				'size' => '25',
				'max' => '256',
				'eval' => 'trim',
				'softref' => 'url'
			)
		),
		'urltype' => Array (
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.type',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', '0'),
					Array('http://', '1'),
					Array('ftp://', '2'),
					Array('mailto:', '3')
				),
				'default' => '1'
			)
		),
		'lastUpdated' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.php:pages.lastUpdated',
			'config' => Array (
				'type' => 'input',
				'size' => '12',
				'max' => '20',
				'eval' => 'datetime',
				'checkbox' => '0',
				'default' => '0'
			)
		),
		'newUntil' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.php:pages.newUntil',
			'config' => Array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'checkbox' => '0',
				'default' => '0'
			)
		),
		'cache_timeout' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.php:pages.cache_timeout',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('LLL:EXT:lang/locallang_general.php:LGL.default_value', 0),
					Array('LLL:EXT:cms/locallang_tca.php:pages.cache_timeout.I.1', 60),
					Array('LLL:EXT:cms/locallang_tca.php:pages.cache_timeout.I.2', 5*60),
					Array('LLL:EXT:cms/locallang_tca.php:pages.cache_timeout.I.3', 15*60),
					Array('LLL:EXT:cms/locallang_tca.php:pages.cache_timeout.I.4', 30*60),
					Array('LLL:EXT:cms/locallang_tca.php:pages.cache_timeout.I.5', 60*60),
					Array('LLL:EXT:cms/locallang_tca.php:pages.cache_timeout.I.6', 4*60*60),
					Array('LLL:EXT:cms/locallang_tca.php:pages.cache_timeout.I.7', 24*60*60),
					Array('LLL:EXT:cms/locallang_tca.php:pages.cache_timeout.I.8', 2*24*60*60),
					Array('LLL:EXT:cms/locallang_tca.php:pages.cache_timeout.I.9', 7*24*60*60),
					Array('LLL:EXT:cms/locallang_tca.php:pages.cache_timeout.I.10', 31*24*60*60)
				),
				'default' => '0'
			)
		),
		'no_cache' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.php:pages.no_cache',
			'config' => Array (
				'type' => 'check'
			)
		),
		'no_search' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.php:pages.no_search',
			'config' => Array (
				'type' => 'check'
			)
		),
		'shortcut' => Array (
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.shortcut_page',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
					'allowed' => 'pages',
				'size' => '3',
				'maxitems' => '1',
				'minitems' => '0',
				'show_thumbs' => '1'
			)
		),
		'shortcut_mode' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.php:pages.shortcut_mode',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
					Array('LLL:EXT:cms/locallang_tca.php:pages.shortcut_mode.I.1', 1),
					Array('LLL:EXT:cms/locallang_tca.php:pages.shortcut_mode.I.2', 2),
				),
				'default' => '0'
			)
		),
		'content_from_pid' => Array (
			'label' => 'LLL:EXT:cms/locallang_tca.php:pages.content_from_pid',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
					'allowed' => 'pages',
				'size' => '1',
				'maxitems' => '1',
				'minitems' => '0',
				'show_thumbs' => '1'
			)
		),
		'mount_pid' => Array (
			'label' => 'LLL:EXT:cms/locallang_tca.php:pages.mount_pid',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'db',
					'allowed' => 'pages',
				'size' => '1',
				'maxitems' => '1',
				'minitems' => '0',
				'show_thumbs' => '1'
			)
		),
		'keywords' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.keywords',
			'config' => Array (
				'type' => 'text',
				'cols' => '40',
				'rows' => '3'
			)
		),
		'description' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.description',
			'config' => Array (
				'type' => 'input',
				'size' => '40',
				'eval' => 'trim'
			)
		),
		'abstract' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.php:pages.abstract',
			'config' => Array (
				'type' => 'text',
				'cols' => '40',
				'rows' => '3'
			)
		),
		'author' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.author',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '80'
			)
		),
		'author_email' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.email',
			'config' => Array (
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '80',
				'softref' => 'email[subst]'
			)
		),
		'media' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.php:pages.media',
			'config' => Array (
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'].',html,htm,ttf,txt,css',
				'max_size' => '2000',
				'uploadfolder' => 'uploads/media',
				'show_thumbs' => '1',
				'size' => '3',
				'maxitems' => '5',
				'minitems' => '0'
			)
		),
		'is_siteroot' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.php:pages.is_siteroot',
			'config' => Array (
				'type' => 'check'
			)
		),
		'mount_pid_ol' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.php:pages.mount_pid_ol',
			'config' => Array (
				'type' => 'check'
			)
		),
		'module' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.php:pages.module',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', ''),
					Array('LLL:EXT:cms/locallang_tca.php:pages.module.I.1', 'shop'),
					Array('LLL:EXT:cms/locallang_tca.php:pages.module.I.2', 'board'),
					Array('LLL:EXT:cms/locallang_tca.php:pages.module.I.3', 'news'),
					Array('LLL:EXT:cms/locallang_tca.php:pages.module.I.4', 'fe_users'),
					Array('LLL:EXT:cms/locallang_tca.php:pages.module.I.5', 'dmail'),
					Array('LLL:EXT:cms/locallang_tca.php:pages.module.I.6', 'approve')
				),
				'default' => ''
			)
		),
		'fe_login_mode' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.php:pages.fe_login_mode',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
					Array('LLL:EXT:cms/locallang_tca.php:pages.fe_login_mode.disable', 1),
					Array('LLL:EXT:cms/locallang_tca.php:pages.fe_login_mode.enable', 2),
				)
			)
		),
		'l18n_cfg' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.php:pages.l18n_cfg',
			'config' => Array (
				'type' => 'check',
				'items' => Array (
					Array('LLL:EXT:cms/locallang_tca.php:pages.l18n_cfg.I.1', ''),
					Array('LLL:EXT:cms/locallang_tca.php:pages.l18n_cfg.I.2', ''),
				),
			)
		),
	));

		// Add columns to info-display list.
	$TCA['pages']['interface']['showRecordFieldList'].=',alias,hidden,starttime,endtime,fe_group,url,target,no_cache,shortcut,keywords,description,abstract,newUntil,lastUpdated,cache_timeout';

		// Setting main palette
	$TCA['pages']['ctrl']['mainpalette']='1';

		// Totally overriding all type-settings:
	$TCA['pages']['types'] = Array (
		'1' => Array('showitem' => 'hidden;;;;1-1-1, doktype;;2;button, title;;3;;2-2-2, subtitle, nav_hide, TSconfig;;6;nowrap;5-5-5, storage_pid;;7, l18n_cfg'),
		'2' => Array('showitem' => 'hidden;;;;1-1-1, doktype;;2;button, title;;3;;2-2-2, subtitle, nav_hide, nav_title, --div--, abstract;;5;;3-3-3, keywords, description, media;;;;4-4-4, --div--, TSconfig;;6;nowrap;5-5-5, storage_pid;;7, l18n_cfg, fe_login_mode, module, content_from_pid'),
		'3' => Array('showitem' => 'hidden;;;;1-1-1, doktype, title;;3;;2-2-2, nav_hide, url;;;;3-3-3, urltype, TSconfig;;6;nowrap;5-5-5, storage_pid;;7, l18n_cfg'),
		'4' => Array('showitem' => 'hidden;;;;1-1-1, doktype, title;;3;;2-2-2, nav_hide, shortcut;;;;3-3-3, shortcut_mode, TSconfig;;6;nowrap;5-5-5, storage_pid;;7, l18n_cfg'),
		'5' => Array('showitem' => 'hidden;;;;1-1-1, doktype;;2;button, title;;3;;2-2-2, subtitle, nav_hide, nav_title, --div--, media;;;;4-4-4, --div--, TSconfig;;6;nowrap;5-5-5, storage_pid;;7, l18n_cfg, fe_login_mode, module, content_from_pid'),
		'7' => Array('showitem' => 'hidden;;;;1-1-1, doktype;;2;button, title;;3;;2-2-2, subtitle, nav_hide, nav_title, --div--, mount_pid;;;;3-3-3, mount_pid_ol, media;;;;4-4-4, --div--, TSconfig;;6;nowrap;5-5-5, storage_pid;;7, l18n_cfg, fe_login_mode, module, content_from_pid'),
		'199' => Array('showitem' => 'hidden;;;;1-1-1, doktype, title;;;;2-2-2, TSconfig;;6;nowrap;5-5-5, storage_pid;;7'),
		'254' => Array('showitem' => 'hidden;;;;1-1-1, doktype, title;LLL:EXT:lang/locallang_general.php:LGL.title;;;2-2-2, --div--, TSconfig;;6;nowrap;5-5-5, storage_pid;;7, module'),
		'255' => Array('showitem' => 'hidden;;;;1-1-1, doktype, title;;;;2-2-2')
	);
		// Merging palette settings:
		// t3lib_div::array_merge() MUST be used - otherwise the keys will be re-numbered!
	$TCA['pages']['palettes'] = t3lib_div::array_merge($TCA['pages']['palettes'],Array(
		'1' => Array('showitem' => 'starttime,endtime,fe_group,extendToSubpages'),
		'2' => Array('showitem' => 'layout, lastUpdated, newUntil, no_search'),
		'3' => Array('showitem' => 'alias, target, no_cache, cache_timeout'),
		'5' => Array('showitem' => 'author,author_email'),
	));






// ******************************************************************
// This is the standard TypoScript content table, tt_content
// ******************************************************************
$TCA['tt_content'] = Array (
	'ctrl' => Array (
		'label' => 'header',
		'label_alt' => 'subheader,bodytext',
		'sortby' => 'sorting',
		'tstamp' => 'tstamp',
		'title' => 'LLL:EXT:cms/locallang_tca.php:tt_content',
		'delete' => 'deleted',
		'versioning' => TRUE,
		'versioning_followPages' => TRUE,
		'type' => 'CType',
		'prependAtCopy' => 'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy',
		'copyAfterDuplFields' => 'colPos,sys_language_uid',
		'useColumnsForDefaultValues' => 'colPos,sys_language_uid',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'languageField' => 'sys_language_uid',
		'enablecolumns' => Array (
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
			'fe_group' => 'fe_group',
		),
		'typeicon_column' => 'CType',
		'typeicons' => Array (
			'header' => 'tt_content_header.gif',
			'textpic' => 'tt_content_textpic.gif',
			'image' => 'tt_content_image.gif',
			'bullets' => 'tt_content_bullets.gif',
			'table' => 'tt_content_table.gif',
			'splash' => 'tt_content_news.gif',
			'uploads' => 'tt_content_uploads.gif',
			'multimedia' => 'tt_content_mm.gif',
			'menu' => 'tt_content_menu.gif',
			'list' => 'tt_content_list.gif',
			'mailform' => 'tt_content_form.gif',
			'search' => 'tt_content_search.gif',
			'login' => 'tt_content_login.gif',
			'shortcut' => 'tt_content_shortcut.gif',
			'script' => 'tt_content_script.gif',
			'div' => 'tt_content_div.gif',
			'html' => 'tt_content_html.gif'
		),
		'mainpalette' => '1',
		'thumbnail' => 'image',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tbl_tt_content.php'
	)
);

// ******************************************************************
// fe_users
// ******************************************************************
$TCA['fe_users'] = Array (
	'ctrl' => Array (
		'label' => 'username',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'fe_cruser_id' => 'fe_cruser_id',
		'title' => 'LLL:EXT:cms/locallang_tca.php:fe_users',
		'delete' => 'deleted',
		'mainpalette' => '1',
		'enablecolumns' => Array (
			'disabled' => 'disable',
			'starttime' => 'starttime',
			'endtime' => 'endtime'
		),
		'useColumnsForDefaultValues' => 'usergroup,lockToDomain,disable,starttime,endtime',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tbl_cms.php'
	),
	'feInterface' => Array (
		'fe_admin_fieldList' => 'username,password,usergroup,name,address,telephone,fax,email,title,zip,city,country,www,company',
	)
);

// ******************************************************************
// fe_groups
// ******************************************************************
$TCA['fe_groups'] = Array (
	'ctrl' => Array (
		'label' => 'title',
		'tstamp' => 'tstamp',
		'delete' => 'deleted',
		'prependAtCopy' => 'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy',
		'enablecolumns' => Array (
			'disabled' => 'hidden'
		),
		'title' => 'LLL:EXT:cms/locallang_tca.php:fe_groups',
		'useColumnsForDefaultValues' => 'lockToDomain',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tbl_cms.php'
	)
);

// ******************************************************************
// sys_domain
// ******************************************************************
$TCA['sys_domain'] = Array (
	'ctrl' => Array (
		'label' => 'domainName',
		'tstamp' => 'tstamp',
		'sortby' => 'sorting',
		'title' => 'LLL:EXT:cms/locallang_tca.php:sys_domain',
		'iconfile' => 'domain.gif',
		'enablecolumns' => Array (
			'disabled' => 'hidden'
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tbl_cms.php'
	)
);

// ******************************************************************
// pages_language_overlay
// ******************************************************************
$TCA['pages_language_overlay'] = Array (
	'ctrl' => Array (
		'label' => 'title',
		'tstamp' => 'tstamp',
		'title' => 'LLL:EXT:cms/locallang_tca.php:pages_language_overlay',
		'versioning' => TRUE,
		'versioning_followPages' => TRUE,
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'enablecolumns' => Array (
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime'
		),
		'transOrigPointerField' => 'pid',
		'transOrigPointerTable' => 'pages',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'languageField' => 'sys_language_uid',
		'mainpalette' => 1,
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tbl_cms.php'
	)
);


// ******************************************************************
// sys_template
// ******************************************************************
$TCA['sys_template'] = Array (
	'ctrl' => Array (
		'label' => 'title',
		'tstamp' => 'tstamp',
		'sortby' => 'sorting',
		'prependAtCopy' => 'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy',
		'title' => 'LLL:EXT:cms/locallang_tca.php:sys_template',
		'versioning' => TRUE,
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'delete' => 'deleted',
		'adminOnly' => 1,	// Only admin, if any
		'iconfile' => 'template.gif',
		'thumbnail' => 'resources',
		'enablecolumns' => Array (
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime'
		),
		'typeicon_column' => 'root',
		'typeicons' => Array (
			'0' => 'template_add.gif'
		),
		'mainpalette' => '1',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tbl_cms.php'
	)
);

// ******************************************************************
// static_template
// ******************************************************************
$TCA['static_template'] = Array (
	'ctrl' => Array (
		'label' => 'title',
		'tstamp' => 'tstamp',
		'title' => 'LLL:EXT:cms/locallang_tca.php:static_template',
		'readOnly' => 1,	// This should always be true, as it prevents the static templates from being altered
		'adminOnly' => 1,	// Only admin, if any
		'rootLevel' => 1,
		'is_static' => 1,
		'default_sortby' => 'ORDER BY title',
		'crdate' => 'crdate',
		'iconfile' => 'template_standard.gif',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tbl_cms.php'
	)
);

?>
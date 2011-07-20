<?php
# TYPO3 SVN ID: $Id$
if (!defined ('TYPO3_MODE'))	die ('Access denied.');


if (TYPO3_MODE == 'BE') {
	t3lib_extMgm::addModule('web','layout','top',t3lib_extMgm::extPath($_EXTKEY).'layout/');
	t3lib_extMgm::addLLrefForTCAdescr('_MOD_web_layout','EXT:cms/locallang_csh_weblayout.xml');
	t3lib_extMgm::addLLrefForTCAdescr('_MOD_web_info','EXT:cms/locallang_csh_webinfo.xml');

	t3lib_extMgm::insertModuleFunction(
		'web_info',
		'tx_cms_webinfo_page',
		t3lib_extMgm::extPath($_EXTKEY).'web_info/class.tx_cms_webinfo.php',
		'LLL:EXT:cms/locallang_tca.xml:mod_tx_cms_webinfo_page'
	);
	t3lib_extMgm::insertModuleFunction(
		'web_info',
		'tx_cms_webinfo_lang',
		t3lib_extMgm::extPath($_EXTKEY).'web_info/class.tx_cms_webinfo_lang.php',
		'LLL:EXT:cms/locallang_tca.xml:mod_tx_cms_webinfo_lang'
	);
}


// ******************************************************************
// Extend 'pages'-table
// ******************************************************************

		// Adding pages_types:
		// t3lib_div::array_merge() MUST be used!
	$PAGES_TYPES = t3lib_div::array_merge(array(
		'3' => array(
		),
		'4' => array(
		),
		'5' => array(
		),
		'6' => array(
			'type' => 'web',
			'allowedTables' => '*'
		),
		'7' => array(
		),
		'199' => array(		// TypoScript: Limit is 200. When the doktype is 200 or above, the page WILL NOT be regarded as a 'page' by TypoScript. Rather is it a system-type page
			'type' => 'sys',
		)
	),$PAGES_TYPES);

	// Add allowed records to pages:
	t3lib_extMgm::allowTableOnStandardPages('pages_language_overlay,tt_content,sys_template,sys_domain');

	// Merging in CMS doktypes:
	array_splice(
		$TCA['pages']['columns']['doktype']['config']['items'],
		1,
		0,
		array(
			array('LLL:EXT:cms/locallang_tca.xml:pages.doktype.I.4', '6', 'i/be_users_section.gif'),
			array('LLL:EXT:cms/locallang_tca.xml:pages.doktype.div.link', '--div--'),
			array('LLL:EXT:cms/locallang_tca.xml:pages.doktype.I.2', '4', 'i/pages_shortcut.gif'),
			array('LLL:EXT:cms/locallang_tca.xml:pages.doktype.I.5', '7', 'i/pages_mountpoint.gif'),
			array('LLL:EXT:cms/locallang_tca.xml:pages.doktype.I.8', '3', 'i/pages_link.gif'),
			array('LLL:EXT:cms/locallang_tca.xml:pages.doktype.div.special', '--div--')
		)
	);
	array_splice(
		$TCA['pages']['columns']['doktype']['config']['items'],
		10,
		0,
		array(
			array('LLL:EXT:cms/locallang_tca.xml:pages.doktype.I.7', '199', 'i/spacer_icon.gif')
		)
	);
	array_unshift(
		$TCA['pages']['columns']['doktype']['config']['items'],
		array('LLL:EXT:cms/locallang_tca.xml:pages.doktype.div.page', '--div--')
	);

	// Setting enablecolumns:
	$TCA['pages']['ctrl']['enablecolumns'] = array (
		'disabled' => 'hidden',
		'starttime' => 'starttime',
		'endtime' => 'endtime',
		'fe_group' => 'fe_group',
	);

	// Enable Tabs
	$TCA['pages']['ctrl']['dividers2tabs'] = 1;

	// Adding default value columns:
	$TCA['pages']['ctrl']['useColumnsForDefaultValues'].=',fe_group,hidden';
	$TCA['pages']['ctrl']['transForeignTable'] = 'pages_language_overlay';

	// Adding new columns:
	$TCA['pages']['columns'] = array_merge($TCA['pages']['columns'],array(
		'hidden' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.hidden',
			'config' => array (
				'type' => 'check',
				'default' => '1'
			)
		),
		'starttime' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.starttime',
			'config' => array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'checkbox' => '0',
				'default' => '0'
			)
		),
		'endtime' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.endtime',
			'config' => array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'checkbox' => '0',
				'default' => '0',
				'range' => array (
					'upper' => mktime(0,0,0,12,31,2020),
				)
			)
		),
		'layout' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.layout',
			'config' => array (
				'type' => 'select',
				'items' => array (
					array('LLL:EXT:lang/locallang_general.xml:LGL.normal', '0'),
					array('LLL:EXT:cms/locallang_tca.xml:pages.layout.I.1', '1'),
					array('LLL:EXT:cms/locallang_tca.xml:pages.layout.I.2', '2'),
					array('LLL:EXT:cms/locallang_tca.xml:pages.layout.I.3', '3')
				),
				'default' => '0'
			)
		),
		'fe_group' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.fe_group',
			'config' => array (
				'type' => 'select',
				'size' => 5,
				'maxitems' => 20,
				'items' => array (
					array('LLL:EXT:lang/locallang_general.xml:LGL.hide_at_login', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.any_login', -2),
					array('LLL:EXT:lang/locallang_general.xml:LGL.usergroups', '--div--')
				),
				'exclusiveKeys' => '-1,-2',
				'foreign_table' => 'fe_groups',
				'foreign_table_where' => 'ORDER BY fe_groups.title',
			)
		),
		'extendToSubpages' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.extendToSubpages',
			'config' => array (
				'type' => 'check'
			)
		),
		'nav_title' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.nav_title',
			'config' => array (
				'type' => 'input',
				'size' => '30',
				'max' => '255',
				'checkbox' => '',
				'eval' => 'trim'
			)
		),
		'nav_hide' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.nav_hide',
			'config' => array (
				'type' => 'check'
			)
		),
		'subtitle' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.subtitle',
			'config' => array (
				'type' => 'input',
				'size' => '30',
				'max' => '255',
				'eval' => ''
			)
		),
		'target' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.target',
			'config' => array (
				'type' => 'input',
				'size' => '20',
				'max' => '80',
				'eval' => 'trim',
				'checkbox' => ''
			)
		),
		'alias' => array (
			'displayCond' => 'VERSION:IS:false',
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.alias',
			'config' => array (
				'type' => 'input',
				'size' => '10',
				'max' => '32',
				'eval' => 'nospace,alphanum_x,lower,unique',
				'softref' => 'notify'
			)
		),
		'url' => array (
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.url',
			'config' => array (
				'type' => 'input',
				'size' => '25',
				'max' => '255',
				'eval' => 'trim,required',
				'softref' => 'url'
			)
		),
		'urltype' => array (
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.type',
			'config' => array (
				'type' => 'select',
				'items' => array (
					array('', '0'),
					array('http://', '1'),
					array('https://', '4'),
					array('ftp://', '2'),
					array('mailto:', '3')
				),
				'default' => '1'
			)
		),
		'lastUpdated' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.lastUpdated',
			'config' => array (
				'type' => 'input',
				'size' => '12',
				'max' => '20',
				'eval' => 'datetime',
				'checkbox' => '0',
				'default' => '0'
			)
		),
		'newUntil' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.newUntil',
			'config' => array (
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'checkbox' => '0',
				'default' => '0'
			)
		),
		'cache_timeout' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.cache_timeout',
			'config' => array (
				'type' => 'select',
				'items' => array (
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0),
					array('LLL:EXT:cms/locallang_tca.xml:pages.cache_timeout.I.1', 60),
					array('LLL:EXT:cms/locallang_tca.xml:pages.cache_timeout.I.2', 300),
					array('LLL:EXT:cms/locallang_tca.xml:pages.cache_timeout.I.3', 900),
					array('LLL:EXT:cms/locallang_tca.xml:pages.cache_timeout.I.4', 1800),
					array('LLL:EXT:cms/locallang_tca.xml:pages.cache_timeout.I.5', 3600),
					array('LLL:EXT:cms/locallang_tca.xml:pages.cache_timeout.I.6', 14400),
					array('LLL:EXT:cms/locallang_tca.xml:pages.cache_timeout.I.7', 86400),
					array('LLL:EXT:cms/locallang_tca.xml:pages.cache_timeout.I.8', 172800),
					array('LLL:EXT:cms/locallang_tca.xml:pages.cache_timeout.I.9', 604800),
					array('LLL:EXT:cms/locallang_tca.xml:pages.cache_timeout.I.10', 2678400)
				),
				'default' => '0'
			)
		),
		'no_cache' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.no_cache',
			'config' => array (
				'type' => 'check'
			)
		),
		'no_search' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.no_search',
			'config' => array (
				'type' => 'check'
			)
		),
		'shortcut' => array (
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.shortcut_page',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'pages',
				'size' => '3',
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
				'items' => array (
					array('', 0),
					array('LLL:EXT:cms/locallang_tca.xml:pages.shortcut_mode.I.1', 1),
					array('LLL:EXT:cms/locallang_tca.xml:pages.shortcut_mode.I.2', 2),
				),
				'default' => '0'
			)
		),
		'content_from_pid' => array (
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.content_from_pid',
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
		'mount_pid' => array (
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.mount_pid',
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
		'keywords' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.keywords',
			'config' => array (
				'type' => 'text',
				'cols' => '40',
				'rows' => '3'
			)
		),
		'description' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.description',
			'config' => array (
				'type' => 'text',
				'cols' => '40',
				'rows' => '3'
			)
		),
		'abstract' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.abstract',
			'config' => array (
				'type' => 'text',
				'cols' => '40',
				'rows' => '3'
			)
		),
		'author' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.author',
			'config' => array (
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '80'
			)
		),
		'author_email' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.email',
			'config' => array (
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '80',
				'softref' => 'email[subst]'
			)
		),
		'media' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.media',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'].',html,htm,ttf,txt,css',
				'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
				'uploadfolder' => 'uploads/media',
				'show_thumbs' => '1',
				'size' => '3',
				'maxitems' => '5',
				'minitems' => '0'
			)
		),
		'is_siteroot' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.is_siteroot',
			'config' => array (
				'type' => 'check'
			)
		),
		'mount_pid_ol' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.mount_pid_ol',
			'config' => array (
				'type' => 'check'
			)
		),
		'module' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.module',
			'config' => array (
				'type' => 'select',
				'items' => array (
					array('', '', ''),
					array('LLL:EXT:cms/locallang_tca.xml:pages.module.I.1', 'shop', 'i/modules_shop.gif'),
					array('LLL:EXT:cms/locallang_tca.xml:pages.module.I.2', 'board', 'i/modules_board.gif'),
					array('LLL:EXT:cms/locallang_tca.xml:pages.module.I.3', 'news', 'i/modules_news.gif'),
					array('LLL:EXT:cms/locallang_tca.xml:pages.module.I.4', 'fe_users', 'i/fe_users.gif'),
					array('LLL:EXT:cms/locallang_tca.xml:pages.module.I.6', 'approve', 'state_checked.png')
				),
				'default' => '',
				'iconsInOptionTags' => 1,
				'noIconsBelowSelect' => 1,
			)
		),
		'fe_login_mode' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.fe_login_mode',
			'config' => array (
				'type' => 'select',
				'items' => array (
					array('', 0),
					array('LLL:EXT:cms/locallang_tca.xml:pages.fe_login_mode.disableAll', 1),
					array('LLL:EXT:cms/locallang_tca.xml:pages.fe_login_mode.disableGroups', 3),
					array('LLL:EXT:cms/locallang_tca.xml:pages.fe_login_mode.enableAgain', 2),
				)
			)
		),
		'l18n_cfg' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.l18n_cfg',
			'config' => array (
				'type' => 'check',
				'items' => array (
					array('LLL:EXT:cms/locallang_tca.xml:pages.l18n_cfg.I.1', ''),
					array($GLOBALS['TYPO3_CONF_VARS']['FE']['hidePagesIfNotTranslatedByDefault'] ? 'LLL:EXT:cms/locallang_tca.xml:pages.l18n_cfg.I.2a' : 'LLL:EXT:cms/locallang_tca.xml:pages.l18n_cfg.I.2', ''),
				),
			)
		),
	));

		// Add columns to info-display list.
	$TCA['pages']['interface']['showRecordFieldList'].=',alias,hidden,starttime,endtime,fe_group,url,target,no_cache,shortcut,keywords,description,abstract,newUntil,lastUpdated,cache_timeout';


		// Totally overriding all type-settings:
	$TCA['pages']['types'] = array (
			// normal
		'1' => array('showitem' =>
				'doktype;;2;;1-1-1, hidden, nav_hide, title;;3;;2-2-2, subtitle, nav_title,
			--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.metadata,
				--palette--;LLL:EXT:lang/locallang_general.xml:LGL.author;5;;3-3-3, abstract, keywords, description,
			--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.files,
				media,
			--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.options,
				TSconfig;;6;nowrap;6-6-6, storage_pid;;7, l18n_cfg, module, content_from_pid,
			--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access,
				starttime, endtime, fe_login_mode, fe_group, extendToSubpages,
			--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.extended,
		'),
			// external URL
		'3' => array('showitem' =>
				'doktype;;2;;1-1-1, hidden, nav_hide, title;;3;;2-2-2, subtitle,
			--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.url,
				url;;;;3-3-3, urltype,
			--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.files,
				media,
			--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.options,
				TSconfig;;6;nowrap;5-5-5, storage_pid;;7, l18n_cfg,
			--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access,
				starttime, endtime, fe_group, extendToSubpages,
			--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.extended,
		'),
			// shortcut
		'4' => array('showitem' =>
				'doktype;;2;;1-1-1, hidden, nav_hide, title;;3;;2-2-2, subtitle, nav_title,
			--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.shortcut,
				shortcut;;;;3-3-3, shortcut_mode,
			--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.files,
				media,
			--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.options,
				TSconfig;;6;nowrap;5-5-5, storage_pid;;7, l18n_cfg,
			--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access,
				starttime, endtime, fe_group, extendToSubpages,
			--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.extended,
		'),
			// not in menu
		'5' => array('showitem' =>
				'doktype;;2;;1-1-1, hidden, nav_hide, title;;3;;2-2-2, subtitle,
			--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.files,
				media,
			--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.options,
				TSconfig;;6;nowrap;5-5-5, storage_pid;;7, l18n_cfg, module, content_from_pid,
			--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access,
				starttime, endtime, fe_login_mode, fe_group, extendToSubpages,
			--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.extended,
		'),
			// mount page
		'7' => array('showitem' =>
				'doktype;;2;;1-1-1, hidden, nav_hide, title;;3;;2-2-2, subtitle, nav_title,
			--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.mount,
				mount_pid;;;;3-3-3, mount_pid_ol,
			--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.files,
				media,
			--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.options,
				TSconfig;;6;nowrap;5-5-5, storage_pid;;7, l18n_cfg, module, content_from_pid,
			--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access,
				starttime, endtime, fe_login_mode, fe_group, extendToSubpages,
			--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.extended,
		'),
			// spacer
		'199' => array('showitem' =>
				'doktype;;2;;1-1-1, hidden, title,
			--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.options,
				TSconfig;;6;nowrap;5-5-5, storage_pid;;7,
			--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.extended,
		'),
			// sysfolder
		'254' => array('showitem' =>
				'doktype;;2;;1-1-1, hidden, title;LLL:EXT:lang/locallang_general.xml:LGL.title,
			--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.files,
				media,
			--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.options,
				TSconfig;;6;nowrap;5-5-5, storage_pid;;7, module,
			--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.extended,
		'),
			// trash
		'255' => array('showitem' =>
				'doktype;;2;;1-1-1, hidden, title,
			--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.extended,
		')
	);

		// Merging palette settings:
		// t3lib_div::array_merge() MUST be used - otherwise the keys will be re-numbered!
	$TCA['pages']['palettes'] = t3lib_div::array_merge($TCA['pages']['palettes'],array(
		'1' => array('showitem' => 'starttime, endtime, extendToSubpages'),
		'2' => array('showitem' => 'layout, lastUpdated, newUntil, no_search'),
		'3' => array('showitem' => 'alias, target, no_cache, cache_timeout'),
		'5' => array('showitem' => 'author, author_email', 'canNotCollapse' => 1)
	));


	// if the compat version is less than 4.2, pagetype 2 ("Advanced")
	// and pagetype 5 ("Not in menu") are added to TCA.
	if (!t3lib_div::compat_version('4.2')) {
			// Merging in CMS doktypes
		array_splice(
			$TCA['pages']['columns']['doktype']['config']['items'],
			2,
			0,
			array(
				array('LLL:EXT:cms/locallang_tca.xml:pages.doktype.I.0', '2', 'i/pages.gif'),
				array('LLL:EXT:cms/locallang_tca.xml:pages.doktype.I.3', '5', 'i/pages_notinmenu.gif'),
			)
		);
			// setting the doktype 1 ("Standard") to show less fields
		$TCA['pages']['types'][1] = array(
				// standard
			'showitem' =>
					'doktype;;2;;1-1-1, hidden, nav_hide, title;;3;;2-2-2, subtitle,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access,
					starttime, endtime, fe_group, extendToSubpages,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.options,
					TSconfig;;6;nowrap;4-4-4, storage_pid;;7, l18n_cfg,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.extended,
		');
			// adding doktype 2 ("Advanced")
		$TCA['pages']['types'][2] = array(
			'showitem' =>
					'doktype;;2;;1-1-1, hidden, nav_hide, title;;3;;2-2-2, subtitle, nav_title,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.metadata,
					abstract;;5;;3-3-3, keywords, description,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.files,
					media,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access,
					starttime, endtime, fe_login_mode, fe_group, extendToSubpages,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.options,
					TSconfig;;6;nowrap;6-6-6, storage_pid;;7, l18n_cfg, module, content_from_pid,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.extended,
		');
	}

// ******************************************************************
// This is the standard TypoScript content table, tt_content
// ******************************************************************
$TCA['tt_content'] = array (
	'ctrl' => array (
		'label' => 'header',
		'label_alt' => 'subheader,bodytext',
		'sortby' => 'sorting',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'title' => 'LLL:EXT:cms/locallang_tca.xml:tt_content',
		'delete' => 'deleted',
		'versioningWS' => 2,
		'versioning_followPages' => true,
		'origUid' => 't3_origuid',
		'type' => 'CType',
		'prependAtCopy' => 'LLL:EXT:lang/locallang_general.xml:LGL.prependAtCopy',
		'copyAfterDuplFields' => 'colPos,sys_language_uid',
		'useColumnsForDefaultValues' => 'colPos,sys_language_uid',
		'shadowColumnsForNewPlaceholders' => 'colPos',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'languageField' => 'sys_language_uid',
		'enablecolumns' => array (
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
			'fe_group' => 'fe_group',
		),
		'typeicon_column' => 'CType',
		'typeicon_classes' => array(
			'header' => 'mimetypes-x-content-header',
			'textpic' => 'mimetypes-x-content-text-picture',
			'image' => 'mimetypes-x-content-image',
			'bullets' => 'mimetypes-x-content-list-bullets',
			'table' => 'mimetypes-x-content-table',
			'splash' => 'mimetypes-x-content-splash',
			'uploads' => 'mimetypes-x-content-uploads',
			'multimedia' => 'mimetypes-x-content-multimedia',
			'media' => 'mimetypes-x-content-multimedia',
			'menu' => 'mimetypes-x-content-menu',
			'list' => 'mimetypes-x-content-plugin',
			'mailform' => 'mimetypes-x-content-form',
			'search' => 'mimetypes-x-content-search',
			'login' => 'mimetypes-x-content-login',
			'shortcut' => 'mimetypes-x-content-link',
			'script' => 'mimetypes-x-content-script',
			'div' => 'mimetypes-x-content-divider',
			'html' => 'mimetypes-x-content-html',
			'text' => 'mimetypes-x-content-text',
			'default' => 'mimetypes-x-content-text',
		),
		'typeicons' => array (
			'header' => 'tt_content_header.gif',
			'textpic' => 'tt_content_textpic.gif',
			'image' => 'tt_content_image.gif',
			'bullets' => 'tt_content_bullets.gif',
			'table' => 'tt_content_table.gif',
			'splash' => 'tt_content_news.gif',
			'uploads' => 'tt_content_uploads.gif',
			'multimedia' => 'tt_content_mm.gif',
			'media' => 'tt_content_mm.gif',
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
		'thumbnail' => 'image',
		'requestUpdate' => 'list_type,rte_enabled',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tbl_tt_content.php',
		'dividers2tabs' => 1
	)
);

// ******************************************************************
// fe_users
// ******************************************************************
$TCA['fe_users'] = array (
	'ctrl' => array (
		'label' => 'username',
		'default_sortby' => 'ORDER BY username',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'fe_cruser_id' => 'fe_cruser_id',
		'title' => 'LLL:EXT:cms/locallang_tca.xml:fe_users',
		'delete' => 'deleted',
		'enablecolumns' => array (
			'disabled' => 'disable',
			'starttime' => 'starttime',
			'endtime' => 'endtime'
		),
		'typeicon_classes' => array(
			'default' => 'status-user-frontend',
		),
		'useColumnsForDefaultValues' => 'usergroup,lockToDomain,disable,starttime,endtime',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tbl_cms.php',
		'dividers2tabs' => 1
	),
	'feInterface' => array (
		'fe_admin_fieldList' => 'username,password,usergroup,name,address,telephone,fax,email,title,zip,city,country,www,company',
	)
);

// ******************************************************************
// fe_groups
// ******************************************************************
$TCA['fe_groups'] = array (
	'ctrl' => array (
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'delete' => 'deleted',
		'prependAtCopy' => 'LLL:EXT:lang/locallang_general.xml:LGL.prependAtCopy',
		'enablecolumns' => array (
			'disabled' => 'hidden'
		),
		'title' => 'LLL:EXT:cms/locallang_tca.xml:fe_groups',
		'typeicon_classes' => array(
			'default' => 'status-user-group-frontend',
		),
		'useColumnsForDefaultValues' => 'lockToDomain',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tbl_cms.php',
		'dividers2tabs' => 1
	)
);

// ******************************************************************
// sys_domain
// ******************************************************************
$TCA['sys_domain'] = array (
	'ctrl' => array (
		'label' => 'domainName',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'sortby' => 'sorting',
		'title' => 'LLL:EXT:cms/locallang_tca.xml:sys_domain',
		'iconfile' => 'domain.gif',
		'enablecolumns' => array (
			'disabled' => 'hidden'
		),
		'typeicon_classes' => array(
			'default' => 'mimetypes-x-content-domain',
		),
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tbl_cms.php'
	)
);

// ******************************************************************
// pages_language_overlay
// ******************************************************************
$TCA['pages_language_overlay'] = array (
	'ctrl' => array (
		'label'                           => 'title',
		'tstamp'                          => 'tstamp',
		'title'                           => 'LLL:EXT:cms/locallang_tca.xml:pages_language_overlay',
		'versioningWS'                    => true,
		'versioning_followPages'          => true,
		'origUid'                         => 't3_origuid',
		'crdate'                          => 'crdate',
		'cruser_id'                       => 'cruser_id',
		'delete'                          => 'deleted',
		'enablecolumns'                   => array (
			'disabled'  => 'hidden',
			'starttime' => 'starttime',
			'endtime'   => 'endtime'
		),
		'transOrigPointerField'           => 'pid',
		'transOrigPointerTable'           => 'pages',
		'transOrigDiffSourceField'        => 'l18n_diffsource',
		'shadowColumnsForNewPlaceholders' => 'title',
		'languageField'                   => 'sys_language_uid',
		'mainpalette'                     => 1,
		'dynamicConfigFile'               => t3lib_extMgm::extPath($_EXTKEY) . 'tbl_cms.php',
		'type'                            => 'doktype',
		'typeicon_classes' => array(
			'default' => 'mimetypes-x-content-page-language-overlay',
		),

		'dividers2tabs'                   => true
	)
);


// ******************************************************************
// sys_template
// ******************************************************************
$TCA['sys_template'] = array (
	'ctrl' => array (
		'label' => 'title',
		'tstamp' => 'tstamp',
		'sortby' => 'sorting',
		'prependAtCopy' => 'LLL:EXT:lang/locallang_general.xml:LGL.prependAtCopy',
		'title' => 'LLL:EXT:cms/locallang_tca.xml:sys_template',
		'versioningWS' => true,
		'origUid' => 't3_origuid',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'delete' => 'deleted',
		'adminOnly' => 1,	// Only admin, if any
		'iconfile' => 'template.gif',
		'thumbnail' => 'resources',
		'enablecolumns' => array (
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime'
		),
		'typeicon_column' => 'root',
		'typeicon_classes' => array(
			'default' => 'mimetypes-x-content-template-extension',
			'1' => 'mimetypes-x-content-template',
		),
		'typeicons' => array (
			'0' => 'template_add.gif'
		),
		'dividers2tabs' => 1,
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY).'tbl_cms.php'
	)
);


?>
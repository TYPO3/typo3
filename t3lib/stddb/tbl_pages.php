<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$TCA['pages'] = array(
	'ctrl' => $TCA['pages']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'doktype,title,alias,hidden,starttime,endtime,fe_group,url,target,no_cache,shortcut,keywords,description,abstract,newUntil,lastUpdated,cache_timeout',
		'maxDBListItems' => 30,
		'maxSingleDBListItems' => 50,
	),
	'columns' => array(
		'doktype' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.type',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('LLL:EXT:cms/locallang_tca.xml:pages.doktype.div.page', '--div--'),
					array('LLL:EXT:lang/locallang_tca.php:doktype.I.0', '1', 'i/pages.gif'),
					array('LLL:EXT:cms/locallang_tca.xml:pages.doktype.I.4', '6', 'i/be_users_section.gif'),
					array('LLL:EXT:cms/locallang_tca.xml:pages.doktype.div.link', '--div--'),
					array('LLL:EXT:cms/locallang_tca.xml:pages.doktype.I.2', '4', 'i/pages_shortcut.gif'),
					array('LLL:EXT:cms/locallang_tca.xml:pages.doktype.I.5', '7', 'i/pages_mountpoint.gif'),
					array('LLL:EXT:cms/locallang_tca.xml:pages.doktype.I.8', '3', 'i/pages_link.gif'),
					array('LLL:EXT:cms/locallang_tca.xml:pages.doktype.div.special', '--div--'),
					array('LLL:EXT:lang/locallang_tca.php:doktype.I.1', '254', 'i/sysf.gif'),
					array('LLL:EXT:lang/locallang_tca.php:doktype.I.2', '255', 'i/recycler.gif'),
					array('LLL:EXT:cms/locallang_tca.xml:pages.doktype.I.7', '199', 'i/spacer_icon.gif'),
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
		'url_scheme' => array (
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.url_scheme',
			'config' => array (
				'type' => 'select',
				'items' => array (
					array('', 0),
					array('LLL:EXT:cms/locallang_tca.xml:pages.url_scheme.http', 1),
					array('LLL:EXT:cms/locallang_tca.xml:pages.url_scheme.https', 2)
				),
				'default' => 0
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
					array('LLL:EXT:cms/locallang_tca.xml:pages.shortcut_mode.I.3', 3),
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
	),
	'types' => array(
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
		'),
	),
	'palettes' => array(
		'1' => array('showitem' => 'starttime, endtime, extendToSubpages'),
		'2' => array('showitem' => 'layout, lastUpdated, newUntil, no_search'),
		'3' => array('showitem' => 'alias, target, no_cache, cache_timeout, url_scheme'),
		'5' => array('showitem' => 'author, author_email', 'canNotCollapse' => 1),
		'6' => array('showitem' => 'php_tree_stop, editlock'),
		'7' => array('showitem' => 'is_siteroot'),
	)
);


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

?>
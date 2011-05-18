<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$TCA['pages'] = array(
	'ctrl' => $TCA['pages']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'doktype,title,alias,hidden,starttime,endtime,fe_group,url,target,no_cache,shortcut,keywords,description,abstract,newUntil,lastUpdated,cache_timeout,backend_layout,backend_layout_next_level',
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
					array(
						'LLL:EXT:cms/locallang_tca.xml:pages.doktype.div.page',
						'--div--',
					),
					array(
						'LLL:EXT:lang/locallang_tca.php:doktype.I.0',
						(string) t3lib_pageSelect::DOKTYPE_DEFAULT,
						'i/pages.gif',
					),
					array(
						'LLL:EXT:cms/locallang_tca.xml:pages.doktype.I.4',
						(string) t3lib_pageSelect::DOKTYPE_BE_USER_SECTION,
						'i/be_users_section.gif',
					),
					array(
						'LLL:EXT:cms/locallang_tca.xml:pages.doktype.div.link',
						'--div--',
					),
					array(
						'LLL:EXT:cms/locallang_tca.xml:pages.doktype.I.2',
						(string) t3lib_pageSelect::DOKTYPE_SHORTCUT,
						'i/pages_shortcut.gif',
					),
					array(
						'LLL:EXT:cms/locallang_tca.xml:pages.doktype.I.5',
						(string) t3lib_pageSelect::DOKTYPE_MOUNTPOINT,
						'i/pages_mountpoint.gif',
					),
					array(
						'LLL:EXT:cms/locallang_tca.xml:pages.doktype.I.8',
						(string) t3lib_pageSelect::DOKTYPE_LINK,
						'i/pages_link.gif',
					),
					array(
						'LLL:EXT:cms/locallang_tca.xml:pages.doktype.div.special',
						'--div--',
					),
					array(
						'LLL:EXT:lang/locallang_tca.xml:doktype.I.folder',
						(string) t3lib_pageSelect::DOKTYPE_SYSFOLDER,
						'i/sysf.gif',
					),
					array(
						'LLL:EXT:lang/locallang_tca.xml:doktype.I.2',
						(string) t3lib_pageSelect::DOKTYPE_RECYCLER,
						'i/recycler.gif',
					),
					array(
						'LLL:EXT:cms/locallang_tca.xml:pages.doktype.I.7',
						(string) t3lib_pageSelect::DOKTYPE_SPACER,
						'i/spacer_icon.gif',
					),
				),
				'default' => (string) t3lib_pageSelect::DOKTYPE_DEFAULT,
				'iconsInOptionTags' => 1,
				'noIconsBelowSelect' => 1,
			),
		),
		'title' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.php:title',
			'config' => array(
				'type' => 'input',
				'size' => '50',
				'max' => '255',
				'eval' => 'trim,required',
				'search' => array(
					'nocase'
				),
			),
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
						'type' => t3lib_extMgm::isLoaded('tsconfig_help') ? 'popup' : '',
						'title' => 'TSconfig QuickReference',
						'script' => 'wizard_tsconfig.php?mode=page',
						'icon' => 'wizard_tsconfig.gif',
						'JSopenParams' => 'height=500,width=780,status=0,menubar=0,scrollbars=1',
					),
				),
				'softref' => 'TSconfig',
			),
			'defaultExtras' => 'fixed-font : enable-tab',
		),
		'php_tree_stop' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_tca.php:php_tree_stop',
			'config' => array(
				'type' => 'check',
				'items' => array(
					'1' => array(
						'0' => 'LLL:EXT:lang/locallang_core.xml:labels.enabled',
					),
				),
			),
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
			),
		),
		'tx_impexp_origuid' => array('config' => array('type' => 'passthrough')),
		't3ver_label' => array(
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.versionLabel',
			'config' => array(
				'type' => 'input',
				'size' => '23',
				'max' => '255',
			),
		),
		'editlock' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_tca.php:editlock',
			'config' => array(
				'type' => 'check',
				'items' => array(
					'1' => array(
						'0' => 'LLL:EXT:lang/locallang_core.xml:labels.enabled',
					),
				),
			),
		),
		'hidden' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.hidden',
			'config' => array(
				'type' => 'check',
				'default' => '1',
				'items' => array(
					'1' => array(
						'0' => 'LLL:EXT:cms/locallang_tca.xml:pages.hidden_checkbox_1_formlabel',
					),
				),
			),
		),
		'starttime' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.starttime',
			'config' => array(
				'type' => 'input',
				'size' => '13',
				'max' => '20',
				'eval' => 'date',
				'default' => '0',
			),
		),
		'endtime' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.endtime',
			'config' => array(
				'type' => 'input',
				'size' => '13',
				'max' => '20',
				'eval' => 'date',
				'default' => '0',
				'range' => array(
					'upper' => mktime(0, 0, 0, 12, 31, 2020),
				),
			),
		),
		'layout' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.layout',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'LLL:EXT:lang/locallang_general.xml:LGL.default_value',
						'0',
					),
					array(
						'LLL:EXT:cms/locallang_tca.xml:pages.layout.I.1',
						'1',
					),
					array(
						'LLL:EXT:cms/locallang_tca.xml:pages.layout.I.2',
						'2',
					),
					array(
						'LLL:EXT:cms/locallang_tca.xml:pages.layout.I.3',
						'3',
					),
				),
				'default' => '0',
			),
		),
		'url_scheme' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.url_scheme',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'LLL:EXT:lang/locallang_general.xml:LGL.default_value',
						0,
					),
					array(
						'LLL:EXT:cms/locallang_tca.xml:pages.url_scheme.http',
						1,
					),
					array(
						'LLL:EXT:cms/locallang_tca.xml:pages.url_scheme.https',
						2,
					),
				),
				'default' => 0,
			),
		),
		'fe_group' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.fe_group',
			'config' => array(
				'type' => 'select',
				'size' => 7,
				'maxitems' => 20,
				'items' => array(
					array(
						'LLL:EXT:lang/locallang_general.xml:LGL.hide_at_login',
						-1,
					),
					array(
						'LLL:EXT:lang/locallang_general.xml:LGL.any_login',
						-2,
					),
					array(
						'LLL:EXT:lang/locallang_general.xml:LGL.usergroups',
						'--div--',
					),
				),
				'exclusiveKeys' => '-1,-2',
				'foreign_table' => 'fe_groups',
				'foreign_table_where' => 'ORDER BY fe_groups.title',
			),
		),
		'extendToSubpages' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.extendToSubpages',
			'config' => array(
				'type' => 'check',
				'items' => array(
					'1' => array(
						'0' => 'LLL:EXT:lang/locallang_core.xml:labels.enabled',
					),
				),
			),
		),
		'nav_title' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.nav_title',
			'config' => array(
				'type' => 'input',
				'size' => '50',
				'max' => '255',
				'eval' => 'trim',
				'search' => array(
					'nocase'
				),
			),
		),
		'nav_hide' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.nav_hide',
			'config' => array(
				'type' => 'check',
				'items' => array(
					'1' => array(
						'0' => 'LLL:EXT:cms/locallang_tca.xml:pages.nav_hide_checkbox_1_formlabel',
					),
				),
			),
		),
		'subtitle' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.subtitle',
			'config' => array(
				'type' => 'input',
				'size' => '50',
				'max' => '255',
				'eval' => '',
				'search' => array(
					'nocase'
				),
			),
		),
		'target' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.target',
			'config' => array(
				'type' => 'input',
				'size' => '50',
				'max' => '80',
				'eval' => 'trim',
			),
		),
		'alias' => array(
			'displayCond' => 'VERSION:IS:false',
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.alias',
			'config' => array(
				'type' => 'input',
				'size' => '50',
				'max' => '32',
				'eval' => 'nospace,alphanum_x,lower,unique',
				'softref' => 'notify',
				'search' => array(
					'nocase'
				),
			),
		),
		'url' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.url',
			'config' => array(
				'type' => 'input',
				'size' => '23',
				'max' => '255',
				'eval' => 'trim,required',
				'softref' => 'url',
				'search' => array(
					'nocase'
				),
			),
		),
		'urltype' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.type',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'LLL:EXT:lang/locallang_core.xml:labels.automatic',
						'0',
					),
					array(
						'http://',
						'1',
					),
					array(
						'https://',
						'4',
					),
					array(
						'ftp://',
						'2',
					),
					array(
						'mailto:',
						'3',
					),
				),
				'default' => '1',
			),
		),
		'lastUpdated' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.lastUpdated',
			'config' => array(
				'type' => 'input',
				'size' => '13',
				'max' => '20',
				'eval' => 'datetime',
				'default' => '0',
			),
		),
		'newUntil' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.newUntil',
			'config' => array(
				'type' => 'input',
				'size' => '13',
				'max' => '20',
				'eval' => 'date',
				'default' => '0',
			),
		),
		'cache_timeout' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.cache_timeout',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'LLL:EXT:lang/locallang_general.xml:LGL.default_value',
						0,
					),
					array(
						'LLL:EXT:cms/locallang_tca.xml:pages.cache_timeout.I.1',
						60,
					),
					array(
						'LLL:EXT:cms/locallang_tca.xml:pages.cache_timeout.I.2',
						300,
					),
					array(
						'LLL:EXT:cms/locallang_tca.xml:pages.cache_timeout.I.3',
						900,
					),
					array(
						'LLL:EXT:cms/locallang_tca.xml:pages.cache_timeout.I.4',
						1800,
					),
					array(
						'LLL:EXT:cms/locallang_tca.xml:pages.cache_timeout.I.5',
						3600,
					),
					array(
						'LLL:EXT:cms/locallang_tca.xml:pages.cache_timeout.I.6',
						14400,
					),
					array(
						'LLL:EXT:cms/locallang_tca.xml:pages.cache_timeout.I.7',
						86400,
					),
					array(
						'LLL:EXT:cms/locallang_tca.xml:pages.cache_timeout.I.8',
						172800,
					),
					array(
						'LLL:EXT:cms/locallang_tca.xml:pages.cache_timeout.I.9',
						604800,
					),
					array(
						'LLL:EXT:cms/locallang_tca.xml:pages.cache_timeout.I.10',
						2678400,
					),
				),
				'default' => '0',
			),
		),
		'no_cache' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.no_cache',
			'config' => array(
				'type' => 'check',
				'items' => array(
					'1' => array(
						'0' => 'LLL:EXT:cms/locallang_tca.xml:pages.no_cache_checkbox_1_formlabel',
					),
				),
			),
		),
		'no_search' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.no_search',
			'config' => array(
				'type' => 'check',
				'items' => array(
					'1' => array(
						'0' => 'LLL:EXT:cms/locallang_tca.xml:pages.no_search_checkbox_1_formlabel',
					),
				),
			),
		),
		'shortcut' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.shortcut_page',
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
			),
		),
		'shortcut_mode' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.shortcut_mode',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'LLL:EXT:cms/locallang_tca.xml:pages.shortcut_mode.I.0',
						t3lib_pageSelect::SHORTCUT_MODE_NONE,
					),
					array(
						'LLL:EXT:cms/locallang_tca.xml:pages.shortcut_mode.I.1',
						t3lib_pageSelect::SHORTCUT_MODE_FIRST_SUBPAGE,
					),
					array(
						'LLL:EXT:cms/locallang_tca.xml:pages.shortcut_mode.I.2',
						t3lib_pageSelect::SHORTCUT_MODE_RANDOM_SUBPAGE,
					),
					array(
						'LLL:EXT:cms/locallang_tca.xml:pages.shortcut_mode.I.3',
						t3lib_pageSelect::SHORTCUT_MODE_PARENT_PAGE,
					),
				),
				'default' => '0',
			),
		),
		'content_from_pid' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.content_from_pid',
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
			),
		),
		'mount_pid' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.mount_pid',
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
			),
		),
		'keywords' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.keywords',
			'config' => array(
				'type' => 'text',
				'cols' => '40',
				'rows' => '3',
				'search' => array(
					'nocase'
				),
			),
		),
		'description' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.description',
			'config' => array(
				'type' => 'text',
				'cols' => '40',
				'rows' => '3',
				'search' => array(
					'nocase'
				),
			),
		),
		'abstract' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.abstract',
			'config' => array(
				'type' => 'text',
				'cols' => '40',
				'rows' => '3',
				'search' => array(
					'nocase'
				),
			),
		),
		'author' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.author',
			'config' => array(
				'type' => 'input',
				'size' => '23',
				'eval' => 'trim',
				'max' => '80',
				'search' => array(
					'nocase'
				),
			),
		),
		'author_email' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.email',
			'config' => array(
				'type' => 'input',
				'size' => '23',
				'eval' => 'trim',
				'max' => '80',
				'softref' => 'email[subst]',
				'search' => array(
					'nocase'
				),
			),
		),
		'media' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.media',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] . ',html,htm,ttf,txt,css',
				'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
				'uploadfolder' => 'uploads/media',
				'show_thumbs' => '1',
				'size' => '3',
				'maxitems' => '5',
				'minitems' => '0',
			),
		),
		'is_siteroot' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.is_siteroot',
			'config' => array(
				'type' => 'check',
				'items' => array(
					'1' => array(
						'0' => 'LLL:EXT:lang/locallang_core.xml:labels.enabled'
					),
				),
			),
		),
		'mount_pid_ol' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.mount_pid_ol',
			'config' => array(
				'type' => 'radio',
				'items' => array(
					array(
						'LLL:EXT:cms/locallang_tca.xml:pages.mount_pid_ol.I.0',0
					),
					array(
						'LLL:EXT:cms/locallang_tca.xml:pages.mount_pid_ol.I.1',1
					),
				),
			),
		),
		'module' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.module',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'',
						'',
						'',
					),
					array(
						'LLL:EXT:cms/locallang_tca.xml:pages.module.I.1',
						'shop',
						'i/modules_shop.gif',
					),
					array(
						'LLL:EXT:cms/locallang_tca.xml:pages.module.I.2',
						'board',
						'i/modules_board.gif',
					),
					array(
						'LLL:EXT:cms/locallang_tca.xml:pages.module.I.3',
						'news',
						'i/modules_news.gif',
					),
					array(
						'LLL:EXT:cms/locallang_tca.xml:pages.module.I.4',
						'fe_users',
						'i/fe_users.gif',
					),
					array(
						'LLL:EXT:cms/locallang_tca.xml:pages.module.I.6',
						'approve',
						'state_checked.png',
					),
				),
				'default' => '',
				'iconsInOptionTags' => 1,
				'noIconsBelowSelect' => 1,
			),
		),
		'fe_login_mode' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.fe_login_mode',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'LLL:EXT:cms/locallang_tca.xml:pages.fe_login_mode.enable',
						0,
					),
					array(
						'LLL:EXT:cms/locallang_tca.xml:pages.fe_login_mode.disableAll',
						1,
					),
					array(
						'LLL:EXT:cms/locallang_tca.xml:pages.fe_login_mode.disableGroups',
						3,
					),
					array(
						'LLL:EXT:cms/locallang_tca.xml:pages.fe_login_mode.enableAgain',
						2,
					),
				),
			),
		),
		'l18n_cfg' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.l18n_cfg',
			'config' => array(
				'type' => 'check',
				'items' => array(
					array(
						'LLL:EXT:cms/locallang_tca.xml:pages.l18n_cfg.I.1',
						'',
					),
					array(
						$GLOBALS['TYPO3_CONF_VARS']['FE']['hidePagesIfNotTranslatedByDefault'] ?
								'LLL:EXT:cms/locallang_tca.xml:pages.l18n_cfg.I.2a' :
								'LLL:EXT:cms/locallang_tca.xml:pages.l18n_cfg.I.2',
						'',
					),
				),
			),
		),
		'backend_layout' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.backend_layout',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'backend_layout',
				'foreign_table_where' => 'AND ( ( ###PAGE_TSCONFIG_ID### = 0 AND ###STORAGE_PID### = 0 ) OR ( backend_layout.pid = ###PAGE_TSCONFIG_ID### OR backend_layout.pid = ###STORAGE_PID### ) OR ( ###PAGE_TSCONFIG_ID### = 0 AND backend_layout.pid = ###THIS_UID### ) ) AND backend_layout.hidden = 0',
				'items' => array(
					array('', 0),
					array('LLL:EXT:cms/locallang_tca.xml:pages.backend_layout.none', -1)
				),
				'selicon_cols' => 5,
				'size' => 1,
				'maxitems' => 1,
				'default' => ''
			)
		),
		'backend_layout_next_level' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xml:pages.backend_layout_next_level',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'backend_layout',
				'foreign_table_where' => 'AND ( ( ###PAGE_TSCONFIG_ID### = 0 AND ###STORAGE_PID### = 0 ) OR ( backend_layout.pid = ###PAGE_TSCONFIG_ID### OR backend_layout.pid = ###STORAGE_PID### ) OR ( ###PAGE_TSCONFIG_ID### = 0 AND backend_layout.pid = ###THIS_UID### ) ) AND backend_layout.hidden = 0',
				'items' => array(
					array('', 0),
					array('LLL:EXT:cms/locallang_tca.xml:pages.backend_layout.none', -1)
				),
				'selicon_cols' => 5,
				'size' => 1,
				'maxitems' => 1,
				'default' => ''
			)
		)
	),
	'types' => array(
		// normal
		(string) t3lib_pageSelect::DOKTYPE_DEFAULT => array(
			'showitem' =>
			'--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.standard;standard,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.title;title,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.access;access,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.metadata,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.abstract;abstract,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.metatags;metatags,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.editorial;editorial,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.layout;layout,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.module;module,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.replace;replace,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.behaviour,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.links;links,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.caching;caching,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.language;language,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.miscellaneous;miscellaneous,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.resources,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.media;media,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.storage;storage,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.config;config,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.extended,
		'),
		// external URL
		(string) t3lib_pageSelect::DOKTYPE_LINK => array(
			'showitem' =>
			'--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.external;external,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.title;title,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.access;access,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.metadata,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.abstract;abstract,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.editorial;editorial,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.layout;layout,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.behaviour,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.links;links,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.language;language,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.miscellaneous;miscellaneous,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.resources,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.media;media,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.storage;storage,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.config;config,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.extended,
		'),
		// shortcut
		(string) t3lib_pageSelect::DOKTYPE_SHORTCUT => array(
			'showitem' =>
			'--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.shortcut;shortcut,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.shortcutpage;shortcutpage,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.title;title,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.access;access,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.metadata,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.abstract;abstract,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.editorial;editorial,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.layout;layout,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.behaviour,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.links;links,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.language;language,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.miscellaneous;miscellaneous,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.resources,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.media;media,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.storage;storage,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.config;config,
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
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.layout;layout,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.module;module,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.replace;replace,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.behaviour,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.links;links,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.caching;caching,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.language;language,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.miscellaneous;miscellaneous,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.resources,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.media;media,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.storage;storage,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.config;config,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.extended,
		'),
		// mount page
		(string) t3lib_pageSelect::DOKTYPE_MOUNTPOINT => array(
			'showitem' =>
			'--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.mountpoint;mountpoint,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.mountpage;mountpage,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.title;title,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.access;access,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.metadata,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.abstract;abstract,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.editorial;editorial,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.layout;layout,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.behaviour,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.links;links,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.language;language,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.miscellaneous;miscellaneous,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.resources,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.media;media,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.config;config,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.extended,
		'),
		// spacer
		(string) t3lib_pageSelect::DOKTYPE_SPACER => array(
			'showitem' =>
			'--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.standard;standard,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.title;titleonly,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.access;access,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.behaviour,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.miscellaneous;adminsonly,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.extended,
			'),
		// Folder
		(string) t3lib_pageSelect::DOKTYPE_SYSFOLDER => array(
			'showitem' =>
			'--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.standard;standard,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.title;titleonly,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.visibility;hiddenonly,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.module;module,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.behaviour,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.miscellaneous;adminsonly,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.resources,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.media;media,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.storage;storage,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.config;config,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.extended,
		'),
		// trash
		(string) t3lib_pageSelect::DOKTYPE_RECYCLER => array(
			'showitem' =>
			'--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.standard;standard,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.title;titleonly,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.visibility;hiddenonly,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.behaviour,
					--palette--;LLL:EXT:cms/locallang_tca.xml:pages.palettes.miscellaneous;adminsonly,
				--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.extended,
		'),
	),
	'palettes' => array(
		'1' => array(
			'showitem' => 'starttime, endtime, extendToSubpages',
		),
		'2' => array(
			'showitem' => 'layout, lastUpdated, newUntil, no_search',
		),
		'3' => array(
			'showitem' => 'alias, target, no_cache, cache_timeout, url_scheme',
		),
		'5' => array(
			'showitem' => 'author, author_email', 'canNotCollapse' => 1,
		),
		'6' => array(
			'showitem' => 'php_tree_stop, editlock',
		),
		'7' => array(
			'showitem' => 'is_siteroot',
		),
		'8' => array(
			'showitem' => 'backend_layout_next_level'
		),
		'standard' => array(
			'showitem' => 'doktype;LLL:EXT:cms/locallang_tca.xml:pages.doktype_formlabel',
			'canNotCollapse' => 1,
		),
		'shortcut' => array(
			'showitem' => 'doktype;LLL:EXT:cms/locallang_tca.xml:pages.doktype_formlabel, shortcut_mode;LLL:EXT:cms/locallang_tca.xml:pages.shortcut_mode_formlabel',
			'canNotCollapse' => 1,
		),
		'shortcutpage' => array(
			'showitem' => 'shortcut;LLL:EXT:cms/locallang_tca.xml:pages.shortcut_formlabel',
			'canNotCollapse' => 1,
		),
		'mountpoint' => array(
			'showitem' => 'doktype;LLL:EXT:cms/locallang_tca.xml:pages.doktype_formlabel, mount_pid_ol;LLL:EXT:cms/locallang_tca.xml:pages.mount_pid_ol_formlabel',
			'canNotCollapse' => 1,
		),
		'mountpage' => array(
			'showitem' => 'mount_pid;LLL:EXT:cms/locallang_tca.xml:pages.mount_pid_formlabel',
			'canNotCollapse' => 1,
		),
		'external' => array(
			'showitem' => 'doktype;LLL:EXT:cms/locallang_tca.xml:pages.doktype_formlabel, urltype;LLL:EXT:cms/locallang_tca.xml:pages.urltype_formlabel, url;LLL:EXT:cms/locallang_tca.xml:pages.url_formlabel',
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
		'visibility' => array(
			'showitem' => 'hidden;LLL:EXT:cms/locallang_tca.xml:pages.hidden_formlabel, nav_hide;LLL:EXT:cms/locallang_tca.xml:pages.nav_hide_formlabel',
			'canNotCollapse' => 1,
		),
		'hiddenonly' => array(
			'showitem' => 'hidden;LLL:EXT:cms/locallang_tca.xml:pages.hidden_formlabel',
			'canNotCollapse' => 1,
		),
		'access' => array(
			'showitem' => 'starttime;LLL:EXT:cms/locallang_tca.xml:pages.starttime_formlabel, endtime;LLL:EXT:cms/locallang_tca.xml:pages.endtime_formlabel, extendToSubpages;LLL:EXT:cms/locallang_tca.xml:pages.extendToSubpages_formlabel, --linebreak--, fe_group;LLL:EXT:cms/locallang_tca.xml:pages.fe_group_formlabel, --linebreak--, fe_login_mode;LLL:EXT:cms/locallang_tca.xml:pages.fe_login_mode_formlabel',
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
			'showitem' => 'author;LLL:EXT:cms/locallang_tca.xml:pages.author_formlabel, author_email;LLL:EXT:cms/locallang_tca.xml:pages.author_email_formlabel, lastUpdated;LLL:EXT:cms/locallang_tca.xml:pages.lastUpdated_formlabel',
			'canNotCollapse' => 1,
		),
		'layout' => array(
			'showitem' => 'layout;LLL:EXT:cms/locallang_tca.xml:pages.layout_formlabel, newUntil;LLL:EXT:cms/locallang_tca.xml:pages.newUntil_formlabel, --linebreak--, backend_layout;LLL:EXT:cms/locallang_tca.xml:pages.backend_layout_formlabel, backend_layout_next_level;LLL:EXT:cms/locallang_tca.xml:pages.backend_layout_next_level_formlabel',
			'canNotCollapse' => 1,
		),
		'module' => array(
			'showitem' => 'module;LLL:EXT:cms/locallang_tca.xml:pages.module_formlabel',
			'canNotCollapse' => 1,
		),
		'replace' => array(
			'showitem' => 'content_from_pid;LLL:EXT:cms/locallang_tca.xml:pages.content_from_pid_formlabel',
			'canNotCollapse' => 1,
		),
		'links' => array(
			'showitem' => 'alias;LLL:EXT:cms/locallang_tca.xml:pages.alias_formlabel, --linebreak--, target;LLL:EXT:cms/locallang_tca.xml:pages.target_formlabel, --linebreak--, url_scheme;LLL:EXT:cms/locallang_tca.xml:pages.url_scheme_formlabel',
			'canNotCollapse' => 1,
		),
		'caching' => array(
			'showitem' => 'cache_timeout;LLL:EXT:cms/locallang_tca.xml:pages.cache_timeout_formlabel, no_cache;LLL:EXT:cms/locallang_tca.xml:pages.no_cache_formlabel',
			'canNotCollapse' => 1,
		),
		'language' => array(
			'showitem' => 'l18n_cfg;LLL:EXT:cms/locallang_tca.xml:pages.l18n_cfg_formlabel',
			'canNotCollapse' => 1,
		),
		'miscellaneous' => array(
			'showitem' => 'is_siteroot;LLL:EXT:cms/locallang_tca.xml:pages.is_siteroot_formlabel, no_search;LLL:EXT:cms/locallang_tca.xml:pages.no_search_formlabel, editlock;LLL:EXT:cms/locallang_tca.xml:pages.editlock_formlabel, php_tree_stop;LLL:EXT:cms/locallang_tca.xml:pages.php_tree_stop_formlabel',
			'canNotCollapse' => 1,
		),
		'adminsonly' => array(
			'showitem' => 'editlock;LLL:EXT:cms/locallang_tca.xml:pages.editlock_formlabel',
			'canNotCollapse' => 1,
		),
		'media' => array(
			'showitem' => 'media;LLL:EXT:cms/locallang_tca.xml:pages.media_formlabel',
			'canNotCollapse' => 1,
		),
		'storage' => array(
			'showitem' => 'storage_pid;LLL:EXT:cms/locallang_tca.xml:pages.storage_pid_formlabel',
			'canNotCollapse' => 1,
		),
		'config' => array(
			'showitem' => 'TSconfig;LLL:EXT:cms/locallang_tca.xml:pages.TSconfig_formlabel',
			'canNotCollapse' => 1,
		)
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
			 array(
				 'LLL:EXT:cms/locallang_tca.xml:pages.doktype.I.0',
				 '2',
				 'i/pages.gif',
			 ),
			 array(
				 'LLL:EXT:cms/locallang_tca.xml:pages.doktype.I.3',
				 '5',
				 'i/pages_notinmenu.gif',
			 ),
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
				TSconfig;;6;nowrap;4-4-4, storage_pid;;7, l18n_cfg, backend_layout;;8,
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
				TSconfig;;6;nowrap;6-6-6, storage_pid;;7, l18n_cfg, module, content_from_pid, backend_layout;;8,
			--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.extended,
	');
}

?>
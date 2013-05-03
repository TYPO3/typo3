<?php
return array(
	'ctrl' => array(
		'label' => 'title',
		'tstamp' => 'tstamp',
		'sortby' => 'sorting',
		'title' => 'LLL:EXT:lang/locallang_tca.xlf:pages',
		'type' => 'doktype',
		'versioningWS' => 2,
		'origUid' => 't3_origuid',
		'delete' => 'deleted',
		'crdate' => 'crdate',
		'hideAtCopy' => 1,
		'prependAtCopy' => 'LLL:EXT:lang/locallang_general.xlf:LGL.prependAtCopy',
		'cruser_id' => 'cruser_id',
		'editlock' => 'editlock',
		'useColumnsForDefaultValues' => 'doktype,fe_group,hidden',
		'dividers2tabs' => 1,
		'enablecolumns' => array(
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
			'fe_group' => 'fe_group'
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
			'default' => 'apps-pagetree-page-default'
		),
		'typeicons' => array(
			'1' => 'pages.gif',
			'254' => 'sysf.gif',
			'255' => 'recycler.gif'
		),
		'searchFields' => 'title,alias,nav_title,subtitle,url,keywords,description,abstract,author,author_email'
	),
	'interface' => array(
		'showRecordFieldList' => 'doktype,title,alias,hidden,starttime,endtime,fe_group,url,target,no_cache,shortcut,keywords,description,abstract,newUntil,lastUpdated,cache_timeout,cache_tags,backend_layout,backend_layout_next_level',
		'maxDBListItems' => 30,
		'maxSingleDBListItems' => 50
	),
	'columns' => array(
		'doktype' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.type',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'LLL:EXT:cms/locallang_tca.xlf:pages.doktype.div.page',
						'--div--'
					),
					array(
						'LLL:EXT:lang/locallang_tca.xlf:doktype.I.0',
						(string) \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_DEFAULT,
						'i/pages.gif'
					),
					array(
						'LLL:EXT:cms/locallang_tca.xlf:pages.doktype.I.4',
						(string) \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_BE_USER_SECTION,
						'i/be_users_section.gif'
					),
					array(
						'LLL:EXT:cms/locallang_tca.xlf:pages.doktype.div.link',
						'--div--'
					),
					array(
						'LLL:EXT:cms/locallang_tca.xlf:pages.doktype.I.2',
						(string) \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_SHORTCUT,
						'i/pages_shortcut.gif'
					),
					array(
						'LLL:EXT:cms/locallang_tca.xlf:pages.doktype.I.5',
						(string) \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_MOUNTPOINT,
						'i/pages_mountpoint.gif'
					),
					array(
						'LLL:EXT:cms/locallang_tca.xlf:pages.doktype.I.8',
						(string) \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_LINK,
						'i/pages_link.gif'
					),
					array(
						'LLL:EXT:cms/locallang_tca.xlf:pages.doktype.div.special',
						'--div--'
					),
					array(
						'LLL:EXT:lang/locallang_tca.xlf:doktype.I.folder',
						(string) \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_SYSFOLDER,
						'i/sysf.gif'
					),
					array(
						'LLL:EXT:lang/locallang_tca.xlf:doktype.I.2',
						(string) \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_RECYCLER,
						'i/recycler.gif'
					),
					array(
						'LLL:EXT:cms/locallang_tca.xlf:pages.doktype.I.7',
						(string) \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_SPACER,
						'i/spacer_icon.gif'
					)
				),
				'default' => (string) \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_DEFAULT,
				'iconsInOptionTags' => 1,
				'noIconsBelowSelect' => 1
			)
		),
		'title' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:title',
			'config' => array(
				'type' => 'input',
				'size' => '50',
				'max' => '255',
				'eval' => 'trim,required'
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
						'type' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('tsconfig_help') ? 'popup' : '',
						'title' => 'TSconfig QuickReference',
						'script' => 'wizard_tsconfig.php?mode=page',
						'icon' => 'wizard_tsconfig.gif',
						'JSopenParams' => 'height=500,width=780,status=0,menubar=0,scrollbars=1'
					)
				),
				'softref' => 'TSconfig'
			),
			'defaultExtras' => 'fixed-font : enable-tab'
		),
		'php_tree_stop' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:php_tree_stop',
			'config' => array(
				'type' => 'check',
				'items' => array(
					'1' => array(
						'0' => 'LLL:EXT:lang/locallang_core.xlf:labels.enabled'
					)
				)
			)
		),
		'storage_pid' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:storage_pid',
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
						'type' => 'suggest'
					)
				)
			)
		),
		'TYPO3\\CMS\\Impexp\\ImportExport_origuid' => array('config' => array('type' => 'passthrough')),
		't3ver_label' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.versionLabel',
			'config' => array(
				'type' => 'input',
				'size' => '23',
				'max' => '255'
			)
		),
		'editlock' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:editlock',
			'config' => array(
				'type' => 'check',
				'items' => array(
					'1' => array(
						'0' => 'LLL:EXT:lang/locallang_core.xlf:labels.enabled'
					)
				)
			)
		),
		'hidden' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:pages.hidden',
			'config' => array(
				'type' => 'check',
				'default' => '1',
				'items' => array(
					'1' => array(
						'0' => 'LLL:EXT:cms/locallang_tca.xlf:pages.hidden_checkbox_1_formlabel'
					)
				)
			)
		),
		'starttime' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.starttime',
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
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.endtime',
			'config' => array(
				'type' => 'input',
				'size' => '13',
				'max' => '20',
				'eval' => 'datetime',
				'default' => '0',
				'range' => array(
					'upper' => mktime(0, 0, 0, 1, 1, 2038)
				)
			)
		),
		'layout' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.layout',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'LLL:EXT:lang/locallang_general.xlf:LGL.default_value',
						'0'
					),
					array(
						'LLL:EXT:cms/locallang_tca.xlf:pages.layout.I.1',
						'1'
					),
					array(
						'LLL:EXT:cms/locallang_tca.xlf:pages.layout.I.2',
						'2'
					),
					array(
						'LLL:EXT:cms/locallang_tca.xlf:pages.layout.I.3',
						'3'
					)
				),
				'default' => '0'
			)
		),
		'url_scheme' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:pages.url_scheme',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'LLL:EXT:lang/locallang_general.xlf:LGL.default_value',
						0
					),
					array(
						'LLL:EXT:cms/locallang_tca.xlf:pages.url_scheme.http',
						1
					),
					array(
						'LLL:EXT:cms/locallang_tca.xlf:pages.url_scheme.https',
						2
					)
				),
				'default' => 0
			)
		),
		'fe_group' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.fe_group',
			'config' => array(
				'type' => 'select',
				'size' => 7,
				'maxitems' => 20,
				'items' => array(
					array(
						'LLL:EXT:lang/locallang_general.xlf:LGL.hide_at_login',
						-1
					),
					array(
						'LLL:EXT:lang/locallang_general.xlf:LGL.any_login',
						-2
					),
					array(
						'LLL:EXT:lang/locallang_general.xlf:LGL.usergroups',
						'--div--'
					)
				),
				'exclusiveKeys' => '-1,-2',
				'foreign_table' => 'fe_groups',
				'foreign_table_where' => 'ORDER BY fe_groups.title'
			)
		),
		'extendToSubpages' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:pages.extendToSubpages',
			'config' => array(
				'type' => 'check',
				'items' => array(
					'1' => array(
						'0' => 'LLL:EXT:lang/locallang_core.xlf:labels.enabled'
					)
				)
			)
		),
		'nav_title' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:pages.nav_title',
			'config' => array(
				'type' => 'input',
				'size' => '50',
				'max' => '255',
				'eval' => 'trim'
			)
		),
		'nav_hide' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:pages.nav_hide',
			'config' => array(
				'type' => 'check',
				'items' => array(
					'1' => array(
						'0' => 'LLL:EXT:cms/locallang_tca.xlf:pages.nav_hide_checkbox_1_formlabel'
					)
				)
			)
		),
		'subtitle' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:pages.subtitle',
			'config' => array(
				'type' => 'input',
				'size' => '50',
				'max' => '255',
				'eval' => ''
			)
		),
		'target' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:pages.target',
			'config' => array(
				'type' => 'input',
				'size' => '50',
				'max' => '80',
				'eval' => 'trim'
			)
		),
		'alias' => array(
			'exclude' => 1,
			'displayCond' => 'VERSION:IS:false',
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:pages.alias',
			'config' => array(
				'type' => 'input',
				'size' => '50',
				'max' => '32',
				'eval' => 'nospace,alphanum_x,lower,unique',
				'softref' => 'notify'
			)
		),
		'url' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:pages.url',
			'config' => array(
				'type' => 'input',
				'size' => '23',
				'max' => '255',
				'eval' => 'trim,required',
				'softref' => 'url'
			)
		),
		'urltype' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.type',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'LLL:EXT:lang/locallang_core.xlf:labels.automatic',
						'0'
					),
					array(
						'http://',
						'1'
					),
					array(
						'https://',
						'4'
					),
					array(
						'ftp://',
						'2'
					),
					array(
						'mailto:',
						'3'
					)
				),
				'default' => '1'
			)
		),
		'lastUpdated' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:pages.lastUpdated',
			'config' => array(
				'type' => 'input',
				'size' => '13',
				'max' => '20',
				'eval' => 'datetime',
				'default' => '0'
			)
		),
		'newUntil' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:pages.newUntil',
			'config' => array(
				'type' => 'input',
				'size' => '13',
				'max' => '20',
				'eval' => 'date',
				'default' => '0'
			)
		),
		'cache_timeout' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:pages.cache_timeout',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'LLL:EXT:lang/locallang_general.xlf:LGL.default_value',
						0
					),
					array(
						'LLL:EXT:cms/locallang_tca.xlf:pages.cache_timeout.I.1',
						60
					),
					array(
						'LLL:EXT:cms/locallang_tca.xlf:pages.cache_timeout.I.2',
						300
					),
					array(
						'LLL:EXT:cms/locallang_tca.xlf:pages.cache_timeout.I.3',
						900
					),
					array(
						'LLL:EXT:cms/locallang_tca.xlf:pages.cache_timeout.I.4',
						1800
					),
					array(
						'LLL:EXT:cms/locallang_tca.xlf:pages.cache_timeout.I.5',
						3600
					),
					array(
						'LLL:EXT:cms/locallang_tca.xlf:pages.cache_timeout.I.6',
						14400
					),
					array(
						'LLL:EXT:cms/locallang_tca.xlf:pages.cache_timeout.I.7',
						86400
					),
					array(
						'LLL:EXT:cms/locallang_tca.xlf:pages.cache_timeout.I.8',
						172800
					),
					array(
						'LLL:EXT:cms/locallang_tca.xlf:pages.cache_timeout.I.9',
						604800
					),
					array(
						'LLL:EXT:cms/locallang_tca.xlf:pages.cache_timeout.I.10',
						2678400
					)
				),
				'default' => '0'
			)
		),
		'cache_tags' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:pages.cache_tags',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'max' => '255',
				'eval' => ''
			)
		),
		'no_cache' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:pages.no_cache',
			'config' => array(
				'type' => 'check',
				'items' => array(
					'1' => array(
						'0' => 'LLL:EXT:cms/locallang_tca.xlf:pages.no_cache_checkbox_1_formlabel'
					)
				)
			)
		),
		'no_search' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:pages.no_search',
			'config' => array(
				'type' => 'check',
				'items' => array(
					'1' => array(
						'0' => 'LLL:EXT:cms/locallang_tca.xlf:pages.no_search_checkbox_1_formlabel'
					)
				)
			)
		),
		'shortcut' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.shortcut_page',
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
						'type' => 'suggest'
					)
				)
			)
		),
		'shortcut_mode' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:pages.shortcut_mode',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'LLL:EXT:cms/locallang_tca.xlf:pages.shortcut_mode.I.0',
						\TYPO3\CMS\Frontend\Page\PageRepository::SHORTCUT_MODE_NONE
					),
					array(
						'LLL:EXT:cms/locallang_tca.xlf:pages.shortcut_mode.I.1',
						\TYPO3\CMS\Frontend\Page\PageRepository::SHORTCUT_MODE_FIRST_SUBPAGE
					),
					array(
						'LLL:EXT:cms/locallang_tca.xlf:pages.shortcut_mode.I.2',
						\TYPO3\CMS\Frontend\Page\PageRepository::SHORTCUT_MODE_RANDOM_SUBPAGE
					),
					array(
						'LLL:EXT:cms/locallang_tca.xlf:pages.shortcut_mode.I.3',
						\TYPO3\CMS\Frontend\Page\PageRepository::SHORTCUT_MODE_PARENT_PAGE
					)
				),
				'default' => '0'
			)
		),
		'content_from_pid' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:pages.content_from_pid',
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
						'type' => 'suggest'
					)
				)
			)
		),
		'mount_pid' => array(
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:pages.mount_pid',
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
						'type' => 'suggest'
					)
				)
			)
		),
		'keywords' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.keywords',
			'config' => array(
				'type' => 'text',
				'cols' => '40',
				'rows' => '3'
			)
		),
		'description' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.description',
			'config' => array(
				'type' => 'text',
				'cols' => '40',
				'rows' => '3'
			)
		),
		'abstract' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:pages.abstract',
			'config' => array(
				'type' => 'text',
				'cols' => '40',
				'rows' => '3'
			)
		),
		'author' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.author',
			'config' => array(
				'type' => 'input',
				'size' => '23',
				'eval' => 'trim',
				'max' => '80'
			)
		),
		'author_email' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.email',
			'config' => array(
				'type' => 'input',
				'size' => '23',
				'eval' => 'trim',
				'max' => '80',
				'softref' => 'email[subst]'
			)
		),
		'media' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:pages.media',
			'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig('media')
		),
		'is_siteroot' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:pages.is_siteroot',
			'config' => array(
				'type' => 'check',
				'items' => array(
					'1' => array(
						'0' => 'LLL:EXT:lang/locallang_core.xlf:labels.enabled'
					)
				)
			)
		),
		'mount_pid_ol' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:pages.mount_pid_ol',
			'config' => array(
				'type' => 'radio',
				'items' => array(
					array(
						'LLL:EXT:cms/locallang_tca.xlf:pages.mount_pid_ol.I.0',
						0
					),
					array(
						'LLL:EXT:cms/locallang_tca.xlf:pages.mount_pid_ol.I.1',
						1
					)
				)
			)
		),
		'module' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:pages.module',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'',
						'',
						''
					),
					array(
						'LLL:EXT:cms/locallang_tca.xlf:pages.module.I.4',
						'fe_users',
						'i/fe_users.gif'
					)
				),
				'default' => '',
				'iconsInOptionTags' => 1,
				'noIconsBelowSelect' => 1
			)
		),
		'fe_login_mode' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:pages.fe_login_mode',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'LLL:EXT:cms/locallang_tca.xlf:pages.fe_login_mode.enable',
						0
					),
					array(
						'LLL:EXT:cms/locallang_tca.xlf:pages.fe_login_mode.disableAll',
						1
					),
					array(
						'LLL:EXT:cms/locallang_tca.xlf:pages.fe_login_mode.disableGroups',
						3
					),
					array(
						'LLL:EXT:cms/locallang_tca.xlf:pages.fe_login_mode.enableAgain',
						2
					)
				)
			)
		),
		'l18n_cfg' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:pages.l18n_cfg',
			'config' => array(
				'type' => 'check',
				'items' => array(
					array(
						'LLL:EXT:cms/locallang_tca.xlf:pages.l18n_cfg.I.1',
						''
					),
					array(
						$GLOBALS['TYPO3_CONF_VARS']['FE']['hidePagesIfNotTranslatedByDefault'] ? 'LLL:EXT:cms/locallang_tca.xlf:pages.l18n_cfg.I.2a' : 'LLL:EXT:cms/locallang_tca.xlf:pages.l18n_cfg.I.2',
						''
					)
				)
			)
		),
		'backend_layout' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:pages.backend_layout_formlabel',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'backend_layout',
				'foreign_table_where' => 'AND ( ( ###PAGE_TSCONFIG_ID### = 0 AND ###STORAGE_PID### = 0 ) OR ( backend_layout.pid = ###PAGE_TSCONFIG_ID### OR backend_layout.pid = ###STORAGE_PID### ) OR ( ###PAGE_TSCONFIG_ID### = 0 AND backend_layout.pid = ###THIS_UID### ) ) AND backend_layout.hidden = 0 ORDER BY backend_layout.sorting',
				'items' => array(
					array('', 0),
					array('LLL:EXT:cms/locallang_tca.xlf:pages.backend_layout.none', -1)
				),
				'selicon_cols' => 5,
				'size' => 1,
				'maxitems' => 1,
				'default' => ''
			)
		),
		'backend_layout_next_level' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:pages.backend_layout_next_level_formlabel',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'backend_layout',
				'foreign_table_where' => 'AND ( ( ###PAGE_TSCONFIG_ID### = 0 AND ###STORAGE_PID### = 0 ) OR ( backend_layout.pid = ###PAGE_TSCONFIG_ID### OR backend_layout.pid = ###STORAGE_PID### ) OR ( ###PAGE_TSCONFIG_ID### = 0 AND backend_layout.pid = ###THIS_UID### ) ) AND backend_layout.hidden = 0 ORDER BY backend_layout.sorting',
				'items' => array(
					array('', 0),
					array('LLL:EXT:cms/locallang_tca.xlf:pages.backend_layout.none', -1)
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
		(string) \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_DEFAULT => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.standard;standard,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.title;title,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.access,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.access;access,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.metadata,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.abstract;abstract,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.metatags;metatags,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.editorial;editorial,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.layout;layout,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.replace;replace,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.behaviour,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.links;links,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.caching;caching,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.language;language,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.miscellaneous;miscellaneous,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.module;module,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.resources,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.media;media,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.storage;storage,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.config;config,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.extended,
		'
		),
		// external URL
		(string) \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_LINK => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.external;external,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.title;title,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.access,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.access;access,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.metadata,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.abstract;abstract,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.editorial;editorial,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.layout;layout,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.behaviour,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.links;links,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.language;language,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.miscellaneous;miscellaneous,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.resources,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.media;media,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.storage;storage,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.config;config,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.extended,
		'
		),
		// shortcut
		(string) \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_SHORTCUT => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.shortcut;shortcut,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.shortcutpage;shortcutpage,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.title;title,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.access,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.access;access,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.metadata,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.abstract;abstract,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.editorial;editorial,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.layout;layout,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.behaviour,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.links;links,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.language;language,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.miscellaneous;miscellaneous,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.resources,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.media;media,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.storage;storage,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.config;config,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.extended,
				'
		),
		// mount page
		(string) \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_MOUNTPOINT => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.mountpoint;mountpoint,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.mountpage;mountpage,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.title;title,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.access,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.access;access,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.metadata,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.abstract;abstract,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.editorial;editorial,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.layout;layout,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.behaviour,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.links;links,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.language;language,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.miscellaneous;miscellaneous,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.resources,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.media;media,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.config;config,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.extended,
		'
		),
		// spacer
		(string) \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_SPACER => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.standard;standard,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.title;titleonly,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.access,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.access;access,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.behaviour,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.miscellaneous;adminsonly,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.extended,
			'
		),
		// Folder
		(string) \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_SYSFOLDER => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.standard;standard,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.title;titleonly,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.access,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.visibility;hiddenonly,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.behaviour,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.miscellaneous;adminsonly,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.module;module,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.resources,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.media;media,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.storage;storage,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.config;config,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.extended,
		'
		),
		// Trash
		(string) \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_RECYCLER => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.standard;standard,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.title;titleonly,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.access,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.visibility;hiddenonly,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.behaviour,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.miscellaneous;adminsonly,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.extended,
		'
		)
	),
	'palettes' => array(
		'1' => array(
			'showitem' => 'starttime, endtime, extendToSubpages'
		),
		'2' => array(
			'showitem' => 'layout, lastUpdated, newUntil, no_search'
		),
		'3' => array(
			'showitem' => 'alias, target, no_cache, cache_timeout, cache_tags, url_scheme'
		),
		'5' => array(
			'showitem' => 'author, author_email',
			'canNotCollapse' => 1
		),
		'6' => array(
			'showitem' => 'php_tree_stop, editlock'
		),
		'7' => array(
			'showitem' => 'is_siteroot'
		),
		'8' => array(
			'showitem' => 'backend_layout_next_level'
		),
		'standard' => array(
			'showitem' => 'doktype;LLL:EXT:cms/locallang_tca.xlf:pages.doktype_formlabel',
			'canNotCollapse' => 1
		),
		'shortcut' => array(
			'showitem' => 'doktype;LLL:EXT:cms/locallang_tca.xlf:pages.doktype_formlabel, shortcut_mode;LLL:EXT:cms/locallang_tca.xlf:pages.shortcut_mode_formlabel',
			'canNotCollapse' => 1
		),
		'shortcutpage' => array(
			'showitem' => 'shortcut;LLL:EXT:cms/locallang_tca.xlf:pages.shortcut_formlabel',
			'canNotCollapse' => 1
		),
		'mountpoint' => array(
			'showitem' => 'doktype;LLL:EXT:cms/locallang_tca.xlf:pages.doktype_formlabel, mount_pid_ol;LLL:EXT:cms/locallang_tca.xlf:pages.mount_pid_ol_formlabel',
			'canNotCollapse' => 1
		),
		'mountpage' => array(
			'showitem' => 'mount_pid;LLL:EXT:cms/locallang_tca.xlf:pages.mount_pid_formlabel',
			'canNotCollapse' => 1
		),
		'external' => array(
			'showitem' => 'doktype;LLL:EXT:cms/locallang_tca.xlf:pages.doktype_formlabel, urltype;LLL:EXT:cms/locallang_tca.xlf:pages.urltype_formlabel, url;LLL:EXT:cms/locallang_tca.xlf:pages.url_formlabel',
			'canNotCollapse' => 1
		),
		'title' => array(
			'showitem' => 'title;LLL:EXT:cms/locallang_tca.xlf:pages.title_formlabel, --linebreak--, nav_title;LLL:EXT:cms/locallang_tca.xlf:pages.nav_title_formlabel, --linebreak--, subtitle;LLL:EXT:cms/locallang_tca.xlf:pages.subtitle_formlabel',
			'canNotCollapse' => 1
		),
		'titleonly' => array(
			'showitem' => 'title;LLL:EXT:cms/locallang_tca.xlf:pages.title_formlabel',
			'canNotCollapse' => 1
		),
		'visibility' => array(
			'showitem' => 'hidden;LLL:EXT:cms/locallang_tca.xlf:pages.hidden_formlabel, nav_hide;LLL:EXT:cms/locallang_tca.xlf:pages.nav_hide_formlabel',
			'canNotCollapse' => 1
		),
		'hiddenonly' => array(
			'showitem' => 'hidden;LLL:EXT:cms/locallang_tca.xlf:pages.hidden_formlabel',
			'canNotCollapse' => 1
		),
		'access' => array(
			'showitem' => 'starttime;LLL:EXT:cms/locallang_tca.xlf:pages.starttime_formlabel, endtime;LLL:EXT:cms/locallang_tca.xlf:pages.endtime_formlabel, extendToSubpages;LLL:EXT:cms/locallang_tca.xlf:pages.extendToSubpages_formlabel, --linebreak--, fe_group;LLL:EXT:cms/locallang_tca.xlf:pages.fe_group_formlabel, --linebreak--, fe_login_mode;LLL:EXT:cms/locallang_tca.xlf:pages.fe_login_mode_formlabel',
			'canNotCollapse' => 1
		),
		'abstract' => array(
			'showitem' => 'abstract;LLL:EXT:cms/locallang_tca.xlf:pages.abstract_formlabel',
			'canNotCollapse' => 1
		),
		'metatags' => array(
			'showitem' => 'keywords;LLL:EXT:cms/locallang_tca.xlf:pages.keywords_formlabel, --linebreak--, description;LLL:EXT:cms/locallang_tca.xlf:pages.description_formlabel',
			'canNotCollapse' => 1
		),
		'editorial' => array(
			'showitem' => 'author;LLL:EXT:cms/locallang_tca.xlf:pages.author_formlabel, author_email;LLL:EXT:cms/locallang_tca.xlf:pages.author_email_formlabel, lastUpdated;LLL:EXT:cms/locallang_tca.xlf:pages.lastUpdated_formlabel',
			'canNotCollapse' => 1
		),
		'layout' => array(
			'showitem' => 'layout;LLL:EXT:cms/locallang_tca.xlf:pages.layout_formlabel, newUntil;LLL:EXT:cms/locallang_tca.xlf:pages.newUntil_formlabel, --linebreak--, backend_layout;LLL:EXT:cms/locallang_tca.xlf:pages.backend_layout_formlabel, backend_layout_next_level;LLL:EXT:cms/locallang_tca.xlf:pages.backend_layout_next_level_formlabel',
			'canNotCollapse' => 1
		),
		'module' => array(
			'showitem' => 'module;LLL:EXT:cms/locallang_tca.xlf:pages.module_formlabel',
			'canNotCollapse' => 1
		),
		'replace' => array(
			'showitem' => 'content_from_pid;LLL:EXT:cms/locallang_tca.xlf:pages.content_from_pid_formlabel',
			'canNotCollapse' => 1
		),
		'links' => array(
			'showitem' => 'alias;LLL:EXT:cms/locallang_tca.xlf:pages.alias_formlabel, --linebreak--, target;LLL:EXT:cms/locallang_tca.xlf:pages.target_formlabel, --linebreak--, url_scheme;LLL:EXT:cms/locallang_tca.xlf:pages.url_scheme_formlabel',
			'canNotCollapse' => 1
		),
		'caching' => array(
			'showitem' => 'cache_timeout;LLL:EXT:cms/locallang_tca.xlf:pages.cache_timeout_formlabel, cache_tags, no_cache;LLL:EXT:cms/locallang_tca.xlf:pages.no_cache_formlabel',
			'canNotCollapse' => 1
		),
		'language' => array(
			'showitem' => 'l18n_cfg;LLL:EXT:cms/locallang_tca.xlf:pages.l18n_cfg_formlabel',
			'canNotCollapse' => 1
		),
		'miscellaneous' => array(
			'showitem' => 'is_siteroot;LLL:EXT:cms/locallang_tca.xlf:pages.is_siteroot_formlabel, no_search;LLL:EXT:cms/locallang_tca.xlf:pages.no_search_formlabel, editlock;LLL:EXT:cms/locallang_tca.xlf:pages.editlock_formlabel, php_tree_stop;LLL:EXT:cms/locallang_tca.xlf:pages.php_tree_stop_formlabel',
			'canNotCollapse' => 1
		),
		'adminsonly' => array(
			'showitem' => 'editlock;LLL:EXT:cms/locallang_tca.xlf:pages.editlock_formlabel',
			'canNotCollapse' => 1
		),
		'media' => array(
			'showitem' => 'media;LLL:EXT:cms/locallang_tca.xlf:pages.media_formlabel',
			'canNotCollapse' => 1
		),
		'storage' => array(
			'showitem' => 'storage_pid;LLL:EXT:cms/locallang_tca.xlf:pages.storage_pid_formlabel',
			'canNotCollapse' => 1
		),
		'config' => array(
			'showitem' => 'TSconfig;LLL:EXT:cms/locallang_tca.xlf:pages.TSconfig_formlabel',
			'canNotCollapse' => 1
		)
	)
);
?>

<?php
return array(
	'ctrl' => array(
		'label' => 'title',
		'tstamp' => 'tstamp',
		'title' => 'LLL:EXT:cms/locallang_tca.xlf:pages_language_overlay',
		'versioningWS' => TRUE,
		'versioning_followPages' => TRUE,
		'origUid' => 't3_origuid',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime'
		),
		'transOrigPointerField' => 'pid',
		'transOrigPointerTable' => 'pages',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'shadowColumnsForNewPlaceholders' => 'title',
		'languageField' => 'sys_language_uid',
		'mainpalette' => 1,
		'type' => 'doktype',
		'typeicon_classes' => array(
			'default' => 'mimetypes-x-content-page-language-overlay'
		),
		'dividers2tabs' => TRUE,
		'searchFields' => 'title,subtitle,nav_title,keywords,description,abstract,author,author_email,url'
	),
	'interface' => array(
		'showRecordFieldList' => 'title,hidden,starttime,endtime,keywords,description,abstract'
	),
	'columns' => array(
		'doktype' => $GLOBALS['TCA']['pages']['columns']['doktype'],
		'hidden' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:pages.hidden',
			'config' => array(
				'type' => 'check',
				'default' => '0',
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
					'upper' => mktime(0, 0, 0, 12, 31, 2020)
				)
			)
		),
		'title' => array(
			'l10n_mode' => 'prefixLangTitle',
			'label' => $GLOBALS['TCA']['pages']['columns']['title']['label'],
			'l10n_cat' => 'text',
			'config' => array(
				'type' => 'input',
				'size' => '50',
				'max' => '255',
				'eval' => 'trim,required'
			)
		),
		'subtitle' => array(
			'exclude' => 1,
			'l10n_cat' => 'text',
			'label' => $GLOBALS['TCA']['pages']['columns']['subtitle']['label'],
			'config' => array(
				'type' => 'input',
				'size' => '50',
				'max' => '255',
				'eval' => 'trim'
			)
		),
		'nav_title' => array(
			'exclude' => 1,
			'l10n_cat' => 'text',
			'label' => $GLOBALS['TCA']['pages']['columns']['nav_title']['label'],
			'config' => array(
				'type' => 'input',
				'size' => '50',
				'max' => '255',
				'eval' => 'trim'
			)
		),
		'keywords' => array(
			'exclude' => 1,
			'label' => $GLOBALS['TCA']['pages']['columns']['keywords']['label'],
			'config' => array(
				'type' => 'text',
				'cols' => '40',
				'rows' => '3'
			)
		),
		'description' => array(
			'exclude' => 1,
			'label' => $GLOBALS['TCA']['pages']['columns']['description']['label'],
			'config' => array(
				'type' => 'text',
				'cols' => '40',
				'rows' => '3'
			)
		),
		'abstract' => array(
			'exclude' => 1,
			'label' => $GLOBALS['TCA']['pages']['columns']['abstract']['label'],
			'config' => array(
				'type' => 'text',
				'cols' => '40',
				'rows' => '3'
			)
		),
		'author' => array(
			'exclude' => 1,
			'label' => $GLOBALS['TCA']['pages']['columns']['author']['label'],
			'config' => array(
				'type' => 'input',
				'size' => '23',
				'eval' => 'trim',
				'max' => '80'
			)
		),
		'author_email' => array(
			'exclude' => 1,
			'label' => $GLOBALS['TCA']['pages']['columns']['author_email']['label'],
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
			'label' => $GLOBALS['TCA']['pages']['columns']['media']['label'],
			'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig('media')
		),
		'url' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_tca.xlf:pages.url',
			'config' => array(
				'type' => 'input',
				'size' => '23',
				'max' => '255',
				'eval' => 'trim',
				'softref' => 'url'
			)
		),
		'urltype' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.type',
			'config' => array(
				'type' => 'select',
				'items' => $GLOBALS['TCA']['pages']['columns']['urltype']['config']['items'],
				'default' => '1'
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
				'items' => $GLOBALS['TCA']['pages']['columns']['shortcut_mode']['config']['items'],
				'default' => '0'
			)
		),
		'sys_language_uid' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.language',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xlf:LGL.default_value', 0)
				)
			)
		),
		'tx_impexp_origuid' => array('config' => array('type' => 'passthrough')),
		'l18n_diffsource' => array('config' => array('type' => 'passthrough')),
		't3ver_label' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.versionLabel',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'max' => '255'
			)
		)
	),
	'types' => array(
		// normal
		(string)\TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_DEFAULT => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.standard;standard,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.title;title,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.access,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.visibility;hiddenonly,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.access;access,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.metadata,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.abstract;abstract,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.metatags;metatags,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.editorial;editorial,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.resources,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.media;media,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.extended,
		'
		),
		// external URL
		(string)\TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_LINK => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.external;external,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.title;title,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.access,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.visibility;hiddenonly,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.access;access,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.metadata,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.abstract;abstract,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.editorial;editorial,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.resources,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.media;media,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.extended,
		'
		),
		// shortcut
		(string)\TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_SHORTCUT => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.shortcut;shortcut,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.shortcutpage;shortcutpage,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.title;title,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.access,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.visibility;hiddenonly,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.access;access,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.metadata,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.abstract;abstract,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.editorial;editorial,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.resources,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.media;media,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.extended,
				'
		),
		// mount page
		(string)\TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_MOUNTPOINT => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.standard;standard,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.title;title,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.access,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.visibility;hiddenonly,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.access;access,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.metadata,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.abstract;abstract,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.editorial;editorial,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.resources,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.media;media,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.extended,
		'
		),
		// spacer
		(string)\TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_SPACER => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.standard;standard,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.title;titleonly,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.access,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.visibility;hiddenonly,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.access;access,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.extended,
			'
		),
		// sysfolder
		(string)\TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_SYSFOLDER => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.standard;standard,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.title;titleonly,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.access,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.visibility;hiddenonly,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.resources,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.media;media,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.extended,
		'
		),
		// trash
		(string)\TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_RECYCLER => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.standard;standard,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.title;titleonly,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.access,
					--palette--;LLL:EXT:cms/locallang_tca.xlf:pages.palettes.visibility;hiddenonly,
				--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.extended,
		'
		)
	),
	'palettes' => array(
		'5' => array('showitem' => 'author,author_email', 'canNotCollapse' => TRUE),
		'standard' => array(
			'showitem' => 'doktype;LLL:EXT:cms/locallang_tca.xlf:pages.doktype_formlabel, sys_language_uid',
			'canNotCollapse' => 1
		),
		'shortcut' => array(
			'showitem' => 'doktype;LLL:EXT:cms/locallang_tca.xlf:pages.doktype_formlabel, sys_language_uid, shortcut_mode;LLL:EXT:cms/locallang_tca.xlf:pages.shortcut_mode_formlabel',
			'canNotCollapse' => 1
		),
		'shortcutpage' => array(
			'showitem' => 'shortcut;LLL:EXT:cms/locallang_tca.xlf:pages.shortcut_formlabel',
			'canNotCollapse' => 1
		),
		'external' => array(
			'showitem' => 'doktype;LLL:EXT:cms/locallang_tca.xlf:pages.doktype_formlabel, sys_language_uid, urltype;LLL:EXT:cms/locallang_tca.xlf:pages.urltype_formlabel, url;LLL:EXT:cms/locallang_tca.xlf:pages.url_formlabel',
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
		'hiddenonly' => array(
			'showitem' => 'hidden;LLL:EXT:cms/locallang_tca.xlf:pages.hidden_formlabel',
			'canNotCollapse' => 1
		),
		'access' => array(
			'showitem' => 'starttime;LLL:EXT:cms/locallang_tca.xlf:pages.starttime_formlabel, endtime;LLL:EXT:cms/locallang_tca.xlf:pages.endtime_formlabel',
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
			'showitem' => 'author;LLL:EXT:cms/locallang_tca.xlf:pages.author_formlabel, author_email;LLL:EXT:cms/locallang_tca.xlf:pages.author_email_formlabel',
			'canNotCollapse' => 1
		),
		'language' => array(
			'showitem' => 'l18n_cfg;LLL:EXT:cms/locallang_tca.xlf:pages.l18n_cfg_formlabel',
			'canNotCollapse' => 1
		),
		'media' => array(
			'showitem' => 'media;LLL:EXT:cms/locallang_tca.xlf:pages.media_formlabel',
			'canNotCollapse' => 1
		)
	)
);
?>
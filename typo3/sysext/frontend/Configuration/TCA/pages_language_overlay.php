<?php
return [
    'ctrl' => [
        'label' => 'title',
        'tstamp' => 'tstamp',
        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages_language_overlay',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'crdate' => 'crdate',
        'hideAtCopy' => 1,
        'prependAtCopy' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.prependAtCopy',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime'
        ],
        'transOrigPointerField' => 'pid',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'shadowColumnsForNewPlaceholders' => 'title',
        'languageField' => 'sys_language_uid',
        'type' => 'doktype',
        'typeicon_classes' => [
            'default' => 'mimetypes-x-content-page-language-overlay'
        ],
        'searchFields' => 'title,subtitle,nav_title,keywords,description,abstract,author,author_email,url'
    ],
    'interface' => [
        'showRecordFieldList' => 'title,hidden,starttime,endtime,keywords,description,abstract'
    ],
    'columns' => [
        'doktype' => $GLOBALS['TCA']['pages']['columns']['doktype'],
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.hidden',
            'config' => [
                'type' => 'check',
                'default' => 0,
                'items' => [
                    '1' => [
                        '0' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.hidden_checkbox_1_formlabel'
                    ]
                ]
            ]
        ],
        'starttime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime,int',
                'default' => 0
            ]
        ],
        'endtime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime,int',
                'default' => 0,
                'range' => [
                    'upper' => mktime(0, 0, 0, 1, 1, 2038)
                ]
            ]
        ],
        'title' => [
            'l10n_mode' => 'prefixLangTitle',
            'label' => $GLOBALS['TCA']['pages']['columns']['title']['label'],
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'eval' => 'trim,required'
            ]
        ],
        'subtitle' => [
            'exclude' => true,
            'label' => $GLOBALS['TCA']['pages']['columns']['subtitle']['label'],
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'eval' => 'trim'
            ]
        ],
        'nav_title' => [
            'exclude' => true,
            'label' => $GLOBALS['TCA']['pages']['columns']['nav_title']['label'],
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'eval' => 'trim'
            ]
        ],
        'keywords' => [
            'exclude' => true,
            'label' => $GLOBALS['TCA']['pages']['columns']['keywords']['label'],
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 3
            ]
        ],
        'description' => [
            'exclude' => true,
            'label' => $GLOBALS['TCA']['pages']['columns']['description']['label'],
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 3
            ]
        ],
        'abstract' => [
            'exclude' => true,
            'label' => $GLOBALS['TCA']['pages']['columns']['abstract']['label'],
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 3
            ]
        ],
        'author' => [
            'exclude' => true,
            'label' => $GLOBALS['TCA']['pages']['columns']['author']['label'],
            'config' => [
                'type' => 'input',
                'size' => 23,
                'eval' => 'trim',
                'max' => 80
            ]
        ],
        'author_email' => [
            'exclude' => true,
            'label' => $GLOBALS['TCA']['pages']['columns']['author_email']['label'],
            'config' => [
                'type' => 'input',
                'size' => 23,
                'eval' => 'trim',
                'max' => 80,
                'softref' => 'email[subst]'
            ]
        ],
        'media' => [
            'exclude' => true,
            'label' => $GLOBALS['TCA']['pages']['columns']['media']['label'],
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig(
                'media',
                [
                    // Use the imageoverlayPalette instead of the basicoverlayPalette
                    'overrideChildTca' => [
                        'types' => [
                            '0' => [
                                'showitem' => '
                                    --palette--;LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                                    --palette--;;filePalette'
                            ],
                            \TYPO3\CMS\Core\Resource\File::FILETYPE_TEXT => [
                                'showitem' => '
                                    --palette--;LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                                    --palette--;;filePalette'
                            ],
                            \TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE => [
                                'showitem' => '
                                    --palette--;LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                                    --palette--;;filePalette'
                            ],
                            \TYPO3\CMS\Core\Resource\File::FILETYPE_AUDIO => [
                                'showitem' => '
                                    --palette--;LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.audioOverlayPalette;audioOverlayPalette,
                                    --palette--;;filePalette'
                            ],
                            \TYPO3\CMS\Core\Resource\File::FILETYPE_VIDEO => [
                                'showitem' => '
                                    --palette--;LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.videoOverlayPalette;videoOverlayPalette,
                                    --palette--;;filePalette'
                            ],
                            \TYPO3\CMS\Core\Resource\File::FILETYPE_APPLICATION => [
                                'showitem' => '
                                    --palette--;LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
                                    --palette--;;filePalette'
                            ]
                        ],
                    ],
                ]
            )
        ],
        'url' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.url',
            'config' => [
                'type' => 'input',
                'size' => 23,
                'max' => 255,
                'eval' => 'trim',
                'softref' => 'url'
            ]
        ],
        'urltype' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => $GLOBALS['TCA']['pages']['columns']['urltype']['config']['items'],
                'default' => '1'
            ]
        ],
        'shortcut' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.shortcut_page',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages',
                'size' => 1,
                'maxitems' => 1,
                'minitems' => 0,
                'default' => 0
            ]
        ],
        'shortcut_mode' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.shortcut_mode',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => $GLOBALS['TCA']['pages']['columns']['shortcut_mode']['config']['items'],
                'default' => 0
            ]
        ],
        'sys_language_uid' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.sorting',
                'items' => [], // no default language here, as the pages table is always the default language
                'default' => 0,
                'fieldWizard' => [
                    'selectIcons' => [
                        'disabled' => false,
                    ],
                ],
            ]
        ],
        'l18n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => ''
            ]
        ],
        't3ver_label' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.versionLabel',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255
            ]
        ]
    ],
    'types' => [
        // normal
        (string)\TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_DEFAULT => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.standard;standard,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.title;title,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.metadata,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.abstract;abstract,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.metatags;metatags,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.editorial;editorial,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.resources,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.media;media,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    sys_language_uid,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.visibility;hiddenonly,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.access;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            '
        ],
        // external URL
        (string)\TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_LINK => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    doktype,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.title;title,
                    --palette--;;external,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.metadata,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.abstract;abstract,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.editorial;editorial,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.resources,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.media;media,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    sys_language_uid,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.visibility;hiddenonly,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.access;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            '
        ],
        // shortcut
        (string)\TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_SHORTCUT => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    doktype,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.title;title,
                    --palette--;;shortcut,
                    --palette--;;shortcutpage,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.metadata,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.abstract;abstract,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.editorial;editorial,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.resources,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.media;media,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    sys_language_uid,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.visibility;hiddenonly,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.access;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            '
        ],
        // mount page
        (string)\TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_MOUNTPOINT => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.standard;standard,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.title;title,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.metadata,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.abstract;abstract,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.editorial;editorial,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.resources,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.media;media,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    sys_language_uid,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.visibility;hiddenonly,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.access;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            '
        ],
        // spacer
        (string)\TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_SPACER => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.standard;standard,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.title;titleonly,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    sys_language_uid,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.visibility;hiddenonly,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.access;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            '
        ],
        // sysfolder
        (string)\TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_SYSFOLDER => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.standard;standard,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.title;titleonly,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.tabs.resources,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.media;media,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    sys_language_uid,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.visibility;hiddenonly,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            '
        ],
        // trash
        (string)\TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_RECYCLER => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.standard;standard,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.title;titleonly,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    sys_language_uid,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.visibility;hiddenonly,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            '
        ]
    ],
    'palettes' => [
        '5' => [
            'showitem' => 'author,author_email',
        ],
        'standard' => [
            'showitem' => 'doktype;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.doktype_formlabel',
        ],
        'shortcut' => [
            'showitem' => 'shortcut_mode;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.shortcut_mode_formlabel',
        ],
        'shortcutpage' => [
            'showitem' => 'shortcut;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.shortcut_formlabel',
        ],
        'external' => [
            'showitem' => 'urltype;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.urltype_formlabel, url;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.url_formlabel',
        ],
        'title' => [
            'showitem' => 'title;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.title_formlabel, --linebreak--, nav_title;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.nav_title_formlabel, --linebreak--, subtitle;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.subtitle_formlabel',
        ],
        'titleonly' => [
            'showitem' => 'title;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.title_formlabel',
        ],
        'hiddenonly' => [
            'showitem' => 'hidden;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.hidden_formlabel',
        ],
        'access' => [
            'showitem' => 'starttime;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.starttime_formlabel, endtime;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.endtime_formlabel',
        ],
        'abstract' => [
            'showitem' => 'abstract;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.abstract_formlabel',
        ],
        'metatags' => [
            'showitem' => 'keywords;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.keywords_formlabel, --linebreak--, description;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.description_formlabel',
        ],
        'editorial' => [
            'showitem' => 'author;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.author_formlabel, author_email;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.author_email_formlabel',
        ],
        'language' => [
            'showitem' => 'l18n_cfg;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.l18n_cfg_formlabel',
        ],
        'media' => [
            'showitem' => 'media;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.media_formlabel',
        ],
    ],
];

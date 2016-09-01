<?php
return [
    'ctrl' => [
        'label' => 'header',
        'label_alt' => 'subheader,bodytext',
        'descriptionColumn' => 'rowDescription',
        'sortby' => 'sorting',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'editlock' => 'editlock',
        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:tt_content',
        'delete' => 'deleted',
        'versioningWS' => true,
        'versioning_followPages' => true,
        'origUid' => 't3_origuid',
        'type' => 'CType',
        'hideAtCopy' => true,
        'prependAtCopy' => 'LLL:EXT:lang/locallang_general.xlf:LGL.prependAtCopy',
        'copyAfterDuplFields' => 'colPos,sys_language_uid',
        'useColumnsForDefaultValues' => 'colPos,sys_language_uid,CType',
        'shadowColumnsForNewPlaceholders' => 'colPos',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'languageField' => 'sys_language_uid',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'fe_group' => 'fe_group'
        ],
        'typeicon_column' => 'CType',
        'typeicon_classes' => [
            'header' => 'mimetypes-x-content-header',
            'bullets' => 'mimetypes-x-content-list-bullets',
            'table' => 'mimetypes-x-content-table',
            'uploads' => 'mimetypes-x-content-list-files',
            'menu' => 'mimetypes-x-content-menu',
            'list' => 'mimetypes-x-content-plugin',
            'login' => 'mimetypes-x-content-login',
            'shortcut' => 'mimetypes-x-content-link',
            'script' => 'mimetypes-x-content-script',
            'div' => 'mimetypes-x-content-divider',
            'html' => 'mimetypes-x-content-html',
            'default' => 'mimetypes-x-content-text'
        ],
        'thumbnail' => 'image',
        'requestUpdate' => 'list_type,menu_type',
        'searchFields' => 'header,header_link,subheader,bodytext,pi_flexform'
    ],
    'interface' => [
        'always_description' => 0,
        'showRecordFieldList' => 'CType,header,rowDescription,header_link,bodytext,image,media,imagewidth,imageorient,records,colPos,starttime,endtime,fe_group'
    ],
    'columns' => [
        'CType' => [
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.div.standard',
                        '--div--'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.I.0',
                        'header',
                        'content-header'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.div.lists',
                        '--div--'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.I.4',
                        'bullets',
                        'content-bullets'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.I.5',
                        'table',
                        'content-table'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.I.6',
                        'uploads',
                        'content-special-uploads'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.div.special',
                        '--div--'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.I.12',
                        'menu',
                        'content-special-menu'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.I.13',
                        'shortcut',
                        'content-special-shortcut'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.I.14',
                        'list',
                        'content-plugin'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.I.16',
                        'div',
                        'content-special-div'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.I.17',
                        'html',
                        'content-special-html'
                    ]
                ],
                'default' => 'header',
                'authMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'],
                'authMode_enforce' => 'strict',
            ]
        ],
        'editlock' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:editlock',
            'config' => [
                'type' => 'check',
                'items' => [
                    '1' => [
                        '0' => 'LLL:EXT:lang/locallang_core.xlf:labels.enabled'
                    ]
                ]
            ]
        ],
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'items' => [
                    '1' => [
                        '0' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:hidden.I.0'
                    ]
                ]
            ]
        ],
        'starttime' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'input',
                'size' => '13',
                'eval' => 'datetime',
                'default' => 0
            ],
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly'
        ],
        'endtime' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'input',
                'size' => '13',
                'eval' => 'datetime',
                'default' => 0,
                'range' => [
                    'upper' => mktime(0, 0, 0, 1, 1, 2038)
                ]
            ],
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly'
        ],
        'fe_group' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.fe_group',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 5,
                'maxitems' => 20,
                'items' => [
                    [
                        'LLL:EXT:lang/locallang_general.xlf:LGL.hide_at_login',
                        -1
                    ],
                    [
                        'LLL:EXT:lang/locallang_general.xlf:LGL.any_login',
                        -2
                    ],
                    [
                        'LLL:EXT:lang/locallang_general.xlf:LGL.usergroups',
                        '--div--'
                    ]
                ],
                'exclusiveKeys' => '-1,-2',
                'foreign_table' => 'fe_groups',
                'foreign_table_where' => 'ORDER BY fe_groups.title',
                'enableMultiSelectFilterTextfield' => true
            ]
        ],
        'sys_language_uid' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'special' => 'languages',
                'items' => [
                    [
                        'LLL:EXT:lang/locallang_general.xlf:LGL.allLanguages',
                        -1,
                        'flags-multiple'
                    ],
                ],
                'default' => 0,
            ]
        ],
        'l18n_parent' => [
            'exclude' => 1,
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        '',
                        0
                    ]
                ],
                'foreign_table' => 'tt_content',
                'foreign_table_where' => 'AND tt_content.pid=###CURRENT_PID### AND tt_content.sys_language_uid IN (-1,0)',
                'default' => 0
            ]
        ],
        'layout' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.layout',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'LLL:EXT:lang/locallang_general.xlf:LGL.default_value',
                        '0'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:layout.I.1',
                        '1'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:layout.I.2',
                        '2'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:layout.I.3',
                        '3'
                    ]
                ],
                'default' => 0
            ]
        ],
        'colPos' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:colPos',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'itemsProcFunc' => \TYPO3\CMS\Backend\View\BackendLayoutView::class . '->colPosListItemProcFunc',
                'items' => [
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:colPos.I.0',
                        '1'
                    ],
                    [
                        'LLL:EXT:lang/locallang_general.xlf:LGL.normal',
                        '0'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:colPos.I.2',
                        '2'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:colPos.I.3',
                        '3'
                    ]
                ],
                'default' => 0
            ]
        ],
        'date' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:date',
            'config' => [
                'type' => 'input',
                'size' => '13',
                'max' => '20',
                'eval' => 'date',
                'default' => 0
            ]
        ],
        'header' => [
            'l10n_mode' => 'prefixLangTitle',
            'l10n_cat' => 'text',
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header',
            'config' => [
                'type' => 'input',
                'size' => '50',
                'max' => '255'
            ]
        ],
        'rowDescription' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.description',
            'config' => [
                'type' => 'text',
                'rows' => 5,
                'cols' => 30
            ]
        ],
        'header_link' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_link',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 1024,
                'eval' => 'trim',
                'wizards' => [
                    'link' => [
                        'type' => 'popup',
                        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_link_formlabel',
                        'icon' => 'actions-wizard-link',
                        'module' => [
                            'name' => 'wizard_link',
                        ],
                        'JSopenParams' => 'width=800,height=600,status=0,menubar=0,scrollbars=1'
                    ]
                ],
                'softref' => 'typolink'
            ]
        ],
        'header_layout' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'LLL:EXT:lang/locallang_general.xlf:LGL.default_value',
                        '0'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_layout.I.1',
                        '1'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_layout.I.2',
                        '2'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_layout.I.3',
                        '3'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_layout.I.4',
                        '4'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_layout.I.5',
                        '5'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_layout.I.6',
                        '100'
                    ]
                ],
                'default' => 0
            ]
        ],
        'subheader' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.subheader',
            'config' => [
                'type' => 'input',
                'size' => '50',
                'max' => '255',
                'softref' => 'email[subst]'
            ]
        ],
        'bodytext' => [
            'l10n_mode' => 'prefixLangTitle',
            'l10n_cat' => 'text',
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.text',
            'config' => [
                'type' => 'text',
                'cols' => '80',
                'rows' => '15',
                'wizards' => [
                    'RTE' => [
                        'notNewRecords' => 1,
                        'RTEonly' => 1,
                        'type' => 'script',
                        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext.W.RTE',
                        'icon' => 'actions-wizard-rte',
                        'module' => [
                            'name' => 'wizard_rte'
                        ]
                    ],
                    'table' => [
                        'notNewRecords' => 1,
                        'enableByTypeConfig' => 1,
                        'type' => 'script',
                        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext.W.table',
                        'icon' => 'content-table',
                        'module' => [
                            'name' => 'wizard_table'
                        ],
                        'params' => [
                            'xmlOutput' => 0
                        ]
                    ]
                ],
                'softref' => 'typolink_tag,images,email[subst],url',
                'search' => [
                    'andWhere' => 'CType=\'text\' OR CType=\'textpic\''
                ]
            ]
        ],
        'image' => [
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.images',
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig('image', [
                'appearance' => [
                    'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference'
                ],
                // custom configuration for displaying fields in the overlay/reference table
                // to use the imageoverlayPalette instead of the basicoverlayPalette
                'foreign_types' => [
                    '0' => [
                        'showitem' => '
							--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
							--palette--;;filePalette'
                    ],
                    \TYPO3\CMS\Core\Resource\File::FILETYPE_TEXT => [
                        'showitem' => '
							--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
							--palette--;;filePalette'
                    ],
                    \TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE => [
                        'showitem' => '
							--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
							--palette--;;filePalette'
                    ],
                    \TYPO3\CMS\Core\Resource\File::FILETYPE_AUDIO => [
                        'showitem' => '
							--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.audioOverlayPalette;audioOverlayPalette,
							--palette--;;filePalette'
                    ],
                    \TYPO3\CMS\Core\Resource\File::FILETYPE_VIDEO => [
                        'showitem' => '
							--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.videoOverlayPalette;videoOverlayPalette,
							--palette--;;filePalette'
                    ],
                    \TYPO3\CMS\Core\Resource\File::FILETYPE_APPLICATION => [
                        'showitem' => '
							--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
							--palette--;;filePalette'
                    ]
                ]
            ], $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'])
        ],
        'imagewidth' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imagewidth',
            'config' => [
                'type' => 'input',
                'size' => '4',
                'max' => '4',
                'eval' => 'int',
                'range' => [
                    'upper' => 1999,
                    'lower' => 0,
                ],
                'default' => 0
            ]
        ],
        'imageheight' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageheight',
            'config' => [
                'type' => 'input',
                'size' => '4',
                'max' => '4',
                'eval' => 'int',
                'range' => [
                    'upper' => 1999,
                    'lower' => 0,
                ],
                'default' => 0
            ]
        ],
        'imageorient' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.0',
                        0,
                        'EXT:frontend/Resources/Public/Icons/ImageOrientation/above_center.gif'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.1',
                        1,
                        'EXT:frontend/Resources/Public/Icons/ImageOrientation/above_right.gif'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.2',
                        2,
                        'EXT:frontend/Resources/Public/Icons/ImageOrientation/above_left.gif'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.3',
                        8,
                        'EXT:frontend/Resources/Public/Icons/ImageOrientation/below_center.gif'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.4',
                        9,
                        'EXT:frontend/Resources/Public/Icons/ImageOrientation/below_right.gif'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.5',
                        10,
                        'EXT:frontend/Resources/Public/Icons/ImageOrientation/below_left.gif'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.6',
                        17,
                        'EXT:frontend/Resources/Public/Icons/ImageOrientation/intext_right.gif'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.7',
                        18,
                        'EXT:frontend/Resources/Public/Icons/ImageOrientation/intext_left.gif'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.8',
                        '--div--'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.9',
                        25,
                        'EXT:frontend/Resources/Public/Icons/ImageOrientation/intext_right_nowrap.gif'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.10',
                        26,
                        'EXT:frontend/Resources/Public/Icons/ImageOrientation/intext_left_nowrap.gif'
                    ]
                ],
                'selicon_cols' => 6,
                'default' => 0,
                'showIconTable' => true,
            ]
        ],
        'imageborder' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageborder',
            'config' => [
                'type' => 'check',
                'items' => [
                    '1' => [
                        '0' => 'LLL:EXT:lang/locallang_core.xlf:labels.enabled'
                    ]
                ]
            ]
        ],
        'image_zoom' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_zoom',
            'config' => [
                'type' => 'check',
                'items' => [
                    '1' => [
                        '0' => 'LLL:EXT:lang/locallang_core.xlf:labels.enabled'
                    ]
                ]
            ]
        ],
        'imagecols' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imagecols',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        '1',
                        1
                    ],
                    [
                        '2',
                        2
                    ],
                    [
                        '3',
                        3
                    ],
                    [
                        '4',
                        4
                    ],
                    [
                        '5',
                        5
                    ],
                    [
                        '6',
                        6
                    ],
                    [
                        '7',
                        7
                    ],
                    [
                        '8',
                        8
                    ]
                ],
                'default' => 2
            ]
        ],
        'cols' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:cols',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:cols.I.0',
                        '0'
                    ],
                    [
                        '1',
                        '1'
                    ],
                    [
                        '2',
                        '2'
                    ],
                    [
                        '3',
                        '3'
                    ],
                    [
                        '4',
                        '4'
                    ],
                    [
                        '5',
                        '5'
                    ],
                    [
                        '6',
                        '6'
                    ],
                    [
                        '7',
                        '7'
                    ],
                    [
                        '8',
                        '8'
                    ],
                    [
                        '9',
                        '9'
                    ]
                ],
                'default' => 0
            ]
        ],
        'pages' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.startingpoint',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'pages',
                'size' => '3',
                'maxitems' => '50',
                'minitems' => '0',
                'show_thumbs' => '1',
                'wizards' => [
                    'suggest' => [
                        'type' => 'suggest'
                    ]
                ]
            ]
        ],
        'recursive' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.recursive',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:recursive.I.0',
                        '0'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:recursive.I.1',
                        '1'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:recursive.I.2',
                        '2'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:recursive.I.3',
                        '3'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:recursive.I.4',
                        '4'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:recursive.I.5',
                        '250'
                    ]
                ],
                'default' => 0
            ]
        ],
        'menu_type' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:menu_type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:menu_type.I.0',
                        '0'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:menu_type.I.1',
                        '1'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:menu_type.I.2',
                        '4'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:menu_type.I.3',
                        '7'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:menu_type.I.4',
                        '2'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:menu_type.I.8',
                        '8'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:menu_type.I.5',
                        '3'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:menu_type.I.6',
                        '5'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:menu_type.I.7',
                        '6'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:menu_type.I.categorized_pages',
                        'categorized_pages'
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:menu_type.I.categorized_content',
                        'categorized_content'
                    ]
                ],
                'default' => 0
            ]
        ],
        'list_type' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:list_type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        '',
                        '',
                        ''
                    ]
                ],
                'itemsProcFunc' => \TYPO3\CMS\Frontend\Hooks\TableColumnHooks::class . '->sortPluginList',
                'default' => '',
                'authMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'],
                'authMode_enforce' => 'strict'
            ]
        ],
        'select_key' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.code',
            'config' => [
                'type' => 'input',
                'size' => '50',
                'max' => '80',
                'eval' => 'trim'
            ]
        ],
        'file_collections' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:file_collections',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'localizeReferencesAtParentLocalization' => true,
                'allowed' => 'sys_file_collection',
                'foreign_table' => 'sys_file_collection',
                'maxitems' => 999,
                'minitems' => 0,
                'size' => 5,
                'wizards' => [
                    'suggest' => [
                        'type' => 'suggest'
                    ]
                ]
            ]
        ],
        'media' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:media',
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig('media', [
                'appearance' => [
                    'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:media.addFileReference'
                ]
            ])
        ],
        'filelink_size' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:filelink_size',
            'config' => [
                'type' => 'check',
                'items' => [
                    '1' => [
                        '0' => 'LLL:EXT:lang/locallang_core.xlf:labels.enabled'
                    ]
                ]
            ]
        ],
        'filelink_sorting' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:filelink_sorting',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:filelink_sorting.none', ''],
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:filelink_sorting.extension', 'extension'],
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:filelink_sorting.name', 'name'],
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:filelink_sorting.type', 'type'],
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:filelink_sorting.size', 'size']
                ]
            ]
        ],
        'target' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:target',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim',
                'wizards' => [
                    'target_picker' => [
                        'type' => 'select',
                        'renderType' => 'selectSingle',
                        'mode' => '',
                        'items' => [
                            ['LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:target.I.1', '_blank']
                        ]
                    ]
                ],
                'default' => ''
            ]
        ],
        'records' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:records',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tt_content',
                'size' => '5',
                'maxitems' => '200',
                'minitems' => '0',
                'show_thumbs' => '1',
                'wizards' => [
                    'suggest' => [
                        'type' => 'suggest'
                    ]
                ]
            ]
        ],
        'sectionIndex' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:sectionIndex',
            'config' => [
                'type' => 'check',
                'default' => 1,
                'items' => [
                    '1' => [
                        '0' => 'LLL:EXT:lang/locallang_core.xlf:labels.enabled'
                    ]
                ]
            ]
        ],
        'linkToTop' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:linkToTop',
            'config' => [
                'type' => 'check',
                'items' => [
                    '1' => [
                        '0' => 'LLL:EXT:lang/locallang_core.xlf:labels.enabled'
                    ]
                ]
            ]
        ],
        'pi_flexform' => [
            'l10n_display' => 'hideDiff',
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:pi_flexform',
            'config' => [
                'type' => 'flex',
                'ds_pointerField' => 'list_type,CType',
                'ds' => [
                    'default' => '
						<T3DataStructure>
						  <ROOT>
						    <type>array</type>
						    <el>
								<!-- Repeat an element like "xmlTitle" beneath for as many elements you like. Remember to name them uniquely  -->
						      <xmlTitle>
								<TCEforms>
									<label>The Title:</label>
									<config>
										<type>input</type>
										<size>48</size>
									</config>
								</TCEforms>
						      </xmlTitle>
						    </el>
						  </ROOT>
						</T3DataStructure>
					'
                ],
                'search' => [
                    'andWhere' => 'CType=\'list\''
                ]
            ]
        ],
        'tx_impexp_origuid' => [
            'config' => [
                'type' => 'passthrough'
            ]
        ],
        'accessibility_title' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:accessibility_title',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim',
                'default' => ''
            ]
        ],
        'accessibility_bypass' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:accessibility_bypass',
            'config' => [
                'type' => 'check',
                'items' => [
                    '1' => [
                        '0' => 'LLL:EXT:lang/locallang_core.xlf:labels.enabled'
                    ]
                ]
            ]
        ],
        'accessibility_bypass_text' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:accessibility_bypass_text',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim',
                'default' => ''
            ]
        ],
        'l18n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => ''
            ]
        ],
        't3ver_label' => [
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.versionLabel',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'max' => '255'
            ]
        ],
        'selected_categories' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:selected_categories',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectTree',
                'foreign_table' => 'sys_category',
                'foreign_table_where' => 'AND sys_category.sys_language_uid IN (0,-1) ORDER BY sys_category.title ASC',
                'size' => 10,
                'autoSizeMax' => 50,
                'maxitems' => 9999,
                'treeConfig' => [
                    'parentField' => 'parent',
                    'appearance' => [
                        'expandAll' => true,
                        'showHeader' => true,
                    ],
                ],
            ]
        ],
        'category_field' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:category_field',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1,
                'itemsProcFunc' => \TYPO3\CMS\Core\Category\CategoryRegistry::class . '->getCategoryFieldsForTable',
            ]
        ],
        'table_caption' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/Database.xlf:tt_content.table_caption',
            'config' => [
                'type' => 'input'
            ]
        ],
        'table_delimiter' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/Database.xlf:tt_content.table_delimiter',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:frontend/Resources/Private/Language/Database.xlf:tt_content.table_delimiter.124', 124],
                    ['LLL:EXT:frontend/Resources/Private/Language/Database.xlf:tt_content.table_delimiter.59', 59],
                    ['LLL:EXT:frontend/Resources/Private/Language/Database.xlf:tt_content.table_delimiter.44', 44],
                    ['LLL:EXT:frontend/Resources/Private/Language/Database.xlf:tt_content.table_delimiter.58', 58],
                    ['LLL:EXT:frontend/Resources/Private/Language/Database.xlf:tt_content.table_delimiter.9', 9]
                ],
                'default' => 124
            ]
        ],
        'table_enclosure' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/Database.xlf:tt_content.table_enclosure',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:frontend/Resources/Private/Language/Database.xlf:tt_content.table_enclosure.0', 0],
                    ['LLL:EXT:frontend/Resources/Private/Language/Database.xlf:tt_content.table_enclosure.39', 39],
                    ['LLL:EXT:frontend/Resources/Private/Language/Database.xlf:tt_content.table_enclosure.34', 34]
                ],
                'default' => 0
            ]
        ],
        'table_header_position' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/Database.xlf:tt_content.table_header_position',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:frontend/Resources/Private/Language/Database.xlf:tt_content.table_header_position.0', 0],
                    ['LLL:EXT:frontend/Resources/Private/Language/Database.xlf:tt_content.table_header_position.1', 1],
                    ['LLL:EXT:frontend/Resources/Private/Language/Database.xlf:tt_content.table_header_position.2', 2]
                ],
                'default' => 0
            ]
        ],
        'table_tfoot' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/Database.xlf:tt_content.table_tfoot',
            'config' => [
                'type' => 'check',
                'default' => 0,
                'items' => [
                    ['LLL:EXT:lang/locallang_core.xml:labels.enabled', 1]
                ]
            ]
        ],
    ],
    'types' => [
        '1' => [
            'showitem' => 'CType'
        ],
        'bullets' => [
            'showitem' => '
					--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
					--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.header;header,rowDescription,
					bodytext;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext.ALT.bulletlist_formlabel,
				--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
					layout;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:layout_formlabel,
					--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.appearanceLinks;appearanceLinks,
				--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
					hidden;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:field.default.hidden,
					--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,
				--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.extended
			',
            'columnsOverrides' => ['bodytext' => ['defaultExtras' => 'nowrap']]
        ],
        'div' => [
            'showitem' => '
					--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
					header;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header.ALT.div_formlabel,rowDescription,
				--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
					layout;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:layout_formlabel,
					--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.appearanceLinks;appearanceLinks,
				--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
					hidden;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:field.default.hidden,
					--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,
				--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.extended
			'
        ],
        'header' => [
            'showitem' => '
					--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
					--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.headers;headers,rowDescription,
				--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
					layout;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:layout_formlabel,
					--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.appearanceLinks;appearanceLinks,
				--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
					hidden;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:field.default.hidden,
					--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,
				--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.extended
			'
        ],
        'html' => [
            'showitem' => '
					--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
					header;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header.ALT.html_formlabel,rowDescription,
					bodytext;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext.ALT.html_formlabel,
				--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
					layout;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:layout_formlabel,
					--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.appearanceLinks;appearanceLinks,
				--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
					hidden;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:field.default.hidden,
					--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,
				--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.extended
			'
        ],
        'list' => [
            'showitem' => '
					--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
					--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.header;header,rowDescription,
				--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.plugin,
					list_type;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:list_type_formlabel,
					select_key;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:select_key_formlabel,
					pages;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:pages.ALT.list_formlabel,
					recursive,
				--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
					layout;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:layout_formlabel,
					--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.appearanceLinks;appearanceLinks,
				--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
					hidden;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:field.default.hidden,
					--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,
				--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.extended
			',
            'subtype_value_field' => 'list_type',
            'subtypes_excludelist' => [
                '3' => 'layout',
                '2' => 'layout',
                '5' => 'layout',
                '9' => 'layout',
                '0' => 'layout',
                '6' => 'layout',
                '7' => 'layout',
                '1' => 'layout',
                '8' => 'layout',
                '11' => 'layout',
                '20' => 'layout',
                '21' => 'layout'
            ]
        ],
        'menu' => [
            'showitem' => '
					--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
					--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.header;header,rowDescription,
					--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.menu;menu,
				--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
					layout;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:layout_formlabel,
					--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.appearanceLinks;appearanceLinks,
				--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
					hidden;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:field.default.hidden,
					--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,
				--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.accessibility,
					--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.menu_accessibility;menu_accessibility,
				--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.extended
			',
            'subtype_value_field' => 'menu_type',
            'subtypes_excludelist' => [
                '2' => 'pages',
                'categorized_pages' => 'pages',
                'categorized_content' => 'pages',
            ],
            'subtypes_addlist' => [
                'categorized_pages' => 'selected_categories, category_field',
                'categorized_content' => 'selected_categories, category_field',
            ]
        ],
        'shortcut' => [
            'showitem' => '
					--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
					header;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header.ALT.shortcut_formlabel,rowDescription,
					records;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:records_formlabel,
				--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
					layout;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:layout_formlabel,
					--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.appearanceLinks;appearanceLinks,
				--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
					hidden;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:field.default.hidden,
					--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,
				--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.extended
			'
        ],
        'table' => [
            'showitem' => '
					--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
					--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.header;header,rowDescription,
					bodytext;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:field.table.bodytext,
					--palette--;;tableconfiguration,
					table_caption,
				--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
					layout;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:layout_formlabel,
					--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.table_layout;tablelayout,
					--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.appearanceLinks;appearanceLinks,
				--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
					hidden;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:field.default.hidden,
					--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,
				--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.extended
			'
        ],
        'uploads' => [
            'showitem' => '
					--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
					--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.header;header,rowDescription,
					--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:media;uploads,
				--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
					layout;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:layout_formlabel,
					--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.uploads_layout;uploadslayout,
					 --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.appearanceLinks;appearanceLinks,
				--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
					hidden;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:field.default.hidden,
					--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,
				--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.extended
			'
        ]
    ],
    'palettes' => [
        'general' => [
            'showitem' => '
				CType;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType_formlabel,
				colPos;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:colPos_formlabel,
				sys_language_uid;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:sys_language_uid_formlabel,
				--linebreak--,
				l18n_parent
			',
        ],
        'header' => [
            'showitem' => '
				header;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_formlabel,
				--linebreak--,
				header_layout;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_layout_formlabel,
				date;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:date_formlabel,
				--linebreak--,
				header_link;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_link_formlabel
			',
        ],
        'headers' => [
            'showitem' => '
				header;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_formlabel,
				--linebreak--,
				header_layout;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_layout_formlabel,
				date;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:date_formlabel,
				--linebreak--,
				header_link;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_link_formlabel,
				--linebreak--,
				subheader;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:subheader_formlabel
			',
        ],
        'imagelinks' => [
            'showitem' => '
				image_zoom;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_zoom_formlabel
			',
        ],
        'uploads' => [
            'showitem' => '
				media;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:media.ALT.uploads_formlabel,
				--linebreak--,
				file_collections;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:file_collections.ALT.uploads_formlabel,
				--linebreak--,
				filelink_sorting,
				target
			',
        ],
        'menu' => [
            'showitem' => '
				menu_type;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:menu_type_formlabel,
				--linebreak--,
				pages;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:pages.ALT.menu_formlabel
			',
        ],
        'menu_accessibility' => [
            'showitem' => '
				accessibility_title;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:menu.ALT.accessibility_title_formlabel,
				--linebreak--,
				accessibility_bypass;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:menu.ALT.accessibility_bypass_formlabel,
				accessibility_bypass_text;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:menu.ALT.accessibility_bypass_text_formlabel
			',
        ],
        'access' => [
            'showitem' => '
				starttime;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:starttime_formlabel,
				endtime;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:endtime_formlabel,
				--linebreak--,
				fe_group;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:fe_group_formlabel,
				--linebreak--,editlock
			',
        ],
        'uploadslayout' => [
            'showitem' => '
				filelink_size;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:filelink_size_formlabel
			',
        ],
        'appearanceLinks' => [
            'showitem' => '
				sectionIndex;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:sectionIndex_formlabel,
				linkToTop;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:linkToTop_formlabel
			'
        ],
        'tableconfiguration' => [
            'showitem' => '
				table_delimiter,
				table_enclosure
			'
        ],
        'tablelayout' => [
            'showitem' => '
				cols,
				table_header_position,
				table_tfoot
			'
        ]
    ]
];

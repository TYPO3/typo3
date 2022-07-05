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
        'groupName' => 'content',
        'origUid' => 't3_origuid',
        'type' => 'CType',
        'hideAtCopy' => true,
        'prependAtCopy' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.prependAtCopy',
        'copyAfterDuplFields' => 'colPos,sys_language_uid',
        'useColumnsForDefaultValues' => 'colPos,sys_language_uid,CType',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'languageField' => 'sys_language_uid',
        'translationSource' => 'l10n_source',
        'previewRenderer' => \TYPO3\CMS\Backend\Preview\StandardContentPreviewRenderer::class,
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'fe_group' => 'fe_group',
        ],
        'typeicon_column' => 'CType',
        'typeicon_classes' => [
            'header' => 'mimetypes-x-content-header',
            'text' => 'mimetypes-x-content-text',
            'textpic' => 'mimetypes-x-content-text-picture',
            'image' => 'mimetypes-x-content-image',
            'textmedia' => 'mimetypes-x-content-text-media',
            'bullets' => 'mimetypes-x-content-list-bullets',
            'table' => 'mimetypes-x-content-table',
            'uploads' => 'mimetypes-x-content-list-files',
            'list' => 'mimetypes-x-content-plugin',
            'shortcut' => 'mimetypes-x-content-link',
            'script' => 'mimetypes-x-content-script',
            'div' => 'mimetypes-x-content-divider',
            'html' => 'mimetypes-x-content-html',
            'default' => 'mimetypes-x-content-text',
            'menu_abstract' => 'content-menu-abstract',
            'menu_categorized_content' => 'content-menu-categorized',
            'menu_categorized_pages' => 'content-menu-categorized',
            'menu_pages' => 'content-menu-pages',
            'menu_subpages' => 'content-menu-pages',
            'menu_recently_updated' => 'content-menu-recently-updated',
            'menu_related_pages' => 'content-menu-related',
            'menu_sitemap' => 'content-menu-sitemap',
            'menu_sitemap_pages' => 'content-menu-sitemap-pages',
            'menu_section' => 'content-menu-section',
            'menu_section_pages' => 'content-menu-section',
        ],
        'searchFields' => 'header,header_link,subheader,bodytext,pi_flexform',
    ],
    'interface' => [
        'always_description' => 0,
    ],
    'columns' => [
        'CType' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.div.standard',
                        '--div--',
                        null,
                        'default',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.I.0',
                        'header',
                        'content-header',
                        'default',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.I.1',
                        'text',
                        'content-text',
                        'default',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.I.2',
                        'textpic',
                        'content-textpic',
                        'default',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.I.3',
                        'image',
                        'content-image',
                        'default',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.textmedia',
                        'textmedia',
                        'content-textmedia',
                        'default',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.div.lists',
                        '--div--',
                        null,
                        'lists',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.I.4',
                        'bullets',
                        'content-bullets',
                        'lists',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.I.5',
                        'table',
                        'content-table',
                        'lists',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.I.6',
                        'uploads',
                        'content-special-uploads',
                        'lists',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.div.menu',
                        '--div--',
                        null,
                        'menu',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.menu_abstract',
                        'menu_abstract',
                        'content-menu-abstract',
                        'menu',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.menu_categorized_content',
                        'menu_categorized_content',
                        'content-menu-categorized',
                        'menu',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.menu_categorized_pages',
                        'menu_categorized_pages',
                        'content-menu-categorized',
                        'menu',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.menu_pages',
                        'menu_pages',
                        'content-menu-pages',
                        'menu',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.menu_subpages',
                        'menu_subpages',
                        'content-menu-pages',
                        'menu',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.menu_recently_updated',
                        'menu_recently_updated',
                        'content-menu-recently-updated',
                        'menu',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.menu_related_pages',
                        'menu_related_pages',
                        'content-menu-related',
                        'menu',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.menu_section',
                        'menu_section',
                        'content-menu-section',
                        'menu',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.menu_section_pages',
                        'menu_section_pages',
                        'content-menu-section',
                        'menu',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.menu_sitemap',
                        'menu_sitemap',
                        'content-menu-sitemap',
                        'menu',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.menu_sitemap_pages',
                        'menu_sitemap_pages',
                        'content-menu-sitemap-pages',
                        'menu',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.div.special',
                        '--div--',
                        null,
                        'special',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.I.13',
                        'shortcut',
                        'content-special-shortcut',
                        'special',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.I.14',
                        'list',
                        'content-plugin',
                        'special',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.I.16',
                        'div',
                        'content-special-div',
                        'special',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.I.17',
                        'html',
                        'content-special-html',
                        'special',
                    ],
                ],
                'itemGroups' => [
                    'default' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.div.standard',
                    'lists' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.div.lists',
                    'menu' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.div.menu',
                    'forms' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.div.forms',
                    'special' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.div.special',
                ],
                'default' => 'text',
                'authMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'],
                'authMode_enforce' => 'strict',
            ],
        ],
        'editlock' => [
            'exclude' => true,
            'displayCond' => 'HIDE_FOR_NON_ADMINS',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:editlock',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
            ],
        ],
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.visible',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        0 => '',
                        'invertStateDisplay' => true,
                    ],
                ],
            ],
        ],
        'starttime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime,int',
                'default' => 0,
            ],
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
        ],
        'endtime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime,int',
                'default' => 0,
                'range' => [
                    'upper' => mktime(0, 0, 0, 1, 1, 2038),
                ],
            ],
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
        ],
        'fe_group' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.fe_group',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 5,
                'maxitems' => 20,
                'items' => [
                    [
                        'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hide_at_login',
                        -1,
                    ],
                    [
                        'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.any_login',
                        -2,
                    ],
                    [
                        'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.usergroups',
                        '--div--',
                    ],
                ],
                'exclusiveKeys' => '-1,-2',
                'foreign_table' => 'fe_groups',
            ],
        ],
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',
            ],
        ],
        'categories' => [
            'config' => [
                'type' => 'category',
            ],
        ],
        'l18n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        '',
                        0,
                    ],
                ],
                'foreign_table' => 'tt_content',
                'foreign_table_where' => 'AND {#tt_content}.{#pid}=###CURRENT_PID### AND {#tt_content}.{#sys_language_uid} IN (-1,0)',
                'default' => 0,
            ],
        ],
        'l10n_source' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'layout' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.layout',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.default_value',
                        '0',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:layout.I.1',
                        '1',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:layout.I.2',
                        '2',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:layout.I.3',
                        '3',
                    ],
                ],
                'default' => 0,
            ],
        ],
        'frame_class' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:frame_class',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:frame_class.default', 'default'],
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:frame_class.ruler_before', 'ruler-before'],
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:frame_class.ruler_after', 'ruler-after'],
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:frame_class.indent', 'indent'],
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:frame_class.indent_left', 'indent-left'],
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:frame_class.indent_right', 'indent-right'],
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:frame_class.none', 'none'],
                ],
                'default' => 'default',
            ],
        ],
        'space_before_class' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:space_before_class',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.default_value', ''],
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:space_class_extra_small', 'extra-small'],
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:space_class_small', 'small'],
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:space_class_medium', 'medium'],
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:space_class_large', 'large'],
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:space_class_extra_large', 'extra-large'],
                ],
                'default' => '',
            ],
        ],
        'space_after_class' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:space_after_class',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.default_value', ''],
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:space_class_extra_small', 'extra-small'],
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:space_class_small', 'small'],
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:space_class_medium', 'medium'],
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:space_class_large', 'large'],
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:space_class_extra_large', 'extra-large'],
                ],
                'default' => '',
            ],
        ],
        'bullets_type' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bullets_type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bullets_type.0', 0],
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bullets_type.1', 1],
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bullets_type.2', 2],
                ],
                'default' => 0,
            ],
        ],
        'colPos' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:colPos',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'itemsProcFunc' => \TYPO3\CMS\Backend\View\BackendLayoutView::class . '->colPosListItemProcFunc',
                'items' => [
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:colPos.I.0',
                        '1',
                    ],
                    [
                        'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.normal',
                        '0',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:colPos.I.2',
                        '2',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:colPos.I.3',
                        '3',
                    ],
                ],
                'default' => 0,
            ],
        ],
        'date' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:date',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'date,int',
                'default' => 0,
            ],
        ],
        'header' => [
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
            ],
        ],
        'header_layout' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.default_value',
                        '0',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_layout.I.1',
                        '1',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_layout.I.2',
                        '2',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_layout.I.3',
                        '3',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_layout.I.4',
                        '4',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_layout.I.5',
                        '5',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_layout.I.6',
                        '100',
                    ],
                ],
                'default' => 0,
            ],
        ],
        'header_position' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_position',
            'exclude' => true,
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.default_value',
                        '',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_position.I.1',
                        'center',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_position.I.2',
                        'right',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_position.I.3',
                        'left',
                    ],
                ],
                'default' => '',
            ],
        ],
        'header_link' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_link',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputLink',
                'size' => 50,
                'max' => 1024,
                'eval' => 'trim',
                'fieldControl' => [
                    'linkPopup' => [
                        'options' => [
                            'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_link_formlabel',
                        ],
                    ],
                ],
                'softref' => 'typolink',
            ],
        ],
        'rowDescription' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.description',
            'config' => [
                'type' => 'text',
                'rows' => 5,
                'cols' => 30,
            ],
        ],
        'subheader' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.subheader',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'softref' => 'email[subst]',
            ],
        ],
        'bodytext' => [
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.text',
            'config' => [
                'type' => 'text',
                'cols' => 80,
                'rows' => 15,
                'softref' => 'typolink_tag,email[subst],url',
                'search' => [
                    'andWhere' => '{#CType}=\'text\' OR {#CType}=\'textpic\' OR {#CType}=\'textmedia\'',
                ],
            ],
        ],
        'image' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.images',
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig('image', [
                'appearance' => [
                    'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference',
                    'showPossibleLocalizationRecords' => true,
                ],
                // custom configuration for displaying fields in the overlay/reference table
                // to use the imageoverlayPalette instead of the basicoverlayPalette
                'overrideChildTca' => [
                    'types' => [
                        '0' => [
                            'showitem' => '
                                --palette--;;imageoverlayPalette,
                                --palette--;;filePalette',
                        ],
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_TEXT => [
                            'showitem' => '
                                --palette--;;imageoverlayPalette,
                                --palette--;;filePalette',
                        ],
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE => [
                            'showitem' => '
                                --palette--;;imageoverlayPalette,
                                --palette--;;filePalette',
                        ],
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_AUDIO => [
                            'showitem' => '
                                --palette--;;audioOverlayPalette,
                                --palette--;;filePalette',
                        ],
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_VIDEO => [
                            'showitem' => '
                                --palette--;;videoOverlayPalette,
                                --palette--;;filePalette',
                        ],
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_APPLICATION => [
                            'showitem' => '
                                --palette--;;imageoverlayPalette,
                                --palette--;;filePalette',
                        ],
                    ],
                ],
            ], $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']),
        ],
        'assets' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/Database.xlf:tt_content.asset_references',
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig('assets', [
                'appearance' => [
                    'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/Database.xlf:tt_content.asset_references.addFileReference',
                    'showPossibleLocalizationRecords' => true,
                ],
                // custom configuration for displaying fields in the overlay/reference table
                // behaves the same as the image field.
                'overrideChildTca' => [
                    'types' => [
                        '0' => [
                            'showitem' => '
                                --palette--;;imageoverlayPalette,
                                --palette--;;filePalette',
                        ],
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_TEXT => [
                            'showitem' => '
                                --palette--;;imageoverlayPalette,
                                --palette--;;filePalette',
                        ],
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE => [
                            'showitem' => '
                                --palette--;;imageoverlayPalette,
                                --palette--;;filePalette',
                        ],
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_AUDIO => [
                            'showitem' => '
                                --palette--;;audioOverlayPalette,
                                --palette--;;filePalette',
                        ],
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_VIDEO => [
                            'showitem' => '
                                --palette--;;videoOverlayPalette,
                                --palette--;;filePalette',
                        ],
                        \TYPO3\CMS\Core\Resource\File::FILETYPE_APPLICATION => [
                            'showitem' => '
                                --palette--;;imageoverlayPalette,
                                --palette--;;filePalette',
                        ],
                    ],
                ],
            ], $GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext']),
        ],
        'imagewidth' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imagewidth',
            'config' => [
                'type' => 'input',
                'size' => 4,
                'eval' => 'int',
                'range' => [
                    'lower' => 0,
                ],
                'default' => 0,
            ],
        ],
        'imageheight' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageheight',
            'config' => [
                'type' => 'input',
                'size' => 4,
                'eval' => 'int',
                'range' => [
                    'lower' => 0,
                ],
                'default' => 0,
            ],
        ],
        'imageorient' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.0',
                        0,
                        'content-beside-text-img-above-center',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.1',
                        1,
                        'content-beside-text-img-above-right',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.2',
                        2,
                        'content-beside-text-img-above-left',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.3',
                        8,
                        'content-beside-text-img-below-center',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.4',
                        9,
                        'content-beside-text-img-below-right',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.5',
                        10,
                        'content-beside-text-img-below-left',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.6',
                        17,
                        'content-inside-text-img-right',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.7',
                        18,
                        'content-inside-text-img-left',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.9',
                        25,
                        'content-beside-text-img-right',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.10',
                        26,
                        'content-beside-text-img-left',
                    ],
                ],
                'default' => 0,
                'fieldWizard' => [
                    'selectIcons' => [
                        'disabled' => false,
                    ],
                ],
            ],
        ],
        'imageborder' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageborder',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
            ],
        ],
        'image_zoom' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_zoom',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
            ],
        ],
        'imagecols' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imagecols',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        '1',
                        1,
                    ],
                    [
                        '2',
                        2,
                    ],
                    [
                        '3',
                        3,
                    ],
                    [
                        '4',
                        4,
                    ],
                    [
                        '5',
                        5,
                    ],
                    [
                        '6',
                        6,
                    ],
                    [
                        '7',
                        7,
                    ],
                    [
                        '8',
                        8,
                    ],
                ],
                'default' => 2,
            ],
        ],
        'cols' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:cols',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:cols.I.0',
                        '0',
                    ],
                    [
                        '1',
                        '1',
                    ],
                    [
                        '2',
                        '2',
                    ],
                    [
                        '3',
                        '3',
                    ],
                    [
                        '4',
                        '4',
                    ],
                    [
                        '5',
                        '5',
                    ],
                    [
                        '6',
                        '6',
                    ],
                    [
                        '7',
                        '7',
                    ],
                    [
                        '8',
                        '8',
                    ],
                    [
                        '9',
                        '9',
                    ],
                ],
                'default' => 0,
            ],
        ],
        'pages' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.startingpoint',
            'config' => [
                'type' => 'group',
                'allowed' => 'pages',
                'size' => 3,
                'maxitems' => 50,
            ],
        ],
        'recursive' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.recursive',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:recursive.I.0',
                        '0',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:recursive.I.1',
                        '1',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:recursive.I.2',
                        '2',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:recursive.I.3',
                        '3',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:recursive.I.4',
                        '4',
                    ],
                    [
                        'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:recursive.I.5',
                        '250',
                    ],
                ],
                'default' => 0,
            ],
        ],
        'list_type' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:list_type',
            'onChange' => 'reload',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        '',
                        '',
                        '',
                        'none',
                    ],
                ],
                'itemGroups' => [
                    'default' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.default_value',
                ],
                'sortItems' => [
                    'label' => 'asc',
                ],
                'default' => '',
                'authMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'],
                'authMode_enforce' => 'strict',
            ],
        ],
        'file_collections' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:file_collections',
            'config' => [
                'type' => 'group',
                'localizeReferencesAtParentLocalization' => true,
                'allowed' => 'sys_file_collection',
                'foreign_table' => 'sys_file_collection',
                'size' => 5,
                'fieldControl' => [
                    'addRecord' => [
                        'disabled' => false,
                        'options' => [
                            'title' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:file_mountpoints_add_title',
                            'setValue' => 'prepend',
                        ],
                    ],
                ],
            ],
        ],
        'media' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:media',
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig('media', [
                'appearance' => [
                    'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:media.addFileReference',
                    'showPossibleLocalizationRecords' => true,
                ],
            ]),
        ],
        'filelink_size' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:filelink_size',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
            ],
        ],
        'filelink_sorting' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:filelink_sorting',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:filelink_sorting.none', ''],
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:filelink_sorting.extension', 'extension'],
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:filelink_sorting.name', 'name'],
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:filelink_sorting.type', 'type'],
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:filelink_sorting.size', 'size'],
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:filelink_sorting.creation_date', 'creation_date'],
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:filelink_sorting.modification_date', 'modification_date'],
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:filelink_sorting.title', 'title'],
                ],
            ],
        ],
        'filelink_sorting_direction' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:filelink_sorting_direction',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', ''],
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:filelink_sorting_direction.ascending', 'asc'],
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:filelink_sorting_direction.descending', 'desc'],
                ],
            ],
        ],
        'target' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:target',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim',
                'valuePicker' => [
                    'items' => [
                        [ 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:target.I.1', '_blank' ],
                    ],
                ],
                'default' => '',
            ],
        ],
        'records' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:records',
            'config' => [
                'type' => 'group',
                'allowed' => 'tt_content',
                'size' => 5,
                'maxitems' => 200,
            ],
        ],
        'sectionIndex' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:sectionIndex',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 1,
            ],
        ],
        'linkToTop' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:linkToTop',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
            ],
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
                    ',
                ],
                'search' => [
                    'andWhere' => '{#CType}=\'list\'',
                ],
            ],
        ],
        'accessibility_title' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:accessibility_title',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
        'accessibility_bypass' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:accessibility_bypass',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
            ],
        ],
        'accessibility_bypass_text' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:accessibility_bypass_text',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim',
                'default' => '',
            ],
        ],
        'l18n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => '',
            ],
        ],
        'selected_categories' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:selected_categories',
            'config' => [
                'type' => 'category',
                'relationship' => 'oneToMany',
            ],
        ],
        'category_field' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:category_field',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'maxitems' => 1,
                'itemsProcFunc' => \TYPO3\CMS\Core\Hooks\TcaItemsProcessorFunctions::class . '->populateAvailableCategoryFields',
                'itemsProcConfig' => [
                    'table' => 'tt_content',
                ],
            ],
        ],
        'table_class' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/Database.xlf:tt_content.table_class',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:frontend/Resources/Private/Language/Database.xlf:tt_content.table_class.default', ''],
                    ['LLL:EXT:frontend/Resources/Private/Language/Database.xlf:tt_content.table_class.striped', 'striped'],
                    ['LLL:EXT:frontend/Resources/Private/Language/Database.xlf:tt_content.table_class.bordered', 'bordered'],
                ],
                'default' => '',
            ],
        ],
        'table_caption' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/Database.xlf:tt_content.table_caption',
            'config' => [
                'type' => 'input',
            ],
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
                    ['LLL:EXT:frontend/Resources/Private/Language/Database.xlf:tt_content.table_delimiter.9', 9],
                ],
                'default' => 124,
            ],
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
                    ['LLL:EXT:frontend/Resources/Private/Language/Database.xlf:tt_content.table_enclosure.34', 34],
                ],
                'default' => 0,
            ],
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
                    ['LLL:EXT:frontend/Resources/Private/Language/Database.xlf:tt_content.table_header_position.2', 2],
                ],
                'default' => 0,
            ],
        ],
        'table_tfoot' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/Database.xlf:tt_content.table_tfoot',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
            ],
        ],
        'uploads_description' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/Database.xlf:tt_content.uploads_description',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
            ],
        ],
        'uploads_type' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/Database.xlf:tt_content.uploads_type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:frontend/Resources/Private/Language/Database.xlf:tt_content.uploads_type.0', 0],
                    ['LLL:EXT:frontend/Resources/Private/Language/Database.xlf:tt_content.uploads_type.1', 1],
                    ['LLL:EXT:frontend/Resources/Private/Language/Database.xlf:tt_content.uploads_type.2', 2],
                ],
                'default' => 0,
            ],
        ],
    ],
    'types' => [
        '1' => [
            'showitem' => 'CType',
        ],
        'bullets' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    --palette--;;general,
                    --palette--;;headers,
                    bullets_type,
                    bodytext;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext.ALT.bulletlist_formlabel,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    rowDescription,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
            'columnsOverrides' => [
                'bodytext' => [
                    'config' => [
                        'wrap' => 'off',
                    ],
                ],
            ],
        ],
        'div' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    --palette--;;general,
                    header;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header.ALT.div_formlabel,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    rowDescription,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
            'columnsOverrides' => [
                'header' => [
                    'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header.description.ALT',
                ],
            ],
        ],
        'header' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    --palette--;;general,
                    --palette--;;headers,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    rowDescription,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
        ],
        'text' => [
            'previewRenderer' => \TYPO3\CMS\Frontend\Preview\TextPreviewRenderer::class,
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    --palette--;;general,
                    --palette--;;headers,
                    bodytext;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext_formlabel,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    rowDescription,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
            'columnsOverrides' => [
                'bodytext' => [
                    'config' => [
                        'enableRichtext' => true,
                    ],
                ],
            ],
        ],
        'textpic' => [
            'previewRenderer' => \TYPO3\CMS\Frontend\Preview\TextpicPreviewRenderer::class,
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    --palette--;;general,
                    --palette--;;headers,
                    bodytext;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext_formlabel,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.images,
                    image,
                    --palette--;;mediaAdjustments,
                    --palette--;;gallerySettings,
                    --palette--;;imagelinks,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    rowDescription,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
            'columnsOverrides' => [
                'bodytext' => [
                    'config' => [
                        'enableRichtext' => true,
                    ],
                ],
            ],
        ],
        'textmedia' => [
            'previewRenderer' => \TYPO3\CMS\Frontend\Preview\TextmediaPreviewRenderer::class,
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    --palette--;;general,
                    --palette--;;headers,
                    bodytext;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext_formlabel,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.media,
                    assets,
                    --palette--;;mediaAdjustments,
                    --palette--;;gallerySettings,
                    --palette--;;imagelinks,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    rowDescription,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
            'columnsOverrides' => [
                'bodytext' => [
                    'config' => [
                        'enableRichtext' => true,
                    ],
                ],
            ],
        ],
        'image' => [
            'previewRenderer' => \TYPO3\CMS\Frontend\Preview\ImagePreviewRenderer::class,
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    --palette--;;general,
                    --palette--;;headers,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.images,
                    image,
                    --palette--;;mediaAdjustments,
                    --palette--;;gallerySettings,
                    --palette--;;imagelinks,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    rowDescription,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
        ],
        'html' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    --palette--;;general,
                    header;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header.ALT.html_formlabel,
                    bodytext;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext.ALT.html_formlabel,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    rowDescription,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
            'columnsOverrides' => [
                'header' => [
                    'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header.description.ALT',
                ],
            ],
        ],
        'list' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    --palette--;;general,
                    --palette--;;headers,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.plugin,
                    list_type;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:list_type_formlabel,
                    pages;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:pages.ALT.list_formlabel,
                    recursive,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    rowDescription,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
            'subtype_value_field' => 'list_type',
        ],
        'menu_categorized_pages' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    --palette--;;general,
                    --palette--;;headers,
                    selected_categories,
                    category_field,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.accessibility,
                    --palette--;;menu_accessibility,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    rowDescription,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
            'columnsOverrides' => [
                'selected_categories' => [
                    'config' => [
                        'minitems' => 1,
                    ],
                ],
                'category_field' => [
                    'config' => [
                        'itemsProcConfig' => [
                            'table' => 'pages',
                        ],
                    ],
                ],
            ],
        ],
        'menu_categorized_content' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    --palette--;;general,
                    --palette--;;headers,
                    selected_categories,
                    category_field,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.accessibility,
                    --palette--;;menu_accessibility,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    rowDescription,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
            'columnsOverrides' => [
                'selected_categories' => [
                    'config' => [
                        'minitems' => 1,
                    ],
                ],
                'category_field' => [
                    'config' => [
                        'itemsProcConfig' => [
                            'table' => 'tt_content',
                        ],
                    ],
                ],
            ],
        ],
        'menu_pages' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    --palette--;;general,
                    --palette--;;headers,
                    pages;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:pages.ALT.menu_formlabel,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.accessibility,
                    --palette--;;menu_accessibility,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    rowDescription,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
        ],
        'menu_subpages' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    --palette--;;general,
                    --palette--;;headers,
                    pages;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:pages.ALT.menu_formlabel,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.accessibility,
                    --palette--;;menu_accessibility,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    rowDescription,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
        ],
        'menu_sitemap' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    --palette--;;general,
                    --palette--;;headers,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.accessibility,
                    --palette--;;menu_accessibility,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    rowDescription,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
        ],
        'menu_section' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    --palette--;;general,
                    --palette--;;headers,
                    pages;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:pages.ALT.menu_formlabel,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.accessibility,
                    --palette--;;menu_accessibility,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    rowDescription,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
        ],
        'menu_abstract' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    --palette--;;general,
                    --palette--;;headers,
                    pages;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:pages.ALT.menu_formlabel,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.accessibility,
                    --palette--;;menu_accessibility,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    rowDescription,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
        ],
        'menu_recently_updated' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    --palette--;;general,
                    --palette--;;headers,
                    pages;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:pages.ALT.menu_formlabel,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.accessibility,
                    --palette--;;menu_accessibility,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    rowDescription,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
        ],
        'menu_related_pages' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    --palette--;;general,
                    --palette--;;headers,
                    pages;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:pages.ALT.menu_formlabel,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.accessibility,
                    --palette--;;menu_accessibility,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    rowDescription,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
        ],
        'menu_section_pages' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    --palette--;;general,
                    --palette--;;headers,
                    pages;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:pages.ALT.menu_formlabel,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.accessibility,
                    --palette--;;menu_accessibility,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    rowDescription,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
        ],
        'menu_sitemap_pages' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    --palette--;;general,
                    --palette--;;headers,
                    pages;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:pages.ALT.menu_formlabel,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.accessibility,
                    --palette--;;menu_accessibility,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    rowDescription,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
        ],
        'shortcut' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    --palette--;;general,
                    header;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header.ALT.shortcut_formlabel,
                    records;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:records_formlabel,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    rowDescription,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
            'columnsOverrides' => [
                'header' => [
                    'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header.description.ALT',
                ],
            ],
        ],
        'table' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    --palette--;;general,
                    --palette--;;headers,
                    bodytext;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:field.table.bodytext,
                    --palette--;;tableconfiguration,
                    table_caption,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
                    --palette--;;frames,
                    --palette--;;tablelayout,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    rowDescription,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
            'columnsOverrides' => [
                'bodytext' => [
                    'config' => [
                        'renderType' => 'textTable',
                        'wrap' => 'off',
                    ],
                ],
            ],
        ],
        'uploads' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    --palette--;;general,
                    --palette--;;headers,
                    --palette--;;uploads,
                    --palette--;;uploadslayout,
                --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                    rowDescription,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
        ],
    ],
    'palettes' => [
        'general' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general',
            'showitem' => '
                CType;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType_formlabel,
                colPos;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:colPos_formlabel,
            ',
        ],
        'header' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.header',
            'showitem' => '
                header;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_formlabel,
                --linebreak--,
                header_layout;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_layout_formlabel,
                header_position;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_position_formlabel,
                date;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:date_formlabel,
                --linebreak--,
                header_link;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_link_formlabel
            ',
        ],
        'headers' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.headers',
            'showitem' => '
                header;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_formlabel,
                --linebreak--,
                header_layout;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_layout_formlabel,
                header_position;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_position_formlabel,
                date;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:date_formlabel,
                --linebreak--,
                header_link;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_link_formlabel,
                --linebreak--,
                subheader;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:subheader_formlabel
            ',
        ],
        'gallerySettings' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/Database.xlf:tt_content.palette.gallerySettings',
            'showitem' => '
                imageorient;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient_formlabel,
                imagecols;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imagecols_formlabel
            ',
        ],
        'mediaAdjustments' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/Database.xlf:tt_content.palette.mediaAdjustments',
            'showitem' => '
                imagewidth;LLL:EXT:frontend/Resources/Private/Language/Database.xlf:tt_content.palette.mediaAdjustments.imagewidth,
                imageheight;LLL:EXT:frontend/Resources/Private/Language/Database.xlf:tt_content.palette.mediaAdjustments.imageheight,
                imageborder;LLL:EXT:frontend/Resources/Private/Language/Database.xlf:tt_content.palette.mediaAdjustments.imageborder
            ',
        ],
        'imagelinks' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.imagelinks',
            'showitem' => '
                image_zoom;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_zoom_formlabel
            ',
        ],
        'uploads' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:media',
            'showitem' => '
                media;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:media.ALT.uploads_formlabel,
                --linebreak--,
                file_collections;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:file_collections.ALT.uploads_formlabel,
                --linebreak--,
                filelink_sorting,
                filelink_sorting_direction,
                target
            ',
        ],
        'menu_accessibility' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.menu_accessibility',
            'showitem' => '
                accessibility_title;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:menu.ALT.accessibility_title_formlabel,
                --linebreak--,
                accessibility_bypass;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:menu.ALT.accessibility_bypass_formlabel,
                accessibility_bypass_text;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:menu.ALT.accessibility_bypass_text_formlabel
            ',
        ],
        'hidden' => [
            'showitem' => '
                hidden;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:field.default.hidden
            ',
        ],
        'language' => [
            'showitem' => '
                sys_language_uid;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:sys_language_uid_formlabel,l18n_parent
            ',
        ],
        'access' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access',
            'showitem' => '
                starttime;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:starttime_formlabel,
                endtime;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:endtime_formlabel,
                --linebreak--,
                fe_group;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:fe_group_formlabel,
                --linebreak--,editlock
            ',
        ],
        'uploadslayout' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.uploads_layout',
            'showitem' => '
                filelink_size;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:filelink_size_formlabel,
                uploads_description,
                uploads_type
            ',
        ],
        'appearanceLinks' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.appearanceLinks',
            'showitem' => '
                sectionIndex;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:sectionIndex_formlabel,
                linkToTop;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:linkToTop_formlabel
            ',
        ],
        'frames' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.frames',
            'showitem' => '
                layout;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:layout_formlabel,
                frame_class;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:frame_class_formlabel,
                space_before_class;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:space_before_class_formlabel,
                space_after_class;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:space_after_class_formlabel,
            ',
        ],
        'tableconfiguration' => [
            'showitem' => '
                table_delimiter,
                table_enclosure
            ',
        ],
        'tablelayout' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.table_layout',
            'showitem' => '
                cols,
                table_class,
                table_header_position,
                table_tfoot
            ',
        ],
    ],
];

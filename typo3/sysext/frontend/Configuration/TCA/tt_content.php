<?php

return [
    'ctrl' => [
        'label' => 'header',
        'label_alt' => 'subheader,bodytext',
        'descriptionColumn' => 'rowDescription',
        'sortby' => 'sorting',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'editlock' => 'editlock',
        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:tt_content',
        'delete' => 'deleted',
        'versioningWS' => true,
        'groupName' => 'content',
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
            'default' => 'mimetypes-x-content-text',
        ],
        'searchFields' => 'header,header_link,subheader,bodytext,pi_flexform',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'CType' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.type',
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.header',
                        'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.header.description',
                        'value' => 'header',
                        'icon' => 'content-header',
                        'group' => 'default',
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.text',
                        'description' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.text.description',
                        'value' => 'text',
                        'icon' => 'content-text',
                        'group' => 'default',
                    ],
                ],
                'itemGroups' => [
                    'default' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.default',
                    'lists' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.lists',
                    'menu' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.menu',
                    'forms' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.forms',
                    'special' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.special',
                    'plugins' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:group.plugins',
                ],
                'default' => 'text',
                'authMode' => 'explicitAllow',
                'dbFieldLength' => 255,
            ],
        ],
        'categories' => [
            'config' => [
                'type' => 'category',
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
                        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.default_value',
                        'value' => '0',
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:layout.I.1',
                        'value' => '1',
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:layout.I.2',
                        'value' => '2',
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:layout.I.3',
                        'value' => '3',
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
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:frame_class.default', 'value' => 'default'],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:frame_class.ruler_before', 'value' => 'ruler-before'],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:frame_class.ruler_after', 'value' => 'ruler-after'],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:frame_class.indent', 'value' => 'indent'],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:frame_class.indent_left', 'value' => 'indent-left'],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:frame_class.indent_right', 'value' => 'indent-right'],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:frame_class.none', 'value' => 'none'],
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
                    ['label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.default_value', 'value' => ''],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:space_class_extra_small', 'value' => 'extra-small'],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:space_class_small', 'value' => 'small'],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:space_class_medium', 'value' => 'medium'],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:space_class_large', 'value' => 'large'],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:space_class_extra_large', 'value' => 'extra-large'],
                ],
                'default' => '',
                'dbFieldLength' => 60,
            ],
        ],
        'space_after_class' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:space_after_class',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.default_value', 'value' => ''],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:space_class_extra_small', 'value' => 'extra-small'],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:space_class_small', 'value' => 'small'],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:space_class_medium', 'value' => 'medium'],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:space_class_large', 'value' => 'large'],
                    ['label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:space_class_extra_large', 'value' => 'extra-large'],
                ],
                'default' => '',
                'dbFieldLength' => 60,
            ],
        ],
        'colPos' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:colPos',
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'itemsProcFunc' => \TYPO3\CMS\Backend\View\BackendLayoutView::class . '->colPosListItemProcFunc',
                'items' => [
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:colPos.I.0',
                        'value' => '1',
                    ],
                    [
                        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.normal',
                        'value' => '0',
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:colPos.I.2',
                        'value' => '2',
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:colPos.I.3',
                        'value' => '3',
                    ],
                ],
                'default' => 0,
            ],
        ],
        'date' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:date',
            'config' => [
                'type' => 'datetime',
                'format' => 'date',
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
                        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.default_value',
                        'value' => '0',
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_layout.I.1',
                        'value' => '1',
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_layout.I.2',
                        'value' => '2',
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_layout.I.3',
                        'value' => '3',
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_layout.I.4',
                        'value' => '4',
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_layout.I.5',
                        'value' => '5',
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_layout.I.6',
                        'value' => '100',
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
                        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.default_value',
                        'value' => '',
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_position.I.1',
                        'value' => 'center',
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_position.I.2',
                        'value' => 'right',
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_position.I.3',
                        'value' => 'left',
                    ],
                ],
                'default' => '',
                'dbFieldLength' => 255,
            ],
        ],
        'header_link' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_link',
            'config' => [
                'type' => 'link',
                'size' => 50,
                'appearance' => [
                    'browserTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_link_formlabel',
                ],
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
                'cols' => 50,
                'rows' => 15,
                'softref' => 'typolink_tag,email[subst],url',
                'search' => [
                    'andWhere' => '{#CType}=\'text\' OR {#CType}=\'textpic\' OR {#CType}=\'textmedia\'',
                ],
            ],
        ],
        'image' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.images',
            'config' => [
                'type' => 'file',
                'allowed' => 'common-image-types',
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
                        \TYPO3\CMS\Core\Resource\FileType::TEXT->value => [
                            'showitem' => '
                                --palette--;;imageoverlayPalette,
                                --palette--;;filePalette',
                        ],
                        \TYPO3\CMS\Core\Resource\FileType::IMAGE->value => [
                            'showitem' => '
                                --palette--;;imageoverlayPalette,
                                --palette--;;filePalette',
                        ],
                        \TYPO3\CMS\Core\Resource\FileType::AUDIO->value => [
                            'showitem' => '
                                --palette--;;audioOverlayPalette,
                                --palette--;;filePalette',
                        ],
                        \TYPO3\CMS\Core\Resource\FileType::VIDEO->value => [
                            'showitem' => '
                                --palette--;;videoOverlayPalette,
                                --palette--;;filePalette',
                        ],
                        \TYPO3\CMS\Core\Resource\FileType::APPLICATION->value => [
                            'showitem' => '
                                --palette--;;imageoverlayPalette,
                                --palette--;;filePalette',
                        ],
                    ],
                ],
            ],
        ],
        // @todo While "assets" is used only by CType:textmedia, we keep it here as it's a common field for reuse in custom CTypes
        'assets' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:asset_references',
            'config' => [
                'type' => 'file',
                'allowed' => 'common-media-types',
                'appearance' => [
                    'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:asset_references.addFileReference',
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
                        \TYPO3\CMS\Core\Resource\FileType::TEXT->value => [
                            'showitem' => '
                                --palette--;;imageoverlayPalette,
                                --palette--;;filePalette',
                        ],
                        \TYPO3\CMS\Core\Resource\FileType::IMAGE->value => [
                            'showitem' => '
                                --palette--;;imageoverlayPalette,
                                --palette--;;filePalette',
                        ],
                        \TYPO3\CMS\Core\Resource\FileType::AUDIO->value => [
                            'showitem' => '
                                --palette--;;audioOverlayPalette,
                                --palette--;;filePalette',
                        ],
                        \TYPO3\CMS\Core\Resource\FileType::VIDEO->value => [
                            'showitem' => '
                                --palette--;;videoOverlayPalette,
                                --palette--;;filePalette',
                        ],
                        \TYPO3\CMS\Core\Resource\FileType::APPLICATION->value => [
                            'showitem' => '
                                --palette--;;imageoverlayPalette,
                                --palette--;;filePalette',
                        ],
                    ],
                ],
            ],
        ],
        'imagewidth' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imagewidth',
            'config' => [
                'type' => 'number',
                'size' => 4,
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
                'type' => 'number',
                'size' => 4,
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
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.0',
                        'value' => 0,
                        'icon' => 'content-beside-text-img-above-center',
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.1',
                        'value' => 1,
                        'icon' => 'content-beside-text-img-above-right',
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.2',
                        'value' => 2,
                        'icon' => 'content-beside-text-img-above-left',
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.3',
                        'value' => 8,
                        'icon' => 'content-beside-text-img-below-center',
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.4',
                        'value' => 9,
                        'icon' => 'content-beside-text-img-below-right',
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.5',
                        'value' => 10,
                        'icon' => 'content-beside-text-img-below-left',
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.6',
                        'value' => 17,
                        'icon' => 'content-inside-text-img-right',
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.7',
                        'value' => 18,
                        'icon' => 'content-inside-text-img-left',
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.9',
                        'value' => 25,
                        'icon' => 'content-beside-text-img-right',
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient.I.10',
                        'value' => 26,
                        'icon' => 'content-beside-text-img-left',
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
                    ['label' => '1', 'value' => 1],
                    ['label' => '2', 'value' => 2],
                    ['label' => '3', 'value' => 3],
                    ['label' => '4', 'value' => 4],
                    ['label' => '5', 'value' => 5],
                    ['label' => '6', 'value' => 6],
                    ['label' => '7', 'value' => 7],
                    ['label' => '8', 'value' => 8],
                ],
                'default' => 2,
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
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:recursive.I.0',
                        'value' => '0',
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:recursive.I.1',
                        'value' => '1',
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:recursive.I.2',
                        'value' => '2',
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:recursive.I.3',
                        'value' => '3',
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:recursive.I.4',
                        'value' => '4',
                    ],
                    [
                        'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:recursive.I.5',
                        'value' => '250',
                    ],
                ],
                'default' => 0,
            ],
        ],
        // @todo While "media" is used only by CType:uploads, we keep it here as it's a common field for reuse in custom CTypes
        'media' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:media',
            'config' => [
                'type' => 'file',
                'appearance' => [
                    'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:media.addFileReference',
                    'showPossibleLocalizationRecords' => true,
                ],
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
                // @todo this is a hack and will be handled separately together with EMU::addPiFlexFormValue
                'ds_pointerField' => 'CType,CType',
                'ds' => [
                    'default' => '
                        <T3DataStructure>
                          <ROOT>
                            <type>array</type>
                            <el>
                                <!-- Repeat an element like "xmlTitle" beneath for as many elements you like. Remember to name them uniquely  -->
                              <xmlTitle>
                                <label>The Title:</label>
                                <config>
                                    <type>input</type>
                                    <size>48</size>
                                </config>
                              </xmlTitle>
                            </el>
                          </ROOT>
                        </T3DataStructure>
                    ',
                ],
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
                'itemsProcFunc' => \TYPO3\CMS\Core\Hooks\TcaItemsProcessorFunctions::class . '->populateAvailableCategoryFields',
                'itemsProcConfig' => [
                    'table' => 'tt_content',
                ],
                'dbFieldLength' => 64,
            ],
        ],
    ],
    'types' => [
        // @todo we should get rid of this FormEngine dependency - see DatabaseRecordTypeValue
        '1' => [
            'showitem' => '',
        ],
        // @todo header has to be kept, due to ExtensionManagementUtility::addPlugin()
        'header' => [
            'showitem' => '
                    --palette--;;headers,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
        ],
        // @todo text has to be kept, due to its use as "default" CType
        'text' => [
            'showitem' => '
                    --palette--;;headers,
                    bodytext;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext_formlabel,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
                    categories,
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
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.gallerySettings',
            'showitem' => '
                imageorient;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imageorient_formlabel,
                imagecols;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:imagecols_formlabel
            ',
        ],
        'mediaAdjustments' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.mediaAdjustments',
            'showitem' => '
                imagewidth;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.mediaAdjustments.imagewidth,
                imageheight;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.mediaAdjustments.imageheight,
                imageborder;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.mediaAdjustments.imageborder
            ',
        ],
        'imagelinks' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.imagelinks',
            'showitem' => '
                image_zoom;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_zoom_formlabel
            ',
        ],
        'hidden' => [
            'showitem' => '
                hidden;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:field.default.hidden
            ',
        ],
        'language' => [
            'showitem' => '
                sys_language_uid,l18n_parent
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
    ],
];

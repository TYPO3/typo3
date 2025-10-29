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
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'columns' => [
        'CType' => [
            'label' => 'frontend.db.tt_content:type',
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
            'label' => 'frontend.db.tt_content:layout',
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
            'label' => 'frontend.db.tt_content:frame_class',
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
            'label' => 'frontend.db.tt_content:space_before_class',
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
            'label' => 'frontend.db.tt_content:space_after_class',
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
            'label' => 'frontend.db.tt_content:column',
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
            'label' => 'frontend.db.tt_content:date',
            'config' => [
                'type' => 'datetime',
                'format' => 'date',
                'default' => 0,
            ],
        ],
        'header' => [
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'frontend.db.tt_content:header',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
            ],
        ],
        'header_layout' => [
            'exclude' => true,
            'label' => 'frontend.db.tt_content:header_type',
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
            'label' => 'frontend.db.tt_content:header_position',
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
            'label' => 'frontend.db.tt_content:header_link',
            'config' => [
                'type' => 'link',
                'size' => 50,
                'appearance' => [
                    'browserTitle' => 'frontend.db.tt_content:header_link',
                ],
            ],
        ],
        'subheader' => [
            'exclude' => true,
            'label' => 'frontend.db.tt_content:subheader',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'softref' => 'email[subst]',
            ],
        ],
        'bodytext' => [
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'frontend.db.tt_content:bodytext',
            'config' => [
                'type' => 'text',
                'cols' => 50,
                'rows' => 15,
                'softref' => 'typolink_tag,email[subst],url',
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
            ],
        ],
        'imagewidth' => [
            'exclude' => true,
            'label' => 'frontend.db.tt_content:imagewidth',
            'config' => [
                'type' => 'number',
                'size' => 4,
                'range' => [
                    'lower' => 1,
                ],
                'nullable' => true,
                'default' => null,
            ],
        ],
        'imageheight' => [
            'exclude' => true,
            'label' => 'frontend.db.tt_content:imageheight',
            'config' => [
                'type' => 'number',
                'size' => 4,
                'range' => [
                    'lower' => 1,
                ],
                'nullable' => true,
                'default' => null,
            ],
        ],
        'imageorient' => [
            'exclude' => true,
            'label' => 'frontend.db.tt_content:imageorientation',
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
            'label' => 'frontend.db.tt_content:imageborder',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
            ],
        ],
        'image_zoom' => [
            'exclude' => true,
            'label' => 'frontend.db.tt_content:image_zoom',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
            ],
        ],
        'imagecols' => [
            'exclude' => true,
            'label' => 'frontend.db.tt_content:imagecols',
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
            'label' => 'frontend.db.tt_content:section_index',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 1,
            ],
        ],
        'linkToTop' => [
            'exclude' => true,
            'label' => 'frontend.db.tt_content:link_to_top',
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
                'ds' => '
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
                --div--;core.form.tabs:appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;core.form.tabs:categories,
                    categories,
                --div--;core.form.tabs:extended,
            ',
        ],
        // @todo text has to be kept, due to its use as "default" CType
        'text' => [
            'showitem' => '
                    --palette--;;headers,
                    bodytext,
                --div--;core.form.tabs:appearance,
                    --palette--;;frames,
                    --palette--;;appearanceLinks,
                --div--;core.form.tabs:categories,
                    categories,
                --div--;core.form.tabs:extended,
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
            'label' => 'core.form.palettes:general',
            'showitem' => 'CType,colPos',
        ],
        'header' => [
            'label' => 'core.form.palettes:header',
            'showitem' => '
                header,
                --linebreak--,header_layout,header_position,date,
                --linebreak--,header_link
            ',
        ],
        'headers' => [
            'label' => 'core.form.palettes:headers',
            'showitem' => '
                header,
                --linebreak--,header_layout,header_position,date,
                --linebreak--,header_link,
                --linebreak--,subheader
            ',
        ],
        'gallerySettings' => [
            'label' => 'core.form.palettes:settings_gallery',
            'showitem' => 'imageorient,imagecols',
        ],
        'mediaAdjustments' => [
            'label' => 'core.form.palettes:media_adjustments',
            'showitem' => 'imagewidth,imageheight,imageborder',
        ],
        'imagelinks' => [
            'label' => 'core.form.palettes:media_behaviour',
            'showitem' => 'image_zoom',
        ],
        'hidden' => [
            'showitem' => 'hidden;frontend.db.tt_content:hidden',
        ],
        'language' => [
            'showitem' => 'sys_language_uid,l18n_parent',
        ],
        'access' => [
            'label' => 'core.form.palettes:access',
            'showitem' => '
                starttime;core.db.general:starttime, endtime;core.db.general:endtime,
                --linebreak--, fe_group;core.db.general:fe_group,
                --linebreak--, editlock',
        ],
        'appearanceLinks' => [
            'label' => 'core.form.palettes:links_appearance',
            'showitem' => 'sectionIndex,linkToTop',
        ],
        'frames' => [
            'label' => 'core.form.palettes:content_layout',
            'showitem' => 'layout,frame_class,space_before_class,space_after_class',
        ],
    ],
];

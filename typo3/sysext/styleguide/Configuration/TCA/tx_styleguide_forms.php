<?php
return array(
    'ctrl' => array(
        'title' => 'Form engine tests - Top record',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'default_sortby' => 'ORDER BY crdate',
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide_forms.svg',

        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',

        'enablecolumns' => array(
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ),
    ),

    'columns' => array(
        'hidden' => array(
            'exclude' => 1,
            'config' => array(
                'type' => 'check',
                'items' => array(
                    '1' => array(
                        '0' => 'Disable',
                    ),
                ),
            ),
        ),
        'starttime' => array(
            'exclude' => 1,
            'label' => 'Publish Date',
            'config' => array(
                'type' => 'input',
                'size' => '13',
                'max' => '20',
                'eval' => 'datetime',
                'default' => '0'
            ),
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly'
        ),
        'endtime' => array(
            'exclude' => 1,
            'label' => 'Expiration Date',
            'config' => array(
                'type' => 'input',
                'size' => '13',
                'max' => '20',
                'eval' => 'datetime',
                'default' => '0',
                'range' => array(
                    'upper' => mktime(0, 0, 0, 12, 31, 2020)
                )
            ),
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly'
        ),


        'flex_3' => array(
            'exclude' => 1,
            'label' => 'FLEX: 3 complex flexform in an external file',
            'config' => array(
                'type' => 'flex',
                'ds' => array(
                    'default' => 'FILE:EXT:styleguide/Configuration/Flexform/Flex_3.xml',
                ),
            ),
        ),
        'flex_5' => array(
            'exclude' => 1,
            'label' => 'FLEX: 5 condition',
            'config' => array(
                'type' => 'flex',
                'ds' => array(
                    'default' => 'FILE:EXT:styleguide/Configuration/Flexform/Condition.xml',
                ),
            ),
        ),


        'inline_1' => array(
            'exclude' => 1,
            'label' => 'IRRE: 1 typical FAL field',
            'config' => array(
                'type' => 'inline',
                'foreign_table' => 'sys_file_reference',
                'foreign_field' => "uid_foreign",
                'foreign_sortby' => "sorting_foreign",
                'foreign_table_field' => "tablenames",
                'foreign_match_fields' => array(
                    'fieldname' => "image",
                ),
                'foreign_label' => "uid_local",
                'foreign_selector' => "uid_local",
                'foreign_selector_fieldTcaOverride' => array(
                    'config' => array(
                        'appearance' => array(
                            'elementBrowserType' => 'file',
                            'elementBrowserAllowed' => 'gif,jpg,jpeg,tif,tiff,bmp,pcx,tga,png,pdf,ai',
                        ),
                    ),
                ),
                'filter' => array(
                    'userFunc' => 'TYPO3\\CMS\\Core\\Resource\\Filter\\FileExtensionFilter->filterInlineChildren',
                    'parameters' => array(
                        'allowedFileExtensions' => 'gif,jpg,jpeg,tif,tiff,bmp,pcx,tga,png,pdf,ai',
                        'disallowedFileExtensions' => '',
                    ),
                ),
                'appearance' => array(
                    'useSortable' => true,
                    'headerThumbnail' => array(
                        'field' => "uid_local",
                        'width' => "45",
                        'height' => "45c",
                    ),
                ),
                'showPossibleLocalizationRecords' => false,
                'showRemovedLocalizationRecords' => false,
                'showSynchronizationLink' => false,
                'showAllLocalizationLink' => false,
                'enabledControls' => array(
                    'info' => true,
                    'new' => false,
                    'dragdrop' => true,
                    'sort' => false,
                    'hide' => true,
                    'delete' => true,
                    'localize' => true,
                ),
                'createNewRelationLinkTitle' => "LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference",
                'behaviour' => array(
                    'localizationMode' => "select",
                    'localizeChildrenAtParentLocalization' => true,
                ),
                'foreign_types' => array(
                    0 => array(
                        'showitem' => "\n\t\t\t\t\t\t\t--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,\n\t\t\t\t\t\t\t--palette--;;filePalette",
                    ),
                    1 => array(
                        'showitem' => "\n\t\t\t\t\t\t\t--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,\n\t\t\t\t\t\t\t--palette--;;filePalette",
                    ),
                    2 => array(
                        'showitem' => "\n\t\t\t\t\t\t\t--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,\n\t\t\t\t\t\t\t--palette--;;filePalette",
                    ),
                    3 => array(
                        'showitem' => "\n\t\t\t\t\t\t\t--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,\n\t\t\t\t\t\t\t--palette--;;filePalette",
                    ),
                    4 => array(
                        'showitem' => "\n\t\t\t\t\t\t\t--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,\n\t\t\t\t\t\t\t--palette--;;filePalette",
                    ),
                    5 => array(
                        'showitem' => "\n\t\t\t\t\t\t\t--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,\n\t\t\t\t\t\t\t--palette--;;filePalette",
                    ),
                ),
            ),
        ),
        'inline_2' => array( /** Taken from irre_tutorial 1nff */
            'exclude' => 1,
            'label' => 'IRRE: 2 1:n foreign field to table with sheets with a custom text expandSingle',
            'config' => array(
                'type' => 'inline',
                'foreign_table' => 'tx_styleguide_forms_inline_2_child1',
                'foreign_field' => 'parentid',
                'foreign_table_field' => 'parenttable',
                'maxitems' => 10,
                'appearance' => array(
                    'expandSingle' => true,
                    'showSynchronizationLink' => true,
                    'showAllLocalizationLink' => true,
                    'showPossibleLocalizationRecords' => true,
                    'showRemovedLocalizationRecords' => true,
                    'newRecordLinkTitle' => 'Create a new relation "inline_2"',
                ),
                'behaviour' => array(
                    'localizationMode' => 'select',
                    'localizeChildrenAtParentLocalization' => true,
                ),
            ),
        ),
        'inline_3' => array(
            'exclude' => 1,
            'label' => 'IRRE: 3 m:m async, useCombination, newRecordLinkAddTitle',
            'config' => array(
                'type' => 'inline',
                'foreign_table' => 'tx_styleguide_forms_inline_3_mm',
                'foreign_field' => 'select_parent',
                'foreign_selector' => 'select_child',
                'foreign_unique' => 'select_child',
                'maxitems' => 9999,
                'appearance' => array(
                    'newRecordLinkAddTitle' => 1,
                    'useCombination' => true,
                    'collapseAll' => false,
                    'levelLinksPosition' => 'top',
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1,
                ),
            ),
        ),
        'inline_4' => array(
            'label' => 'IRRE: 4 media FAL field',
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig('inline_4', array(
                'appearance' => array(
                    'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference'
                ),
                // custom configuration for displaying fields in the overlay/reference table
                // to use the imageoverlayPalette instead of the basicoverlayPalette
                'foreign_types' => array(
                    '0' => array(
                        'showitem' => '
							--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
							--palette--;;filePalette'
                    ),
                    \TYPO3\CMS\Core\Resource\File::FILETYPE_TEXT => array(
                        'showitem' => '
							--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
							--palette--;;filePalette'
                    ),
                    \TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE => array(
                        'showitem' => '
							--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
							--palette--;;filePalette'
                    ),
                    \TYPO3\CMS\Core\Resource\File::FILETYPE_AUDIO => array(
                        'showitem' => '
							--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.audioOverlayPalette;audioOverlayPalette,
							--palette--;;filePalette'
                    ),
                    \TYPO3\CMS\Core\Resource\File::FILETYPE_VIDEO => array(
                        'showitem' => '
							--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.videoOverlayPalette;videoOverlayPalette,
							--palette--;;filePalette'
                    ),
                    \TYPO3\CMS\Core\Resource\File::FILETYPE_APPLICATION => array(
                        'showitem' => '
							--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
							--palette--;;filePalette'
                    )
                )
            ), $GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext'])
        ),
        'inline_5' => array(
            'exclude' => 1,
            'label' => 'IRRE: 5 tt_content child with foreign_record_defaults',
            'config' => array(
                'type' => 'inline',
                'allowed' => 'tt_content',
                'foreign_table' => 'tt_content',
                'foreign_record_defaults' => array(
                    'CType' => 'text'
                ),
                'minitems' => 0,
                'maxitems' => 1,
                'appearance' => array(
                    'collapseAll' => 0,
                    'expandSingle' => 1,
                    'levelLinksPosition' => 'bottom',
                    'useSortable' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showRemovedLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1,
                    'showSynchronizationLink' => 1,
                    'enabledControls' => array(
                        'info' => false,
                        'new' => false,
                        'dragdrop' => true,
                        'sort' => false,
                        'hide' => true,
                        'delete' => true,
                        'localize' => true,
                    ),
                ),
            ),
        ),




    ),


    'interface' => array(
        'showRecordFieldList' => 'hidden,starttime,endtime,
			type_field,
			flex_1, flex_2, flex_3,
			inline_1, inline_2, inline_3, inline_4, inline_5,
			wizard_1, wizard_2, wizard_3, wizard_4, wizard_5, wizard_6, wizard_7, wizard_8,
			',
    ),

    'types' => array(
        '0' => array(
            'showitem' => '
				--div--;Flex,
                    flex_3, flex_4, flex_5,
				--div--;Inline,
					inline_1, inline_2, inline_3, inline_4, inline_5,
			',
        ),
    ),

    'palettes' => array(
        'visibility' => array(
            'showitem' => 'hidden;Shown in frontend',
            'canNotCollapse' => 1,
        ),
    ),

);

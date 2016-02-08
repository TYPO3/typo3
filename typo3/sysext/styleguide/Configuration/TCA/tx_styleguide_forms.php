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

        'type' => 'type_field',
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


        'type_field' => array(
            'exclude' => 1,
            'label' => 'TYPE FIELD',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => array(
                    array('type standard', '0'),
                    array('type test', 'test'),
                ),
            ),
        ),


        'flex_1' => array(
            'exclude' => 1,
            'label' => 'FLEX: 1 simple flex form',
            'config' => array(
                'type' => 'flex',
                'ds' => array(
                    'default' => '
						<T3DataStructure>
							<ROOT>
								<type>array</type>
								<el>
									<input_1>
										<TCEforms>
											<label>Some input field</label>
											<config>
												<type>input</type>
												<size>23</size>
												<default>a default value</default>
											</config>
										</TCEforms>
									</input_1>
								</el>
							</ROOT>
						</T3DataStructure>
					',
                ),
            ),
        ),
        'flex_2' => array(
            'exclude' => 1,
            'label' => 'FLEX: 2 simple flex form with langDisable=1',
            'config' => array(
                'type' => 'flex',
                'ds' => array(
                    'default' => '
						<T3DataStructure>
							<meta>
								<langDisable>1</langDisable>
							</meta>
							<ROOT>
								<type>array</type>
								<el>
									<input_1>
										<TCEforms>
											<label>Some input field</label>
											<config>
												<type>input</type>
												<size>23</size>
											</config>
										</TCEforms>
									</input_1>
								</el>
							</ROOT>
						</T3DataStructure>
					',
                ),
            ),
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
        'flex_4' => array(
            'exclude' => 1,
            'label' => 'FLEX: 4 multiple items',
            'config' => array(
                'type' => 'flex',
                'ds' => array(
                    'default' => '
						<T3DataStructure>
							<meta>
								<langDisable>1</langDisable>
							</meta>
							<ROOT>
								<type>array</type>
								<el>
									<input_1>
										<TCEforms>
											<label>Some input field</label>
											<config>
												<type>input</type>
												<size>23</size>
											</config>
										</TCEforms>
									</input_1>
									<input_2>
										<TCEforms>
											<label>Some input field</label>
											<config>
												<type>input</type>
												<size>23</size>
											</config>
										</TCEforms>
									</input_2>
								</el>
							</ROOT>
						</T3DataStructure>
					',
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
        'palette_1_1' => array(
            'exclude' => 0,
            'label' => 'checkbox is type check',
            'config' => array(
                'type' => 'check',
                'default' => 1,
            ),
        ),
        'palette_1_2' => array(
            'exclude' => 0,
            'label' => 'checkbox type is user',
            'config' => array(
                'default' => true,
                'type' => 'user',
                'userFunc' => 'TYPO3\\CMS\\Styleguide\\UserFunctions\\FormEngine\\TypeUserPalette->render',
            ),
        ),
        'palette_1_3' => array(
            'exclude' => 0,
            'label' => 'checkbox is type check',
            'config' => array(
                'type' => 'check',
                'default' => 1,
            ),
        ),
        'palette_2_1' => array(
            'label' => 'Palette Field',
            'config' => array(
                'type' => 'input',
            ),
        ),
        'palette_3_1' => array(
            'label' => 'Palette Field',
            'config' => array(
                'type' => 'input',
            ),
        ),
        'palette_3_2' => array(
            'label' => 'Palette Field',
            'config' => array(
                'type' => 'input',
            ),
        ),
        'palette_4_1' => array(
            'label' => 'Palette Field',
            'config' => array(
                'type' => 'input',
            ),
        ),
        'palette_4_2' => array(
            'label' => 'Palette Field',
            'config' => array(
                'type' => 'input',
            ),
        ),
        'palette_4_3' => array(
            'label' => 'Palette Field',
            'config' => array(
                'type' => 'input',
            ),
        ),
        'palette_4_4' => array(
            'label' => 'Palette Field',
            'config' => array(
                'type' => 'input',
            ),
        ),
        'palette_5_1' => array(
            'label' => 'Palette Field',
            'config' => array(
                'type' => 'input',
            ),
        ),
        'palette_5_2' => array(
            'label' => 'Palette Field',
            'config' => array(
                'type' => 'input',
            ),
        ),
        'palette_6_1' => array(
            'label' => 'PALETTE: Simple field with palette below',
            'exclude' => 1,
            'config' => array(
                'type' => 'input',
            ),
        ),
        'palette_6_2' => array(
            'label' => 'Palette Field',
            'config' => array(
                'type' => 'input',
            ),
        ),
        'palette_6_3' => array(
            'label' => 'Palette Field',
            'config' => array(
                'type' => 'input',
            ),
        ),


        'wizard_1' => array(
            'label' => 'WIZARD: 1 vertical, edit, add, list',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_styleguide_forms_staticdata',
                'rootLevel' => 1,
                'size' => 5,
                'autoSizeMax' => 20,
                'minitems' => 0,
                'maxitems' => 999,
                'wizards' => array(
                    '_PADDING' => 1, // @TODO: Has no sane effect
                    '_VERTICAL' => 1,
                    'edit' => array(
                        'type' => 'popup',
                        'title' => 'edit',
                        'module' => array( // @TODO: TCA documentation is not up to date at least in "Adding wizards" section of type=select here
                            'name' => 'wizard_edit',
                        ),
                        'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_edit.gif',
                        'popup_onlyOpenIfSelected' => 1,
                        'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
                    ),
                    'add' => array(
                        'type' => 'script',
                        'title' => 'add',
                        'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_add.gif',
                        'module' => array(
                            'name' => 'wizard_add',
                        ),
                        'params' => array(
                            'table' => 'tx_styleguide_forms_staticdata',
                            'pid' => '0',
                            'setValue' => 'prepend',
                        ),
                    ),
                    'list' => array(
                        'type' => 'script',
                        'title' => 'list',
                        'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_list.gif',
                        'module' => array(
                            'name' => 'wizard_list',
                        ),
                        'params' => array(
                            'table' => 'tx_styleguide_forms_staticdata',
                            'pid' => '0',
                        ),
                    ),
                ),
            ),
        ),
        'wizard_2' => array(
            'exclude' => 1,
            'label' => 'WIZARD: 2 colorbox',
            'config' => array(
                'type' => 'input',
                'wizards' => array(
                    '_PADDING' => 6,
                    'colorpicker' => array(
                        'type' => 'colorbox',
                        'title' => 'Color picker',
                        'module' => array(
                            'name' => 'wizard_colorpicker',
                        ),
                        'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1',
                    ),
                ),
            ),
        ),
        'wizard_3' => array(
            'label' => 'WIZARD: 3 colorbox, with image',
            'config' => array(
                'type' => 'input',
                'wizards' => array(
                    'colorpicker' => array(
                        'type' => 'colorbox',
                        'title' => 'Color picker',
                        'module' => array(
                            'name' => 'wizard_colorpicker',
                        ),
                        'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1',
                        'exampleImg' => 'EXT:styleguide/Resources/Public/Images/colorpicker.jpg',
                    ),
                ),
            ),
        ),
        'wizard_4' => array(
            'label' => 'WIZARD: 4 suggest wizard, position top',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_styleguide_forms_staticdata',
                'disable_controls' => 'browser',
                'maxitems' => 999,
                'wizards' => array(
                    '_POSITION' => 'top',
                    'suggest' => array(
                        'type' => 'suggest',
                    ),
                ),
            ),
        ),
        'wizard_5' => array(
            'label' => 'WIZARD: 5 suggest wizard, position bottom',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_styleguide_forms_staticdata',
                'disable_controls' => 'browser',
                'maxitems' => 999,
                'wizards' => array(
                    '_POSITION' => 'bottom',
                    'suggest' => array(
                        'type' => 'suggest',
                    ),
                ),
            ),
        ),
        'wizard_6' => array(
            'exclude' => 1,
            'label' => 'WIZARD 6: Flex forms',
            'config' => array(
                'type' => 'flex',
                'ds' => array(
                    'default' => '
						<T3DataStructure>
							<meta>
								<langDisable>1</langDisable>
							</meta>
							<ROOT>
								<type>array</type>
								<el>
									<link_1>
										<TCEforms>
											<label>LINK 1</label>
											<config>
												<type>input</type>
												<eval>trim</eval>
												<softref>typolink</softref>
												<wizards type="array">
													<link type="array">
														<type>popup</type>
														<title>Link</title>
														<icon>EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_link.gif</icon>
														<module type="array">
															<name>wizard_link</name>
															<urlParameters type="array">
																<act>file|url</act>
															</urlParameters>
														</module>
														<params type="array">
															<blindLinkOptions>mail,folder,spec</blindLinkOptions>
														</params>
														<JSopenParams>height=300,width=500,status=0,menubar=0,scrollbars=1</JSopenParams>
													</link>
												</wizards>
											</config>
										</TCEforms>
									</link_1>
									<table_1>
										<TCEforms>
											<label>TABLE 1</label>
												<config>
													<type>text</type>
													<cols>30</cols>
													<rows>5</rows>
													<wizards>
														<table type="array">
															<type>script</type>
															<title>Table wizard</title>
															<icon>EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_table.gif</icon>
															<module type="array">
																<name>wizard_table</name>
															</module>
															<params type="array">
																<xmlOutput>0</xmlOutput>
															</params>
															<notNewRecords>1</notNewRecords>
														</table>
													</wizards>
												</config>
										</TCEforms>
									</table_1>
								</el>
							</ROOT>
						</T3DataStructure>
					',
                ),
            ),
        ),
        'wizard_7' => array(
            'label' => 'WIZARD: 7 table',
            'config' => array(
                'type' => 'text',
                'cols' => '40',
                'rows' => '5',
                'wizards' => array(
                    'table' => array(
                        'type' => 'script',
                        'title' => 'Table wizard',
                        'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_table.gif',
                        'module' => array(
                            'name' => 'wizard_table'
                        ),
                        'params' => array(
                            'xmlOutput' => 0
                        ),
                        'notNewRecords' => 1,
                    ),
                ),
            ),
        ),
        'wizard_8' => array(
            'label' => 'WIZARD: 8 textarea with select',
            'config' => array(
                'type' => 'text',
                'cols' => '40',
                'rows' => '5',
                'wizards' => array(
                    'select' => array(
                        'type' => 'select',
                        'items' => array(
                            array('Option 1', 'Dummy Text for Option 1'),
                            array('Option 2', 'Dummy Text for Option 2'),
                            array('Option 3', 'Dummy Text for Option 3'),
                        ),
                    ),
                ),
            ),
        ),


        'rte_1' => array(
            'exclude' => 1,
            'label' => 'RTE 1',
            'config' => array(
                'type' => 'text',
            ),
            'defaultExtras' => 'richtext[*]:rte_transform[mode=ts_css]',
        ),
        'rte_2' => array(
            'exclude' => 1,
            'label' => 'RTE 2',
            'config' => array(
                'type' => 'text',
                'cols' => 30,
                'rows' => 6,
            ),
            'defaultExtras' => 'richtext[]:rte_transform[mode=ts_css]',
        ),
        'rte_3' => array(
            'exclude' => 1,
            'label' => 'RTE 3: In inline child',
            'config' => array(
                'type' => 'inline',
                'foreign_table' => 'tx_styleguide_forms_rte_3_child1',
                'foreign_field' => 'parentid',
                'foreign_table_field' => 'parenttable',
            ),
        ),
        'rte_4' => array(
            'exclude' => 1,
            'label' => 'RTE 4: type flex, rte in a tab, rte in section container, rte in inline',
            'config' => array(
                'type' => 'flex',
                'ds' => array(
                    'default' => '
						<T3DataStructure>
							<sheets>
								<sGeneral>
									<ROOT>
										<TCEforms>
											<sheetTitle>RTE in tab</sheetTitle>
										</TCEforms>
										<type>array</type>
										<el>
											<rte_1>
												<TCEforms>
													<label>RTE 1</label>
													<config>
														<type>text</type>
													</config>
													<defaultExtras>richtext[]:rte_transform[mode=ts_css]</defaultExtras>
												</TCEforms>
											</rte_1>
										</el>
									</ROOT>
								</sGeneral>
								<sSections>
									<ROOT>
										<TCEforms>
											<sheetTitle>RTE in section</sheetTitle>
										</TCEforms>
										<type>array</type>
										<el>
											<section_1>
												<title>section_1</title>
												<type>array</type>
												<section>1</section>
												<el>
													<container_1>
														<type>array</type>
														<title>1 RTE field</title>
														<el>
															<rte_2>
																<TCEforms>
																	<label>RTE 2</label>
																	<config>
																		<type>text</type>
																	</config>
																	<defaultExtras>richtext[]:rte_transform[mode=ts_css]</defaultExtras>
																</TCEforms>
															</rte_2>
														</el>
													</container_1>
												</el>
											</section_1>
										</el>
									</ROOT>
								</sSections>
								<sInline>
									<ROOT>
										<TCEforms>
											<sheetTitle>RTE in inline</sheetTitle>
										</TCEforms>
										<type>array</type>
										<el>
											<inline_1>
												<TCEforms>
													<label>inline_1 to one field</label>
													<config>
														<type>inline</type>
														<foreign_table>tx_styleguide_forms_rte_4_flex_inline_1_child1</foreign_table>
														<foreign_field>parentid</foreign_field>
														<foreign_table_field>parenttable</foreign_table_field>
													</config>
												</TCEforms>
											</inline_1>
										</el>
									</ROOT>
								</sInline>
							</sheets>
						</T3DataStructure>
					',
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
			rte_1, rte_2, rte_3, rte_4,
			',
    ),

    'types' => array(
        '0' => array(
            'showitem' => '
				--div--;Type,
					type_field,
				--div--;Flex,
					flex_1, flex_2, flex_3, flex_4, flex_5,
				--div--;Inline,
					inline_1, inline_2, inline_3, inline_4, inline_5,
				--div--;Palettes,
					--palette--;Palettes 1;palettes_1,
					--palette--;Palettes 2;palettes_2,
					--palette--;Palettes 3;palettes_3,
					--palette--;;palettes_4,
					--palette--;Palettes 5;palettes_5,
					palette_6_1;Field with palette below, --palette--;;palettes_6,
				--div--;Wizards,
					wizard_1, wizard_2, wizard_3, wizard_7, wizard_4, wizard_5, wizard_6, wizard_8,
				--div--;RTE,
					rte_1, --palette--;RTE in palette;rte_2_palette, rte_3, rte_4,
			',
            'columnsOverrides' => array(
                't3editor_2' => array(
                    'config' => array(
                        'renderType' => 't3editor',
                        'format' => 'html',
                    ),
                ),
            ),
        ),
        'test' => array(
            'showitem' => '
				--div--;Type,
					type_field,
				--div--;t3editor,
					t3editor_2;T3EDITOR: 2 Should be usual text field,
			',
        ),
    ),

    'palettes' => array(
        'palettes_1' => array(
            'showitem' => 'palette_1_1, palette_1_2, palette_1_3',
            'canNotCollapse' => 1,
        ),
        'palettes_2' => array(
            'showitem' => 'palette_2_1',
        ),
        'palettes_3' => array(
            'showitem' => 'palette_3_1, palette_3_2',
        ),
        'palettes_4' => array(
            'showitem' => 'palette_4_1, palette_4_2, palette_4_3, --linebreak--, palette_4_4',
        ),
        'palettes_5' => array(
            'showitem' => 'palette_5_1, --linebreak--, palette_5_2',
            'canNotCollapse' => 1,
        ),
        'palettes_6' => array(
            'showitem' => 'palette_6_2, palette_6_3',
        ),
        'rte_2_palette' => array(
            'showitem' => 'rte_2',
        ),
        'visibility' => array(
            'showitem' => 'hidden;Shown in frontend',
            'canNotCollapse' => 1,
        ),
    ),

);

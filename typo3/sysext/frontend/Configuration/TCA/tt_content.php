<?php
return array(
	'ctrl' => array(
		'label' => 'header',
		'label_alt' => 'subheader,bodytext',
		'sortby' => 'sorting',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'title' => 'LLL:EXT:cms/locallang_tca.xlf:tt_content',
		'delete' => 'deleted',
		'versioningWS' => 2,
		'versioning_followPages' => TRUE,
		'origUid' => 't3_origuid',
		'type' => 'CType',
		'hideAtCopy' => TRUE,
		'prependAtCopy' => 'LLL:EXT:lang/locallang_general.xlf:LGL.prependAtCopy',
		'copyAfterDuplFields' => 'colPos,sys_language_uid',
		'useColumnsForDefaultValues' => 'colPos,sys_language_uid',
		'shadowColumnsForNewPlaceholders' => 'colPos',
		'transOrigPointerField' => 'l18n_parent',
		'transOrigDiffSourceField' => 'l18n_diffsource',
		'languageField' => 'sys_language_uid',
		'enablecolumns' => array(
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
			'fe_group' => 'fe_group'
		),
		'typeicon_column' => 'CType',
		'typeicon_classes' => array(
			'header' => 'mimetypes-x-content-header',
			'textpic' => 'mimetypes-x-content-text-picture',
			'image' => 'mimetypes-x-content-image',
			'bullets' => 'mimetypes-x-content-list-bullets',
			'table' => 'mimetypes-x-content-table',
			'uploads' => 'mimetypes-x-content-list-files',
			'multimedia' => 'mimetypes-x-content-multimedia',
			'media' => 'mimetypes-x-content-multimedia',
			'menu' => 'mimetypes-x-content-menu',
			'list' => 'mimetypes-x-content-plugin',
			'mailform' => 'mimetypes-x-content-form',
			'search' => 'mimetypes-x-content-form-search',
			'login' => 'mimetypes-x-content-login',
			'shortcut' => 'mimetypes-x-content-link',
			'script' => 'mimetypes-x-content-script',
			'div' => 'mimetypes-x-content-divider',
			'html' => 'mimetypes-x-content-html',
			'text' => 'mimetypes-x-content-text',
			'default' => 'mimetypes-x-content-text'
		),
		'typeicons' => array(
			'header' => 'tt_content_header.gif',
			'textpic' => 'tt_content_textpic.gif',
			'image' => 'tt_content_image.gif',
			'bullets' => 'tt_content_bullets.gif',
			'table' => 'tt_content_table.gif',
			'uploads' => 'tt_content_uploads.gif',
			'multimedia' => 'tt_content_mm.gif',
			'media' => 'tt_content_mm.gif',
			'menu' => 'tt_content_menu.gif',
			'list' => 'tt_content_list.gif',
			'mailform' => 'tt_content_form.gif',
			'search' => 'tt_content_search.gif',
			'login' => 'tt_content_login.gif',
			'shortcut' => 'tt_content_shortcut.gif',
			'script' => 'tt_content_script.gif',
			'div' => 'tt_content_div.gif',
			'html' => 'tt_content_html.gif'
		),
		'thumbnail' => 'image',
		'requestUpdate' => 'list_type,rte_enabled,menu_type',
		'dividers2tabs' => 1,
		'searchFields' => 'header,header_link,subheader,bodytext,pi_flexform'
	),
	'interface' => array(
		'always_description' => 0,
		'showRecordFieldList' => 'CType,header,header_link,bodytext,image,imagewidth,imageorient,media,records,colPos,starttime,endtime,fe_group'
	),
	'columns' => array(
		'CType' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.type',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:CType.div.standard',
						'--div--'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:CType.I.0',
						'header',
						'i/tt_content_header.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:CType.I.1',
						'text',
						'i/tt_content.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:CType.I.2',
						'textpic',
						'i/tt_content_textpic.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:CType.I.3',
						'image',
						'i/tt_content_image.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:CType.div.lists',
						'--div--'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:CType.I.4',
						'bullets',
						'i/tt_content_bullets.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:CType.I.5',
						'table',
						'i/tt_content_table.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:CType.I.6',
						'uploads',
						'i/tt_content_uploads.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:CType.div.forms',
						'--div--'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:CType.I.8',
						'mailform',
						'i/tt_content_form.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:CType.I.9',
						'search',
						'i/tt_content_search.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:CType.div.special',
						'--div--'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:CType.I.7',
						'multimedia',
						'i/tt_content_mm.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:CType.I.18',
						'media',
						'i/tt_content_mm.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:CType.I.12',
						'menu',
						'i/tt_content_menu.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:CType.I.13',
						'shortcut',
						'i/tt_content_shortcut.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:CType.I.14',
						'list',
						'i/tt_content_list.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:CType.I.16',
						'div',
						'i/tt_content_div.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:CType.I.17',
						'html',
						'i/tt_content_html.gif'
					)
				),
				'default' => 'text',
				'authMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'],
				'authMode_enforce' => 'strict',
				'iconsInOptionTags' => 1,
				'noIconsBelowSelect' => 1
			)
		),
		'hidden' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.hidden',
			'config' => array(
				'type' => 'check',
				'items' => array(
					'1' => array(
						'0' => 'LLL:EXT:cms/locallang_ttc.xlf:hidden.I.0'
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
			),
			'l10n_mode' => 'exclude',
			'l10n_display' => 'defaultAsReadonly'
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
			),
			'l10n_mode' => 'exclude',
			'l10n_display' => 'defaultAsReadonly'
		),
		'fe_group' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.fe_group',
			'config' => array(
				'type' => 'select',
				'size' => 5,
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
		'sys_language_uid' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.language',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array(
						'LLL:EXT:lang/locallang_general.xlf:LGL.allLanguages',
						-1
					),
					array(
						'LLL:EXT:lang/locallang_general.xlf:LGL.default_value',
						0
					)
				)
			)
		),
		'l18n_parent' => array(
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.l18n_parent',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'',
						0
					)
				),
				'foreign_table' => 'tt_content',
				'foreign_table_where' => 'AND tt_content.pid=###CURRENT_PID### AND tt_content.sys_language_uid IN (-1,0)'
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
						'LLL:EXT:cms/locallang_ttc.xlf:layout.I.1',
						'1'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:layout.I.2',
						'2'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:layout.I.3',
						'3'
					)
				),
				'default' => '0'
			)
		),
		'colPos' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:colPos',
			'config' => array(
				'type' => 'select',
				'itemsProcFunc' => 'TYPO3\\CMS\\Backend\\View\\BackendLayoutView->colPosListItemProcFunc',
				'items' => array(
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:colPos.I.0',
						'1'
					),
					array(
						'LLL:EXT:lang/locallang_general.xlf:LGL.normal',
						'0'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:colPos.I.2',
						'2'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:colPos.I.3',
						'3'
					)
				),
				'default' => '0'
			)
		),
		'date' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:date',
			'config' => array(
				'type' => 'input',
				'size' => '13',
				'max' => '20',
				'eval' => 'date',
				'default' => '0'
			)
		),
		'header' => array(
			'l10n_mode' => 'prefixLangTitle',
			'l10n_cat' => 'text',
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:header',
			'config' => array(
				'type' => 'input',
				'size' => '50',
				'max' => '256'
			)
		),
		'header_position' => array(
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:header_position',
			'exclude' => 1,
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'LLL:EXT:lang/locallang_general.xlf:LGL.default_value',
						''
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:header_position.I.1',
						'center'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:header_position.I.2',
						'right'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:header_position.I.3',
						'left'
					)
				),
				'default' => ''
			)
		),
		'header_link' => array(
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:header_link',
			'exclude' => 1,
			'config' => array(
				'type' => 'input',
				'size' => '50',
				'max' => '256',
				'eval' => 'trim',
				'wizards' => array(
					'_PADDING' => 2,
					'link' => array(
						'type' => 'popup',
						'title' => 'LLL:EXT:cms/locallang_ttc.xlf:header_link_formlabel',
						'icon' => 'link_popup.gif',
						'module' => array(
							'name' => 'wizard_element_browser',
							'urlParameters' => array(
								'mode' => 'wizard'
							)
						),
						'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1'
					)
				),
				'softref' => 'typolink'
			)
		),
		'header_layout' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.type',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'LLL:EXT:lang/locallang_general.xlf:LGL.default_value',
						'0'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:header_layout.I.1',
						'1'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:header_layout.I.2',
						'2'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:header_layout.I.3',
						'3'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:header_layout.I.4',
						'4'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:header_layout.I.5',
						'5'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:header_layout.I.6',
						'100'
					)
				),
				'default' => '0'
			)
		),
		'subheader' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.subheader',
			'config' => array(
				'type' => 'input',
				'size' => '50',
				'max' => '256',
				'softref' => 'email[subst]'
			)
		),
		'bodytext' => array(
			'l10n_mode' => 'prefixLangTitle',
			'l10n_cat' => 'text',
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.text',
			'config' => array(
				'type' => 'text',
				'cols' => '80',
				'rows' => '15',
				'wizards' => array(
					'_PADDING' => 4,
					'_VALIGN' => 'middle',
					'RTE' => array(
						'notNewRecords' => 1,
						'RTEonly' => 1,
						'type' => 'script',
						'title' => 'LLL:EXT:cms/locallang_ttc.xlf:bodytext.W.RTE',
						'icon' => 'wizard_rte2.gif',
						'module' => array(
							'name' => 'wizard_rte'
						)
					),
					'table' => array(
						'notNewRecords' => 1,
						'enableByTypeConfig' => 1,
						'type' => 'script',
						'title' => 'LLL:EXT:cms/locallang_ttc.xlf:bodytext.W.table',
						'icon' => 'wizard_table.gif',
						'module' => array(
							'name' => 'wizard_table'
						),
						'params' => array(
							'xmlOutput' => 0
						)
					),
					'forms' => array(
						'notNewRecords' => 1,
						'enableByTypeConfig' => 1,
						'type' => 'script',
						'title' => 'LLL:EXT:cms/locallang_ttc.xlf:bodytext.W.forms',
						'icon' => 'wizard_forms.gif',
						'module' => array(
							'name' => 'wizard_forms',
							'urlParameters' => array(
								'special' => 'formtype_mail'
							)
						),
						'params' => array(
							'xmlOutput' => 0
						)
					)
				),
				'softref' => 'typolink_tag,images,email[subst],url',
				'search' => array(
					'andWhere' => 'CType=\'text\' OR CType=\'textpic\''
				)
			)
		),
		'image' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.images',
			'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig('image', array(
				'appearance' => array(
					'createNewRelationLinkTitle' => 'LLL:EXT:cms/locallang_ttc.xlf:images.addFileReference'
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
							--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
							--palette--;;filePalette'
					),
					\TYPO3\CMS\Core\Resource\File::FILETYPE_VIDEO => array(
						'showitem' => '
							--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
							--palette--;;filePalette'
					),
					\TYPO3\CMS\Core\Resource\File::FILETYPE_APPLICATION => array(
						'showitem' => '
							--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
							--palette--;;filePalette'
					)
				)
			), $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'])
		),
		'imagewidth' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:imagewidth',
			'config' => array(
				'type' => 'input',
				'size' => '4',
				'max' => '4',
				'eval' => 'int',
				'range' => array(
					'upper' => '999',
					'lower' => '25'
				),
				'default' => 0
			)
		),
		'imageheight' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:imageheight',
			'config' => array(
				'type' => 'input',
				'size' => '4',
				'max' => '4',
				'eval' => 'int',
				'range' => array(
					'upper' => '700',
					'lower' => '25'
				),
				'default' => 0
			)
		),
		'imageorient' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:imageorient',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:imageorient.I.0',
						0,
						'selicons/above_center.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:imageorient.I.1',
						1,
						'selicons/above_right.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:imageorient.I.2',
						2,
						'selicons/above_left.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:imageorient.I.3',
						8,
						'selicons/below_center.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:imageorient.I.4',
						9,
						'selicons/below_right.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:imageorient.I.5',
						10,
						'selicons/below_left.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:imageorient.I.6',
						17,
						'selicons/intext_right.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:imageorient.I.7',
						18,
						'selicons/intext_left.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:imageorient.I.8',
						'--div--'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:imageorient.I.9',
						25,
						'selicons/intext_right_nowrap.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:imageorient.I.10',
						26,
						'selicons/intext_left_nowrap.gif'
					)
				),
				'selicon_cols' => 6,
				'default' => '0',
				'iconsInOptionTags' => 1
			)
		),
		'imageborder' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:imageborder',
			'config' => array(
				'type' => 'check',
				'items' => array(
					'1' => array(
						'0' => 'LLL:EXT:lang/locallang_core.xlf:labels.enabled'
					)
				)
			)
		),
		'image_noRows' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:image_noRows',
			'config' => array(
				'type' => 'check',
				'items' => array(
					'1' => array(
						'0' => 'LLL:EXT:cms/locallang_ttc.xlf:image_noRows.I.0'
					)
				)
			)
		),
		'image_link' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:image_link',
			'config' => array(
				'type' => 'text',
				'cols' => '30',
				'rows' => '3',
				'wizards' => array(
					'_PADDING' => 2,
					'link' => array(
						'type' => 'popup',
						'title' => 'LLL:EXT:cms/locallang_ttc.xlf:image_link_formlabel',
						'icon' => 'link_popup.gif',
						'module' => array(
							'name' => 'wizard_element_browser',
							'urlParameters' => array(
								'mode' => 'wizard'
							)
						),
						'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1'
					)
				),
				'softref' => 'typolink[linkList]'
			)
		),
		'image_zoom' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:image_zoom',
			'config' => array(
				'type' => 'check',
				'items' => array(
					'1' => array(
						'0' => 'LLL:EXT:lang/locallang_core.xlf:labels.enabled'
					)
				)
			)
		),
		'image_effects' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:image_effects',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:image_effects.I.0',
						0
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:image_effects.I.1',
						1
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:image_effects.I.2',
						2
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:image_effects.I.3',
						3
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:image_effects.I.4',
						10
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:image_effects.I.5',
						11
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:image_effects.I.6',
						20
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:image_effects.I.7',
						23
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:image_effects.I.8',
						25
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:image_effects.I.9',
						26
					)
				)
			)
		),
		'image_frames' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:image_frames',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:image_frames.I.0',
						0
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:image_frames.I.1',
						1
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:image_frames.I.2',
						2
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:image_frames.I.3',
						3
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:image_frames.I.4',
						4
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:image_frames.I.5',
						5
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:image_frames.I.6',
						6
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:image_frames.I.7',
						7
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:image_frames.I.8',
						8
					)
				)
			)
		),
		'image_compression' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:image_compression',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'LLL:EXT:lang/locallang_general.xlf:LGL.default_value',
						0
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:image_compression.I.1',
						1
					),
					array(
						'GIF/256',
						10
					),
					array(
						'GIF/128',
						11
					),
					array(
						'GIF/64',
						12
					),
					array(
						'GIF/32',
						13
					),
					array(
						'GIF/16',
						14
					),
					array(
						'GIF/8',
						15
					),
					array(
						'PNG',
						39
					),
					array(
						'PNG/256',
						30
					),
					array(
						'PNG/128',
						31
					),
					array(
						'PNG/64',
						32
					),
					array(
						'PNG/32',
						33
					),
					array(
						'PNG/16',
						34
					),
					array(
						'PNG/8',
						35
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:image_compression.I.15',
						21
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:image_compression.I.16',
						22
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:image_compression.I.17',
						24
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:image_compression.I.18',
						26
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:image_compression.I.19',
						28
					)
				)
			)
		),
		'imagecols' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:imagecols',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'1',
						1
					),
					array(
						'2',
						2
					),
					array(
						'3',
						3
					),
					array(
						'4',
						4
					),
					array(
						'5',
						5
					),
					array(
						'6',
						6
					),
					array(
						'7',
						7
					),
					array(
						'8',
						8
					)
				),
				'default' => 1
			)
		),
		'imagecaption' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.caption',
			'config' => array(
				'type' => 'text',
				'cols' => '30',
				'rows' => '3',
				'softref' => 'typolink_tag,images,email[subst],url'
			)
		),
		'imagecaption_position' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:imagecaption_position',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'LLL:EXT:lang/locallang_general.xlf:LGL.default_value',
						''
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:imagecaption_position.I.1',
						'center'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:imagecaption_position.I.2',
						'right'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:imagecaption_position.I.3',
						'left'
					)
				),
				'default' => ''
			)
		),
		'altText' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:image_altText',
			'config' => array(
				'type' => 'text',
				'cols' => '30',
				'rows' => '3'
			)
		),
		'titleText' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:image_titleText',
			'config' => array(
				'type' => 'text',
				'cols' => '30',
				'rows' => '3'
			)
		),
		'longdescURL' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:image_longdescURL',
			'config' => array(
				'type' => 'text',
				'cols' => '30',
				'rows' => '3',
				'wizards' => array(
					'_PADDING' => 2,
					'link' => array(
						'type' => 'popup',
						'title' => 'LLL:EXT:cms/locallang_ttc.xlf:image_link_formlabel',
						'icon' => 'link_popup.gif',
						'module' => array(
							'name' => 'wizard_element_browser',
							'urlParameters' => array(
								'mode' => 'wizard'
							)
						),
						'params' => array(
							'blindLinkOptions' => 'folder,file,mail,spec',
							'blindLinkFields' => 'target,title,class,params'
						),
						'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1'
					)
				),
				'softref' => 'typolink[linkList]'
			)
		),
		'cols' => array(
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:cols',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:cols.I.0',
						'0'
					),
					array(
						'1',
						'1'
					),
					array(
						'2',
						'2'
					),
					array(
						'3',
						'3'
					),
					array(
						'4',
						'4'
					),
					array(
						'5',
						'5'
					),
					array(
						'6',
						'6'
					),
					array(
						'7',
						'7'
					),
					array(
						'8',
						'8'
					),
					array(
						'9',
						'9'
					)
				),
				'default' => '0'
			)
		),
		'pages' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.startingpoint',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'pages',
				'size' => '3',
				'maxitems' => '22',
				'minitems' => '0',
				'show_thumbs' => '1',
				'wizards' => array(
					'suggest' => array(
						'type' => 'suggest'
					)
				)
			)
		),
		'recursive' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.recursive',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:recursive.I.0',
						'0'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:recursive.I.1',
						'1'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:recursive.I.2',
						'2'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:recursive.I.3',
						'3'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:recursive.I.4',
						'4'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:recursive.I.5',
						'250'
					)
				),
				'default' => '0'
			)
		),
		'menu_type' => array(
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:menu_type',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:menu_type.I.0',
						'0'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:menu_type.I.1',
						'1'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:menu_type.I.2',
						'4'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:menu_type.I.3',
						'7'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:menu_type.I.4',
						'2'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:menu_type.I.8',
						'8'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:menu_type.I.5',
						'3'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:menu_type.I.6',
						'5'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:menu_type.I.7',
						'6'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:menu_type.I.categorized_pages',
						'categorized_pages'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:menu_type.I.categorized_content',
						'categorized_content'
					)
				),
				'default' => '0'
			)
		),
		'list_type' => array(
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:list_type',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'',
						'',
						''
					)
				),
				'itemsProcFunc' => 'user_sortPluginList',
				'default' => '',
				'authMode' => $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'],
				'iconsInOptionTags' => 1,
				'noIconsBelowSelect' => 1
			)
		),
		'select_key' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.code',
			'config' => array(
				'type' => 'input',
				'size' => '50',
				'max' => '80',
				'eval' => 'trim'
			)
		),
		'table_bgColor' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:table_bgColor',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'LLL:EXT:lang/locallang_general.xlf:LGL.default_value',
						'0'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:table_bgColor.I.1',
						'1'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:table_bgColor.I.2',
						'2'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:table_bgColor.I.3',
						'200'
					),
					array(
						'-----',
						'--div--'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:table_bgColor.I.5',
						'240'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:table_bgColor.I.6',
						'241'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:table_bgColor.I.7',
						'242'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:table_bgColor.I.8',
						'243'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:table_bgColor.I.9',
						'244'
					)
				),
				'default' => '0'
			)
		),
		'table_border' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:table_border',
			'config' => array(
				'type' => 'input',
				'size' => '3',
				'max' => '3',
				'eval' => 'int',
				'range' => array(
					'upper' => '20',
					'lower' => '0'
				),
				'default' => 0
			)
		),
		'table_cellspacing' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:table_cellspacing',
			'config' => array(
				'type' => 'input',
				'size' => '3',
				'max' => '3',
				'eval' => 'int',
				'range' => array(
					'upper' => '200',
					'lower' => '0'
				),
				'default' => 0
			)
		),
		'table_cellpadding' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:table_cellpadding',
			'config' => array(
				'type' => 'input',
				'size' => '3',
				'max' => '3',
				'eval' => 'int',
				'range' => array(
					'upper' => '200',
					'lower' => '0'
				),
				'default' => 0
			)
		),
		'media' => array(
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:media',
			'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig('media', array(
				'appearance' => array(
					'createNewRelationLinkTitle' => 'LLL:EXT:cms/locallang_ttc.xlf:media.addFileReference'
				)
			))
		),
		'file_collections' => array(
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:file_collections',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'db',
				'localizeReferencesAtParentLocalization' => TRUE,
				'allowed' => 'sys_file_collection',
				'foreign_table' => 'sys_file_collection',
				'maxitems' => 999,
				'minitems' => 0,
				'size' => 5,
			)
		),
		'multimedia' => array(
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:multimedia',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => 'txt,html,htm,class,swf,swa,dcr,wav,avi,au,mov,asf,mpg,wmv,mp3,mp4,m4v',
				'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
				'uploadfolder' => 'uploads/media',
				'size' => '2',
				'maxitems' => '1',
				'minitems' => '0'
			)
		),
		'filelink_size' => array(
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:filelink_size',
			'config' => array(
				'type' => 'check',
				'items' => array(
					'1' => array(
						'0' => 'LLL:EXT:lang/locallang_core.xlf:labels.enabled'
					)
				)
			)
		),
		'filelink_sorting' => array(
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:filelink_sorting',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('LLL:EXT:cms/locallang_ttc.xlf:filelink_sorting.none', ''),
					array('LLL:EXT:cms/locallang_ttc.xlf:filelink_sorting.extension', 'extension'),
					array('LLL:EXT:cms/locallang_ttc.xlf:filelink_sorting.name', 'name'),
					array('LLL:EXT:cms/locallang_ttc.xlf:filelink_sorting.type', 'type'),
					array('LLL:EXT:cms/locallang_ttc.xlf:filelink_sorting.size', 'size')
				)
			)
		),
		'target' => array(
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:target',
			'config' => array(
				'type' => 'input',
				'size' => 20,
				'eval' => 'trim',
				'wizards' => array(
					'target_picker' => array(
						'type' => 'select',
						'mode' => '',
						'items' => array(
							array('LLL:EXT:cms/locallang_ttc.xlf:target.I.1', '_blank')
						)
					)
				),
				'default' => ''
			)
		),
		'records' => array(
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:records',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tt_content',
				'size' => '5',
				'maxitems' => '200',
				'minitems' => '0',
				'show_thumbs' => '1',
				'wizards' => array(
					'suggest' => array(
						'type' => 'suggest'
					)
				)
			)
		),
		'spaceBefore' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:spaceBefore',
			'config' => array(
				'type' => 'input',
				'size' => '5',
				'max' => '5',
				'eval' => 'int',
				'range' => array(
					'lower' => '0'
				),
				'default' => 0
			)
		),
		'spaceAfter' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:spaceAfter',
			'config' => array(
				'type' => 'input',
				'size' => '5',
				'max' => '5',
				'eval' => 'int',
				'range' => array(
					'lower' => '0'
				),
				'default' => 0
			)
		),
		'section_frame' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:section_frame',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'',
						'0'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:section_frame.I.1',
						'1'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:section_frame.I.2',
						'5'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:section_frame.I.3',
						'6'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:section_frame.I.4',
						'10'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:section_frame.I.5',
						'11'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:section_frame.I.6',
						'12'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:section_frame.I.7',
						'20'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xlf:section_frame.I.8',
						'21'
					)
				),
				'default' => '0'
			)
		),
		'sectionIndex' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:sectionIndex',
			'config' => array(
				'type' => 'check',
				'default' => 1,
				'items' => array(
					'1' => array(
						'0' => 'LLL:EXT:lang/locallang_core.xlf:labels.enabled'
					)
				)
			)
		),
		'linkToTop' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:linkToTop',
			'config' => array(
				'type' => 'check',
				'items' => array(
					'1' => array(
						'0' => 'LLL:EXT:lang/locallang_core.xlf:labels.enabled'
					)
				)
			)
		),
		'rte_enabled' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:rte_enabled',
			'config' => array(
				'type' => 'check',
				'showIfRTE' => 1,
				'items' => array(
					'1' => array(
						'0' => 'LLL:EXT:cms/locallang_ttc.xlf:rte_enabled.I.0'
					)
				)
			)
		),
		'pi_flexform' => array(
			'l10n_display' => 'hideDiff',
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:pi_flexform',
			'config' => array(
				'type' => 'flex',
				'ds_pointerField' => 'list_type,CType',
				'ds' => array(
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
					',media' => file_get_contents(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('cms') . 'flexform_media.xml')
				),
				'search' => array(
					'andWhere' => 'CType=\'list\''
				)
			)
		),
		'tx_impexp_origuid' => array(
			'config' => array(
				'type' => 'passthrough'
			)
		),
		'accessibility_title' => array(
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:accessibility_title',
			'config' => array(
				'type' => 'input',
				'size' => 20,
				'eval' => 'trim',
				'default' => ''
			)
		),
		'accessibility_bypass' => array(
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:accessibility_bypass',
			'config' => array(
				'type' => 'check',
				'items' => array(
					'1' => array(
						'0' => 'LLL:EXT:lang/locallang_core.xlf:labels.enabled'
					)
				)
			)
		),
		'accessibility_bypass_text' => array(
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:accessibility_bypass_text',
			'config' => array(
				'type' => 'input',
				'size' => 20,
				'eval' => 'trim',
				'default' => ''
			)
		),
		'l18n_diffsource' => array(
			'config' => array(
				'type' => 'passthrough'
			)
		),
		't3ver_label' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.versionLabel',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'max' => '255'
			)
		),
		'selected_categories' => array(
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:selected_categories',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'sys_category',
				'foreign_table_where' => 'AND sys_category.sys_language_uid IN (0,-1) ORDER BY sys_category.title ASC',
				'size' => 10,
				'autoSizeMax' => 50,
				'maxitems' => 9999,
				'renderMode' => 'tree',
				'treeConfig' => array(
					'parentField' => 'parent',
					'appearance' => array(
						'expandAll' => TRUE,
						'showHeader' => TRUE,
					),
				),
			)
		),
		'category_field' => array(
			'label' => 'LLL:EXT:cms/locallang_ttc.xlf:category_field',
			'config' => array(
				'type' => 'select',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
				'suppress_icons' => 1,
				'itemsProcFunc' => 'TYPO3\\CMS\\Core\\Category\\CategoryRegistry->getCategoryFieldsForTable',
			)
		)
	),
	'types' => array(
		'1' => array(
			'showitem' => 'CType'
		),
		'header' => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.general;general,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.headers;headers,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.frames;frames,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.access;access,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.extended'
		),
		'text' => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.general;general,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.header;header,
					bodytext;LLL:EXT:cms/locallang_ttc.xlf:bodytext_formlabel;;richtext:rte_transform[flag=rte_enabled|mode=ts_css],
					rte_enabled;LLL:EXT:cms/locallang_ttc.xlf:rte_enabled_formlabel,
					--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.appearance,
						--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.frames;frames,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.access;access,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.extended'
		),
		'textpic' => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.general;general,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.header;header,
					bodytext;Text;;richtext:rte_transform[flag=rte_enabled|mode=ts_css],
					rte_enabled;LLL:EXT:cms/locallang_ttc.xlf:rte_enabled_formlabel,' . '--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.images,
					image,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.imagelinks;imagelinks,' . '--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.frames;frames,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.image_settings;image_settings,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.imageblock;imageblock,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.access;access,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.extended'
		),
		'image' => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.general;general,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.header;header,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.images,
					image,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.imagelinks;imagelinks,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.frames;frames,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.image_settings;image_settings,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.imageblock;imageblock,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.access;access,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.extended'
		),
		'bullets' => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.general;general,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.header;header,
					bodytext;LLL:EXT:cms/locallang_ttc.xlf:bodytext.ALT.bulletlist_formlabel;;nowrap,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.frames;frames,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.access;access,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.extended'
		),
		'table' => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.general;general,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.header;header,
					bodytext;LLL:EXT:cms/locallang_ttc.xlf:bodytext.ALT.table_formlabel;;nowrap:wizards[table],
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.frames;frames,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.table_layout;tablelayout,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.access;access,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.extended'
		),
		// file list
		'uploads' => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.general;general,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.header;header,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:media;uploads,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.frames;frames,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.uploads_layout;uploadslayout,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.access;access,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.extended'
		),
		'multimedia' => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.general;general,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.header;header,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.media,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.multimediafiles;multimediafiles,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.frames;frames,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.access;access,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.extended'
		),
		'media' => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.general;general,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.header;header,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.media,
					pi_flexform; ;,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.frames;frames,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.access;access,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.behaviour,
					bodytext;LLL:EXT:cms/locallang_ttc.xlf:bodytext.ALT.media_formlabel;;richtext:rte_transform[flag=rte_enabled|mode=ts_css],
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.extended'
		),
		'menu' => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.general;general,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.header;header,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.menu;menu,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.menu_accessibility;menu_accessibility,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.frames;frames,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.access;access,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.extended',
			'subtype_value_field' => 'menu_type',
			'subtypes_excludelist' => array(
				'2' => 'pages',
				'categorized_pages' => 'pages',
				'categorized_content' => 'pages',
			),
			'subtypes_addlist' => array(
				'categorized_pages' => 'selected_categories;;menu, category_field;;menu',
				'categorized_content' => 'selected_categories;;menu, category_field;;menu',
			)
		),
		'mailform' => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.general;general,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.header;header,
					bodytext;LLL:EXT:cms/locallang_ttc.xlf:bodytext.ALT.mailform_formlabel;;nowrap:wizards[forms],
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.frames;frames,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.access;access,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.behaviour,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.mailform;mailform,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.extended'
		),
		'search' => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.general;general,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.header;header,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.frames;frames,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.access;access,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.behaviour,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.searchform;searchform,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.extended'
		),
		'shortcut' => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.general;general,
					header;LLL:EXT:cms/locallang_ttc.xlf:header.ALT.shortcut_formlabel,
					records;LLL:EXT:cms/locallang_ttc.xlf:records_formlabel,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.frames;frames,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.access;access,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.extended'
		),
		'list' => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.general;general,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.header;header,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.plugin,
					list_type;LLL:EXT:cms/locallang_ttc.xlf:list_type_formlabel,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.frames;frames,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.access;access,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.behaviour,
					select_key;LLL:EXT:cms/locallang_ttc.xlf:select_key_formlabel,
					pages;LLL:EXT:cms/locallang_ttc.xlf:pages.ALT.list_formlabel,
					recursive,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.extended',
			'subtype_value_field' => 'list_type',
			'subtypes_excludelist' => array(
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
			)
		),
		'div' => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.general;general,
					header;LLL:EXT:cms/locallang_ttc.xlf:header.ALT.div_formlabel,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.frames;frames,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.access;access,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.extended'
		),
		'html' => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.general;general,
					header;LLL:EXT:cms/locallang_ttc.xlf:header.ALT.html_formlabel,
					bodytext,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.frames;frames,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.access;access,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.extended'
		)
	),
	'palettes' => array(
		'1' => array(
			'showitem' => 'starttime, endtime'
		),
		'2' => array(
			'showitem' => 'imagecols, image_noRows, imageborder'
		),
		'3' => array(
			'showitem' => 'header_position, header_layout, header_link, date'
		),
		'4' => array(
			'showitem' => 'sys_language_uid, l18n_parent, colPos, spaceBefore, spaceAfter, section_frame, sectionIndex'
		),
		'5' => array(
			'showitem' => 'imagecaption_position'
		),
		'6' => array(
			'showitem' => 'imagewidth,image_link'
		),
		'7' => array(
			'showitem' => 'image_link, image_zoom',
			'canNotCollapse' => 1
		),
		'8' => array(
			'showitem' => 'layout'
		),
		'10' => array(
			'showitem' => 'table_bgColor, table_border, table_cellspacing, table_cellpadding'
		),
		'11' => array(
			'showitem' => 'image_compression, image_effects, image_frames',
			'canNotCollapse' => 1
		),
		'12' => array(
			'showitem' => 'recursive'
		),
		'13' => array(
			'showitem' => 'imagewidth, imageheight',
			'canNotCollapse' => 1
		),
		'14' => array(
			'showitem' => 'sys_language_uid, l18n_parent, colPos'
		),
		'general' => array(
			'showitem' => 'CType;LLL:EXT:cms/locallang_ttc.xlf:CType_formlabel, colPos;LLL:EXT:cms/locallang_ttc.xlf:colPos_formlabel, sys_language_uid;LLL:EXT:cms/locallang_ttc.xlf:sys_language_uid_formlabel, --linebreak--, l18n_parent',
			'canNotCollapse' => 1
		),
		'header' => array(
			'showitem' => 'header;LLL:EXT:cms/locallang_ttc.xlf:header_formlabel, --linebreak--, header_layout;LLL:EXT:cms/locallang_ttc.xlf:header_layout_formlabel, header_position;LLL:EXT:cms/locallang_ttc.xlf:header_position_formlabel, date;LLL:EXT:cms/locallang_ttc.xlf:date_formlabel, --linebreak--, header_link;LLL:EXT:cms/locallang_ttc.xlf:header_link_formlabel',
			'canNotCollapse' => 1
		),
		'headers' => array(
			'showitem' => 'header;LLL:EXT:cms/locallang_ttc.xlf:header_formlabel, --linebreak--, header_layout;LLL:EXT:cms/locallang_ttc.xlf:header_layout_formlabel, header_position;LLL:EXT:cms/locallang_ttc.xlf:header_position_formlabel, date;LLL:EXT:cms/locallang_ttc.xlf:date_formlabel, --linebreak--, header_link;LLL:EXT:cms/locallang_ttc.xlf:header_link_formlabel, --linebreak--, subheader;LLL:EXT:cms/locallang_ttc.xlf:subheader_formlabel',
			'canNotCollapse' => 1
		),
		'multimediafiles' => array(
			'showitem' => 'multimedia;LLL:EXT:cms/locallang_ttc.xlf:multimedia_formlabel, bodytext;LLL:EXT:cms/locallang_ttc.xlf:bodytext.ALT.multimedia_formlabel;;nowrap',
			'canNotCollapse' => 1
		),
		'imagelinks' => array(
			'showitem' => 'image_zoom;LLL:EXT:cms/locallang_ttc.xlf:image_zoom_formlabel',
			'canNotCollapse' => 1
		),
		'image_accessibility' => array(
			'showitem' => 'altText;LLL:EXT:cms/locallang_ttc.xlf:altText_formlabel, titleText;LLL:EXT:cms/locallang_ttc.xlf:titleText_formlabel, --linebreak--, longdescURL;LLL:EXT:cms/locallang_ttc.xlf:longdescURL_formlabel',
			'canNotCollapse' => 1
		),
		'image_settings' => array(
			'showitem' => 'imagewidth;LLL:EXT:cms/locallang_ttc.xlf:imagewidth_formlabel, imageheight;LLL:EXT:cms/locallang_ttc.xlf:imageheight_formlabel, imageborder;LLL:EXT:cms/locallang_ttc.xlf:imageborder_formlabel, --linebreak--, image_compression;LLL:EXT:cms/locallang_ttc.xlf:image_compression_formlabel, image_effects;LLL:EXT:cms/locallang_ttc.xlf:image_effects_formlabel, image_frames;LLL:EXT:cms/locallang_ttc.xlf:image_frames_formlabel',
			'canNotCollapse' => 1
		),
		'imageblock' => array(
			'showitem' => 'imageorient;LLL:EXT:cms/locallang_ttc.xlf:imageorient_formlabel, imagecols;LLL:EXT:cms/locallang_ttc.xlf:imagecols_formlabel, --linebreak--, image_noRows;LLL:EXT:cms/locallang_ttc.xlf:image_noRows_formlabel, imagecaption_position;LLL:EXT:cms/locallang_ttc.xlf:imagecaption_position_formlabel',
			'canNotCollapse' => 1
		),
		'uploads' => array(
			'showitem' => 'media;LLL:EXT:cms/locallang_ttc.xlf:media.ALT.uploads_formlabel, --linebreak--, file_collections;LLL:EXT:cms/locallang_ttc.xlf:file_collections.ALT.uploads_formlabel, --linebreak--, filelink_sorting, target',
			'canNotCollapse' => 1
		),
		'mailform' => array(
			'showitem' => 'pages;LLL:EXT:cms/locallang_ttc.xlf:pages.ALT.mailform, --linebreak--, subheader;LLL:EXT:cms/locallang_ttc.xlf:subheader.ALT.mailform_formlabel',
			'canNotCollapse' => 1
		),
		'searchform' => array(
			'showitem' => 'pages;LLL:EXT:cms/locallang_ttc.xlf:pages.ALT.searchform',
			'canNotCollapse' => 1
		),
		'menu' => array(
			'showitem' => 'menu_type;LLL:EXT:cms/locallang_ttc.xlf:menu_type_formlabel, --linebreak--, pages;LLL:EXT:cms/locallang_ttc.xlf:pages.ALT.menu_formlabel',
			'canNotCollapse' => 1
		),
		'menu_accessibility' => array(
			'showitem' => 'accessibility_title;LLL:EXT:cms/locallang_ttc.xlf:menu.ALT.accessibility_title_formlabel, --linebreak--, accessibility_bypass;LLL:EXT:cms/locallang_ttc.xlf:menu.ALT.accessibility_bypass_formlabel, accessibility_bypass_text;LLL:EXT:cms/locallang_ttc.xlf:menu.ALT.accessibility_bypass_text_formlabel',
			'canNotCollapse' => 1
		),
		'visibility' => array(
			'showitem' => 'hidden;LLL:EXT:cms/locallang_ttc.xlf:hidden_formlabel, sectionIndex;LLL:EXT:cms/locallang_ttc.xlf:sectionIndex_formlabel, linkToTop;LLL:EXT:cms/locallang_ttc.xlf:linkToTop_formlabel',
			'canNotCollapse' => 1
		),
		'access' => array(
			'showitem' => 'starttime;LLL:EXT:cms/locallang_ttc.xlf:starttime_formlabel, endtime;LLL:EXT:cms/locallang_ttc.xlf:endtime_formlabel, --linebreak--, fe_group;LLL:EXT:cms/locallang_ttc.xlf:fe_group_formlabel',
			'canNotCollapse' => 1
		),
		'frames' => array(
			'showitem' => 'layout;LLL:EXT:cms/locallang_ttc.xlf:layout_formlabel, spaceBefore;LLL:EXT:cms/locallang_ttc.xlf:spaceBefore_formlabel, spaceAfter;LLL:EXT:cms/locallang_ttc.xlf:spaceAfter_formlabel, section_frame;LLL:EXT:cms/locallang_ttc.xlf:section_frame_formlabel',
			'canNotCollapse' => 1
		),
		'tablelayout' => array(
			'showitem' => 'table_bgColor;LLL:EXT:cms/locallang_ttc.xlf:table_bgColor_formlabel, table_border;LLL:EXT:cms/locallang_ttc.xlf:table_border_formlabel, table_cellspacing;LLL:EXT:cms/locallang_ttc.xlf:table_cellspacing_formlabel, table_cellpadding;LLL:EXT:cms/locallang_ttc.xlf:table_cellpadding_formlabel',
			'canNotCollapse' => 1
		),
		'uploadslayout' => array(
			'showitem' => 'filelink_size;LLL:EXT:cms/locallang_ttc.xlf:filelink_size_formlabel',
			'canNotCollapse' => 1
		)
	)
);

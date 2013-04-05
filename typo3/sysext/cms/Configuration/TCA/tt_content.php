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
						'LLL:EXT:cms/locallang_ttc.xml:CType.div.standard',
						'--div--'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:CType.I.0',
						'header',
						'i/tt_content_header.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:CType.I.1',
						'text',
						'i/tt_content.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:CType.I.2',
						'textpic',
						'i/tt_content_textpic.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:CType.I.3',
						'image',
						'i/tt_content_image.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:CType.div.lists',
						'--div--'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:CType.I.4',
						'bullets',
						'i/tt_content_bullets.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:CType.I.5',
						'table',
						'i/tt_content_table.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:CType.I.6',
						'uploads',
						'i/tt_content_uploads.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:CType.div.forms',
						'--div--'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:CType.I.8',
						'mailform',
						'i/tt_content_form.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:CType.I.9',
						'search',
						'i/tt_content_search.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:CType.div.special',
						'--div--'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:CType.I.7',
						'multimedia',
						'i/tt_content_mm.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:CType.I.18',
						'media',
						'i/tt_content_mm.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:CType.I.12',
						'menu',
						'i/tt_content_menu.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:CType.I.13',
						'shortcut',
						'i/tt_content_shortcut.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:CType.I.14',
						'list',
						'i/tt_content_list.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:CType.I.16',
						'div',
						'i/tt_content_div.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:CType.I.17',
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
						'0' => 'LLL:EXT:cms/locallang_ttc.xml:hidden.I.0'
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
						'LLL:EXT:cms/locallang_ttc.xml:layout.I.1',
						'1'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:layout.I.2',
						'2'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:layout.I.3',
						'3'
					)
				),
				'default' => '0'
			)
		),
		'colPos' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:colPos',
			'config' => array(
				'type' => 'select',
				'itemsProcFunc' => 'EXT:cms/classes/class.tx_cms_backendlayout.php:TYPO3\\CMS\\Backend\\View\\BackendLayoutView->colPosListItemProcFunc',
				'items' => array(
					array(
						'LLL:EXT:cms/locallang_ttc.xml:colPos.I.0',
						'1'
					),
					array(
						'LLL:EXT:lang/locallang_general.xlf:LGL.normal',
						'0'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:colPos.I.2',
						'2'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:colPos.I.3',
						'3'
					)
				),
				'default' => '0'
			)
		),
		'date' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:date',
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
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:header',
			'config' => array(
				'type' => 'input',
				'size' => '50',
				'max' => '256'
			)
		),
		'header_position' => array(
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:header_position',
			'exclude' => 1,
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'LLL:EXT:lang/locallang_general.xlf:LGL.default_value',
						''
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:header_position.I.1',
						'center'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:header_position.I.2',
						'right'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:header_position.I.3',
						'left'
					)
				),
				'default' => ''
			)
		),
		'header_link' => array(
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:header_link',
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
						'title' => 'LLL:EXT:cms/locallang_ttc.xml:header_link_formlabel',
						'icon' => 'link_popup.gif',
						'script' => 'browse_links.php?mode=wizard',
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
						'LLL:EXT:cms/locallang_ttc.xml:header_layout.I.1',
						'1'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:header_layout.I.2',
						'2'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:header_layout.I.3',
						'3'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:header_layout.I.4',
						'4'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:header_layout.I.5',
						'5'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:header_layout.I.6',
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
				'cols' => '48',
				'rows' => '5',
				'wizards' => array(
					'_PADDING' => 4,
					'_VALIGN' => 'middle',
					'RTE' => array(
						'notNewRecords' => 1,
						'RTEonly' => 1,
						'type' => 'script',
						'title' => 'LLL:EXT:cms/locallang_ttc.xml:bodytext.W.RTE',
						'icon' => 'wizard_rte2.gif',
						'script' => 'wizard_rte.php'
					),
					'table' => array(
						'notNewRecords' => 1,
						'enableByTypeConfig' => 1,
						'type' => 'script',
						'title' => 'LLL:EXT:cms/locallang_ttc.xml:bodytext.W.table',
						'icon' => 'wizard_table.gif',
						'script' => 'wizard_table.php',
						'params' => array(
							'xmlOutput' => 0
						)
					),
					'forms' => array(
						'notNewRecords' => 1,
						'enableByTypeConfig' => 1,
						'type' => 'script',
						'title' => 'LLL:EXT:cms/locallang_ttc.xml:bodytext.W.forms',
						'icon' => 'wizard_forms.gif',
						'script' => 'wizard_forms.php?special=formtype_mail',
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
		'text_align' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:text_align',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'',
						''
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:text_align.I.1',
						'center'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:text_align.I.2',
						'right'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:text_align.I.3',
						'left'
					)
				),
				'default' => ''
			)
		),
		'text_face' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:text_face',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'LLL:EXT:lang/locallang_general.xlf:LGL.default_value',
						'0'
					),
					array(
						'Times',
						'1'
					),
					array(
						'Verdana',
						'2'
					),
					array(
						'Arial',
						'3'
					)
				),
				'default' => '0'
			)
		),
		'text_size' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:text_size',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'LLL:EXT:lang/locallang_general.xlf:LGL.default_value',
						'0'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:text_size.I.1',
						'1'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:text_size.I.2',
						'2'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:text_size.I.3',
						'3'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:text_size.I.4',
						'4'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:text_size.I.5',
						'5'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:text_size.I.6',
						'10'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:text_size.I.7',
						'11'
					)
				),
				'default' => '0'
			)
		),
		'text_color' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:text_color',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'LLL:EXT:lang/locallang_general.xlf:LGL.default_value',
						'0'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:text_color.I.1',
						'1'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:text_color.I.2',
						'2'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:text_color.I.3',
						'200'
					),
					array(
						'-----',
						'--div--'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:text_color.I.5',
						'240'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:text_color.I.6',
						'241'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:text_color.I.7',
						'242'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:text_color.I.8',
						'243'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:text_color.I.9',
						'244'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:text_color.I.10',
						'245'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:text_color.I.11',
						'246'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:text_color.I.12',
						'247'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:text_color.I.13',
						'248'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:text_color.I.14',
						'249'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:text_color.I.15',
						'250'
					)
				),
				'default' => '0'
			)
		),
		'text_properties' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:text_properties',
			'config' => array(
				'type' => 'check',
				'items' => array(
					array(
						'LLL:EXT:cms/locallang_ttc.xml:text_properties.I.0',
						''
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:text_properties.I.1',
						''
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:text_properties.I.2',
						''
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:text_properties.I.3',
						''
					)
				),
				'cols' => 4
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
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:imagewidth',
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
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:imageheight',
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
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:imageorient',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'LLL:EXT:cms/locallang_ttc.xml:imageorient.I.0',
						0,
						'selicons/above_center.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:imageorient.I.1',
						1,
						'selicons/above_right.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:imageorient.I.2',
						2,
						'selicons/above_left.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:imageorient.I.3',
						8,
						'selicons/below_center.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:imageorient.I.4',
						9,
						'selicons/below_right.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:imageorient.I.5',
						10,
						'selicons/below_left.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:imageorient.I.6',
						17,
						'selicons/intext_right.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:imageorient.I.7',
						18,
						'selicons/intext_left.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:imageorient.I.8',
						'--div--'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:imageorient.I.9',
						25,
						'selicons/intext_right_nowrap.gif'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:imageorient.I.10',
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
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:imageborder',
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
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:image_noRows',
			'config' => array(
				'type' => 'check',
				'items' => array(
					'1' => array(
						'0' => 'LLL:EXT:cms/locallang_ttc.xml:image_noRows.I.0'
					)
				)
			)
		),
		'image_link' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:image_link',
			'config' => array(
				'type' => 'text',
				'cols' => '30',
				'rows' => '3',
				'wizards' => array(
					'_PADDING' => 2,
					'link' => array(
						'type' => 'popup',
						'title' => 'LLL:EXT:cms/locallang_ttc.xml:image_link_formlabel',
						'icon' => 'link_popup.gif',
						'script' => 'browse_links.php?mode=wizard',
						'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1'
					)
				),
				'softref' => 'typolink[linkList]'
			)
		),
		'image_zoom' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:image_zoom',
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
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:image_effects',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'LLL:EXT:cms/locallang_ttc.xml:image_effects.I.0',
						0
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:image_effects.I.1',
						1
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:image_effects.I.2',
						2
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:image_effects.I.3',
						3
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:image_effects.I.4',
						10
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:image_effects.I.5',
						11
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:image_effects.I.6',
						20
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:image_effects.I.7',
						23
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:image_effects.I.8',
						25
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:image_effects.I.9',
						26
					)
				)
			)
		),
		'image_frames' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:image_frames',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'LLL:EXT:cms/locallang_ttc.xml:image_frames.I.0',
						0
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:image_frames.I.1',
						1
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:image_frames.I.2',
						2
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:image_frames.I.3',
						3
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:image_frames.I.4',
						4
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:image_frames.I.5',
						5
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:image_frames.I.6',
						6
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:image_frames.I.7',
						7
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:image_frames.I.8',
						8
					)
				)
			)
		),
		'image_compression' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:image_compression',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'LLL:EXT:lang/locallang_general.xlf:LGL.default_value',
						0
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:image_compression.I.1',
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
						'LLL:EXT:cms/locallang_ttc.xml:image_compression.I.15',
						21
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:image_compression.I.16',
						22
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:image_compression.I.17',
						24
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:image_compression.I.18',
						26
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:image_compression.I.19',
						28
					)
				)
			)
		),
		'imagecols' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:imagecols',
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
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:imagecaption_position',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'LLL:EXT:lang/locallang_general.xlf:LGL.default_value',
						''
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:imagecaption_position.I.1',
						'center'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:imagecaption_position.I.2',
						'right'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:imagecaption_position.I.3',
						'left'
					)
				),
				'default' => ''
			)
		),
		'altText' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:image_altText',
			'config' => array(
				'type' => 'text',
				'cols' => '30',
				'rows' => '3'
			)
		),
		'titleText' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:image_titleText',
			'config' => array(
				'type' => 'text',
				'cols' => '30',
				'rows' => '3'
			)
		),
		'longdescURL' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:image_longdescURL',
			'config' => array(
				'type' => 'text',
				'cols' => '30',
				'rows' => '3',
				'wizards' => array(
					'_PADDING' => 2,
					'link' => array(
						'type' => 'popup',
						'title' => 'LLL:EXT:cms/locallang_ttc.xml:image_link_formlabel',
						'icon' => 'link_popup.gif',
						'script' => 'browse_links.php?mode=wizard',
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
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:cols',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'LLL:EXT:cms/locallang_ttc.xml:cols.I.0',
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
						'LLL:EXT:cms/locallang_ttc.xml:recursive.I.0',
						'0'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:recursive.I.1',
						'1'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:recursive.I.2',
						'2'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:recursive.I.3',
						'3'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:recursive.I.4',
						'4'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:recursive.I.5',
						'250'
					)
				),
				'default' => '0'
			)
		),
		'menu_type' => array(
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:menu_type',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'LLL:EXT:cms/locallang_ttc.xml:menu_type.I.0',
						'0'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:menu_type.I.1',
						'1'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:menu_type.I.2',
						'4'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:menu_type.I.3',
						'7'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:menu_type.I.4',
						'2'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:menu_type.I.8',
						'8'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:menu_type.I.5',
						'3'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:menu_type.I.6',
						'5'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:menu_type.I.7',
						'6'
					)
				),
				'default' => '0'
			)
		),
		'list_type' => array(
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:list_type',
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
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:table_bgColor',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'LLL:EXT:lang/locallang_general.xlf:LGL.default_value',
						'0'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:table_bgColor.I.1',
						'1'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:table_bgColor.I.2',
						'2'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:table_bgColor.I.3',
						'200'
					),
					array(
						'-----',
						'--div--'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:table_bgColor.I.5',
						'240'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:table_bgColor.I.6',
						'241'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:table_bgColor.I.7',
						'242'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:table_bgColor.I.8',
						'243'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:table_bgColor.I.9',
						'244'
					)
				),
				'default' => '0'
			)
		),
		'table_border' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:table_border',
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
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:table_cellspacing',
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
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:table_cellpadding',
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
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:media',
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
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:multimedia',
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
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:filelink_size',
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
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:filelink_sorting',
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
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:target',
			'config' => array(
				'type' => 'input',
				'size' => 20,
				'eval' => 'trim',
				'wizards' => array(
					'target_picker' => array(
						'type' => 'select',
						'mode' => '',
						'items' => array(
							array('LLL:EXT:cms/locallang_ttc.xml:target.I.1', '_blank')
						)
					)
				),
				'default' => ''
			)
		),
		'records' => array(
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:records',
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
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:spaceBefore',
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
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:spaceAfter',
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
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:section_frame',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array(
						'',
						'0'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:section_frame.I.1',
						'1'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:section_frame.I.2',
						'5'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:section_frame.I.3',
						'6'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:section_frame.I.4',
						'10'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:section_frame.I.5',
						'11'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:section_frame.I.6',
						'12'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:section_frame.I.7',
						'20'
					),
					array(
						'LLL:EXT:cms/locallang_ttc.xml:section_frame.I.8',
						'21'
					)
				),
				'default' => '0'
			)
		),
		'sectionIndex' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:sectionIndex',
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
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:linkToTop',
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
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:rte_enabled',
			'config' => array(
				'type' => 'check',
				'showIfRTE' => 1,
				'items' => array(
					'1' => array(
						'0' => 'LLL:EXT:cms/locallang_ttc.xml:rte_enabled.I.0'
					)
				)
			)
		),
		'pi_flexform' => array(
			'l10n_display' => 'hideDiff',
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:pi_flexform',
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
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:accessibility_title',
			'config' => array(
				'type' => 'input',
				'size' => 20,
				'eval' => 'trim',
				'default' => ''
			)
		),
		'accessibility_bypass' => array(
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:accessibility_bypass',
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
			'label' => 'LLL:EXT:cms/locallang_ttc.xml:accessibility_bypass_text',
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
		)
	),
	'types' => array(
		'1' => array(
			'showitem' => 'CType'
		),
		'header' => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.general;general,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.headers;headers,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.frames;frames,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.access,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.access;access,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.extended'
		),
		'text' => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.general;general,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.header;header,
					bodytext;LLL:EXT:cms/locallang_ttc.xml:bodytext_formlabel;;richtext:rte_transform[flag=rte_enabled|mode=ts_css],
					rte_enabled;LLL:EXT:cms/locallang_ttc.xml:rte_enabled_formlabel,
					--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.appearance,
						--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.frames;frames,
						--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.textlayout;textlayout,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.access,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.access;access,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.extended'
		),
		'textpic' => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.general;general,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.header;header,
					bodytext;Text;;richtext:rte_transform[flag=rte_enabled|mode=ts_css],
					rte_enabled;LLL:EXT:cms/locallang_ttc.xml:rte_enabled_formlabel,' . '--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.images,
					image,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.imagelinks;imagelinks,' . '--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.frames;frames,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.image_settings;image_settings,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.imageblock;imageblock,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.textlayout;textlayout,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.access,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.access;access,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.extended'
		),
		'image' => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.general;general,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.header;header,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.images,
					image,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.imagelinks;imagelinks,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.frames;frames,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.image_settings;image_settings,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.imageblock;imageblock,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.access,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.access;access,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.extended'
		),
		'bullets' => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.general;general,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.header;header,
					bodytext;LLL:EXT:cms/locallang_ttc.xml:bodytext.ALT.bulletlist_formlabel;;nowrap,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.frames;frames,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.textlayout;textlayout,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.access,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.access;access,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.extended'
		),
		'table' => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.general;general,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.header;header,
					bodytext;LLL:EXT:cms/locallang_ttc.xml:bodytext.ALT.table_formlabel;;nowrap:wizards[table],
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.frames;frames,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.table_layout;tablelayout,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.textlayout;textlayout,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.access,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.access;access,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.extended'
		),
		// file list
		'uploads' => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.general;general,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.header;header,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:media;uploads,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.frames;frames,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.uploads_layout;uploadslayout,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.access,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.access;access,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.extended'
		),
		'multimedia' => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.general;general,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.header;header,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.media,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.multimediafiles;multimediafiles,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.frames;frames,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.access,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.access;access,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.extended'
		),
		'media' => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.general;general,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.header;header,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.media,
					pi_flexform; ;,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.frames;frames,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.access,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.access;access,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.behaviour,
					bodytext;LLL:EXT:cms/locallang_ttc.xml:bodytext.ALT.media_formlabel;;richtext:rte_transform[flag=rte_enabled|mode=ts_css],
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.extended'
		),
		'menu' => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.general;general,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.header;header,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.menu;menu,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.menu_accessibility;menu_accessibility,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.frames;frames,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.access,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.access;access,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.extended',
			'subtype_value_field' => 'menu_type',
			'subtypes_excludelist' => array(
				'2' => 'pages'
			)
		),
		'mailform' => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.general;general,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.header;header,
					bodytext;LLL:EXT:cms/locallang_ttc.xml:bodytext.ALT.mailform_formlabel;;nowrap:wizards[forms],
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.frames;frames,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.access,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.access;access,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.behaviour,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.mailform;mailform,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.extended'
		),
		'search' => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.general;general,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.header;header,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.frames;frames,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.access,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.access;access,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.behaviour,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.searchform;searchform,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.extended'
		),
		'shortcut' => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.general;general,
					header;LLL:EXT:cms/locallang_ttc.xml:header.ALT.shortcut_formlabel,
					records;LLL:EXT:cms/locallang_ttc.xml:records_formlabel,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.frames;frames,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.access,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.access;access,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.extended'
		),
		'list' => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.general;general,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.header;header,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.plugin,
					list_type;LLL:EXT:cms/locallang_ttc.xml:list_type_formlabel,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.frames;frames,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.access,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.access;access,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.behaviour,
					select_key;LLL:EXT:cms/locallang_ttc.xml:select_key_formlabel,
					pages;LLL:EXT:cms/locallang_ttc.xml:pages.ALT.list_formlabel,
					recursive,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.extended',
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
			'showitem' => '--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.general;general,
					header;LLL:EXT:cms/locallang_ttc.xml:header.ALT.div_formlabel,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.frames;frames,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.access,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.access;access,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.extended'
		),
		'html' => array(
			'showitem' => '--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.general;general,
					header;LLL:EXT:cms/locallang_ttc.xml:header.ALT.html_formlabel,
					bodytext,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.frames;frames,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.access,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_ttc.xml:palette.access;access,
				--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.extended'
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
		'9' => array(
			'showitem' => 'text_align,text_face,text_size,text_color'
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
			'showitem' => 'CType;LLL:EXT:cms/locallang_ttc.xml:CType_formlabel, colPos;LLL:EXT:cms/locallang_ttc.xml:colPos_formlabel, sys_language_uid;LLL:EXT:cms/locallang_ttc.xml:sys_language_uid_formlabel',
			'canNotCollapse' => 1
		),
		'header' => array(
			'showitem' => 'header;LLL:EXT:cms/locallang_ttc.xml:header_formlabel, --linebreak--, header_layout;LLL:EXT:cms/locallang_ttc.xml:header_layout_formlabel, header_position;LLL:EXT:cms/locallang_ttc.xml:header_position_formlabel, date;LLL:EXT:cms/locallang_ttc.xml:date_formlabel, --linebreak--, header_link;LLL:EXT:cms/locallang_ttc.xml:header_link_formlabel',
			'canNotCollapse' => 1
		),
		'headers' => array(
			'showitem' => 'header;LLL:EXT:cms/locallang_ttc.xml:header_formlabel, --linebreak--, header_layout;LLL:EXT:cms/locallang_ttc.xml:header_layout_formlabel, header_position;LLL:EXT:cms/locallang_ttc.xml:header_position_formlabel, date;LLL:EXT:cms/locallang_ttc.xml:date_formlabel, --linebreak--, header_link;LLL:EXT:cms/locallang_ttc.xml:header_link_formlabel, --linebreak--, subheader;LLL:EXT:cms/locallang_ttc.xml:subheader_formlabel',
			'canNotCollapse' => 1
		),
		'multimediafiles' => array(
			'showitem' => 'multimedia;LLL:EXT:cms/locallang_ttc.xml:multimedia_formlabel, bodytext;LLL:EXT:cms/locallang_ttc.xml:bodytext.ALT.multimedia_formlabel;;nowrap',
			'canNotCollapse' => 1
		),
		'imagelinks' => array(
			'showitem' => 'image_zoom;LLL:EXT:cms/locallang_ttc.xml:image_zoom_formlabel',
			'canNotCollapse' => 1
		),
		'image_accessibility' => array(
			'showitem' => 'altText;LLL:EXT:cms/locallang_ttc.xml:altText_formlabel, titleText;LLL:EXT:cms/locallang_ttc.xml:titleText_formlabel, --linebreak--, longdescURL;LLL:EXT:cms/locallang_ttc.xml:longdescURL_formlabel',
			'canNotCollapse' => 1
		),
		'image_settings' => array(
			'showitem' => 'imagewidth;LLL:EXT:cms/locallang_ttc.xml:imagewidth_formlabel, imageheight;LLL:EXT:cms/locallang_ttc.xml:imageheight_formlabel, imageborder;LLL:EXT:cms/locallang_ttc.xml:imageborder_formlabel, --linebreak--, image_compression;LLL:EXT:cms/locallang_ttc.xml:image_compression_formlabel, image_effects;LLL:EXT:cms/locallang_ttc.xml:image_effects_formlabel, image_frames;LLL:EXT:cms/locallang_ttc.xml:image_frames_formlabel',
			'canNotCollapse' => 1
		),
		'imageblock' => array(
			'showitem' => 'imageorient;LLL:EXT:cms/locallang_ttc.xml:imageorient_formlabel, imagecols;LLL:EXT:cms/locallang_ttc.xml:imagecols_formlabel, --linebreak--, image_noRows;LLL:EXT:cms/locallang_ttc.xml:image_noRows_formlabel, imagecaption_position;LLL:EXT:cms/locallang_ttc.xml:imagecaption_position_formlabel',
			'canNotCollapse' => 1
		),
		'uploads' => array(
			'showitem' => 'media;LLL:EXT:cms/locallang_ttc.xml:media.ALT.uploads_formlabel, --linebreak--, file_collections;LLL:EXT:cms/locallang_ttc.xml:file_collections.ALT.uploads_formlabel, --linebreak--, filelink_sorting, target',
			'canNotCollapse' => 1
		),
		'mailform' => array(
			'showitem' => 'pages;LLL:EXT:cms/locallang_ttc.xml:pages.ALT.mailform, --linebreak--, subheader;LLL:EXT:cms/locallang_ttc.xml:subheader.ALT.mailform_formlabel',
			'canNotCollapse' => 1
		),
		'searchform' => array(
			'showitem' => 'pages;LLL:EXT:cms/locallang_ttc.xml:pages.ALT.searchform',
			'canNotCollapse' => 1
		),
		'menu' => array(
			'showitem' => 'menu_type;LLL:EXT:cms/locallang_ttc.xml:menu_type_formlabel, --linebreak--, pages;LLL:EXT:cms/locallang_ttc.xml:pages.ALT.menu_formlabel',
			'canNotCollapse' => 1
		),
		'menu_accessibility' => array(
			'showitem' => 'accessibility_title;LLL:EXT:cms/locallang_ttc.xml:menu.ALT.accessibility_title_formlabel, --linebreak--, accessibility_bypass;LLL:EXT:cms/locallang_ttc.xml:menu.ALT.accessibility_bypass_formlabel, accessibility_bypass_text;LLL:EXT:cms/locallang_ttc.xml:menu.ALT.accessibility_bypass_text_formlabel',
			'canNotCollapse' => 1
		),
		'visibility' => array(
			'showitem' => 'hidden;LLL:EXT:cms/locallang_ttc.xml:hidden_formlabel, sectionIndex;LLL:EXT:cms/locallang_ttc.xml:sectionIndex_formlabel, linkToTop;LLL:EXT:cms/locallang_ttc.xml:linkToTop_formlabel',
			'canNotCollapse' => 1
		),
		'access' => array(
			'showitem' => 'starttime;LLL:EXT:cms/locallang_ttc.xml:starttime_formlabel, endtime;LLL:EXT:cms/locallang_ttc.xml:endtime_formlabel, --linebreak--, fe_group;LLL:EXT:cms/locallang_ttc.xml:fe_group_formlabel',
			'canNotCollapse' => 1
		),
		'frames' => array(
			'showitem' => 'layout;LLL:EXT:cms/locallang_ttc.xml:layout_formlabel, spaceBefore;LLL:EXT:cms/locallang_ttc.xml:spaceBefore_formlabel, spaceAfter;LLL:EXT:cms/locallang_ttc.xml:spaceAfter_formlabel, section_frame;LLL:EXT:cms/locallang_ttc.xml:section_frame_formlabel',
			'canNotCollapse' => 1
		),
		'textlayout' => array(
			'showitem' => 'text_align;LLL:EXT:cms/locallang_ttc.xml:text_align_formlabel, text_face;LLL:EXT:cms/locallang_ttc.xml:text_face_formlabel, text_size;LLL:EXT:cms/locallang_ttc.xml:text_size_formlabel, text_color;LLL:EXT:cms/locallang_ttc.xml:text_color_formlabel, --linebreak--, text_properties;LLL:EXT:cms/locallang_ttc.xml:text_properties_formlabel',
			'canNotCollapse' => 1
		),
		'tablelayout' => array(
			'showitem' => 'table_bgColor;LLL:EXT:cms/locallang_ttc.xml:table_bgColor_formlabel, table_border;LLL:EXT:cms/locallang_ttc.xml:table_border_formlabel, table_cellspacing;LLL:EXT:cms/locallang_ttc.xml:table_cellspacing_formlabel, table_cellpadding;LLL:EXT:cms/locallang_ttc.xml:table_cellpadding_formlabel',
			'canNotCollapse' => 1
		),
		'uploadslayout' => array(
			'showitem' => 'filelink_size;LLL:EXT:cms/locallang_ttc.xml:filelink_size_formlabel',
			'canNotCollapse' => 1
		)
	)
);

?>
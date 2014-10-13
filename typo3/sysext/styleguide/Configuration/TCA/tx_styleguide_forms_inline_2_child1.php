<?php
return array(
	'ctrl' => array(
		'title' => 'Form engine tests - inline_2 child 1',
		'label' => 'input_1',
		'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('styleguide') . 'Resources/Public/Icons/tx_styleguide_forms_staticdata.png',

		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'delete' => 'deleted',
		'sortby' => 'sorting',
		'default_sortby' => 'ORDER BY crdate',

		'dividers2tabs' => 1,
	),
	'columns' => array(
		'sys_language_uid' => array(
			'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0)
				)
			)
		),
		'l18n_parent' => array(
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0),
				),
				'foreign_table' => 'tx_styleguide_forms_inline_2_child1',
				'foreign_table_where' => 'AND tx_styleguide_forms_inline_2_child1.pid=###CURRENT_PID### AND tx_styleguide_forms_inline_2_child1.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => array(
			'config' => array(
				'type' => 'passthrough'
			)
		),
		'hidden' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),
		'parentid' => array(
			'config' => array(
				'type' => 'passthrough',
			)
		),
		'parenttable' => array(
			'config' => array(
				'type' => 'passthrough',
			)
		),
		'input_1' => array(
			'l10n_mode' => 'prefixLangTitle',
			'label' => 'input_1 eval=required',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'required',
			),
		),
		'inline_1' => array(
			'label' => '1 1:n foreign field to table without sheets',
			'config' => array(
				'type' => 'inline',
				'foreign_table' => 'tx_styleguide_forms_inline_2_child2',
				'foreign_field' => 'parentid',
				'foreign_table_field' => 'parenttable',
				'maxitems' => 10,
				'appearance' => array(
					'showSynchronizationLink' => TRUE,
					'showAllLocalizationLink' => TRUE,
					'showPossibleLocalizationRecords' => TRUE,
					'showRemovedLocalizationRecords' => TRUE,
				),
				'behaviour' => array(
					'localizationMode' => 'select',
					'localizeChildrenAtParentLocalization' => TRUE,
				),
			),
		),
		'inline_2' => array(
			'label' => '2 typical FAL field',
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
					'useSortable' => TRUE,
					'headerThumbnail' => array(
						'field' => "uid_local",
						'width' => "45",
						'height' => "45c",
					),
				),
				'showPossibleLocalizationRecords' => FALSE,
				'showRemovedLocalizationRecords' => FALSE,
				'showSynchronizationLink' => FALSE,
				'showAllLocalizationLink' => FALSE,
				'enabledControls' => array(
					'info' => TRUE,
					'new' => FALSE,
					'dragdrop' => TRUE,
					'sort' => FALSE,
					'hide' => TRUE,
					'delete' => TRUE,
					'localize' => TRUE,
				),
				'createNewRelationLinkTitle' => "LLL:EXT:cms/locallang_ttc.xlf:images.addFileReference",
				'behaviour' => array(
					'localizationMode' => "select",
					'localizeChildrenAtParentLocalization' => TRUE,
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
	),
	'interface' => array(
		'showRecordFieldList' => '
			sys_language_uid, l18n_parent, l18n_diffsource, hidden, parentid, parenttable,
			input_1, inline_1, inline_2,
		',
	),
	'types' => array(
		'0' => array(
			'showitem' => '
				--div--;General, input_1, inline_1, inline_2,
				--div--;Visibility, sys_language_uid, l18n_parent, l18n_diffsource, hidden, parentid, parenttable
			',
		),
	),
);
<?php
return array(
	'ctrl' => array(
		'title' => 'Form engine tests - inline expand inline_1 child 1',
		'label' => 'rte_1',
		'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide_forms_staticdata.png',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'delete' => 'deleted',
		'sortby' => 'sorting',
		'default_sortby' => 'ORDER BY crdate',
	),
	'columns' => array(
		'sys_language_uid' => array(
			'exclude' => 1,
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
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0),
				),
				'foreign_table' => 'tx_styleguide_forms_inline_2_child2',
				'foreign_table_where' => 'AND tx_styleguide_forms_inline_2_child2.pid=###CURRENT_PID### AND tx_styleguide_forms_inline_2_child2.sys_language_uid IN (-1,0)',
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
			),
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

		'rte_1' => array(
			'exclude' => 1,
			'label' => 'RTE 1',
			'config' => array(
				'type' => 'text',
			),
			'defaultExtras' => 'richtext[*]:rte_transform[mode=ts_css]',
		),

		'tree_1' => array(
			'exclude' => 1,
			'label' => 'SELECT TREE 1',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'pages',
				'size' => 8,
				'maxitems' => 99, // @TODO: *must* be set, otherwise invalid upon checking first item?!
				'renderMode' => 'tree',
				'treeConfig' => array(
					'expandAll' => true,
					'parentField' => 'pid',
					'appearance' => array(
						'showHeader' => TRUE,
					),
				),
			),
		),

		'fal_1' => array(
			'label' => 'FAL 1',
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
					'createNewRelationLinkTitle' => "LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference",
				),
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
			rte_1, tree_1, fal_1,
		',
	),

	'types' => array(
		'0' => array(
			'showitem' => '
				tree_1, fal_1, rte_1,
			',
		),
	),
);

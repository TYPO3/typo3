<?php
return array(
	'ctrl' => array(
		'title' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_reference',
		'label' => 'uid',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'type' => 'uid_local:type',
		'hideTable' => TRUE,
		'sortby' => 'sorting',
		'delete' => 'deleted',
		'versioningWS' => TRUE,
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l10n_parent',
		'transOrigDiffSourceField' => 'l10n_diffsource',
		// records can and should be edited in workspaces
		'shadowColumnsForNewPlaceholders' => 'tablenames,fieldname,uid_local,uid_foreign',
		'enablecolumns' => array(
			'disabled' => 'hidden'
		),
		'security' => array(
			'ignoreWebMountRestriction' => TRUE,
			'ignoreRootLevelRestriction' => TRUE,
		),
	),
	'interface' => array(
		'showRecordFieldList' => 'hidden,uid_local,uid_foreign,tablenames,fieldname,sorting_foreign,table_local,title,description'
	),
	'columns' => array(
		't3ver_label' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.versionLabel',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'max' => '30'
			)
		),
		'sys_language_uid' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.language',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xlf:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.xlf:LGL.default_value', 0)
				)
			)
		),
		'l10n_parent' => array(
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.l18n_parent',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0)
				),
				'foreign_table' => 'sys_file_reference',
				'foreign_table_where' => 'AND sys_file_reference.uid=###REC_FIELD_l10n_parent### AND sys_file_reference.sys_language_uid IN (-1,0)'
			)
		),
		'l10n_diffsource' => array(
			'exclude' => 0,
			'config' => array(
				'type' => 'passthrough'
			)
		),
		'hidden' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.hidden',
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),
		'uid_local' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.uid_local',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'db',
				'size' => 1,
				'maxitems' => 1,
				'minitems' => 0,
				'allowed' => 'sys_file'
			)
		),
		'uid_foreign' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.uid_foreign',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0)
				),
				'foreign_table' => 'tt_content',
				'foreign_table_where' => 'ORDER BY tt_content.uid',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1
			)
		),
		'tablenames' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.tablenames',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'trim'
			)
		),
		'fieldname' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.fieldname',
			'config' => array(
				'type' => 'input',
				'size' => '30'
			)
		),
		'sorting_foreign' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.sorting_foreign',
			'config' => array(
				'type' => 'input',
				'size' => '4',
				'max' => '4',
				'eval' => 'int',
				'checkbox' => '0',
				'range' => array(
					'upper' => '1000',
					'lower' => '10'
				),
				'default' => 0
			)
		),
		'table_local' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.table_local',
			'config' => array(
				'type' => 'input',
				'size' => '20',
				'default' => 'sys_file'
			)
		),
		'title' => array(
			'l10n_mode' => 'mergeIfNotBlank',
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.title',
			'config' => array(
				'type' => 'input',
				'eval' => 'null',
				'size' => '20',
				'placeholder' => '__row|uid_local|title',
			)
		),
		'link' => array(
			'l10n_mode' => 'mergeIfNotBlank',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.link',
			'config' => array(
				'type' => 'input',
				'size' => '20',
				'wizards' => array(
					'_PADDING' => 2,
					'link' => array(
						'type' => 'popup',
						'title' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.link',
						'icon' => 'link_popup.gif',
						'script' => 'browse_links.php?mode=wizard',
						'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1'
					)
				)
			)
		),
		'description' => array(
			// This is used for captions in the frontend
			'l10n_mode' => 'mergeIfNotBlank',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.description',
			'config' => array(
				'type' => 'text',
				'eval' => 'null',
				'cols' => '20',
				'rows' => '5',
				'placeholder' => '__row|uid_local|description',
			)
		),
		'alternative' => array(
			'l10n_mode' => 'mergeIfNotBlank',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.alternative',
			'config' => array(
				'type' => 'input',
				'eval' => 'null',
				'size' => '20',
				'placeholder' => '__row|uid_local|alternative',
			),
		),
	),
	'types' => array(
		// Note that at the moment we define the same fields for every media type.
		// We leave the extensive definition of each type here anyway, to make clear that you can use it to differentiate between the types.
		'0' => array(
			'showitem' => '
				--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.basicoverlayPalette;basicoverlayPalette,
				--palette--;;filePalette'
		),
		\TYPO3\CMS\Core\Resource\File::FILETYPE_TEXT => array(
			'showitem' => '
				--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.basicoverlayPalette;basicoverlayPalette,
				--palette--;;filePalette'
		),
		\TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE => array(
			'showitem' => '
				--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.basicoverlayPalette;basicoverlayPalette,
				--palette--;;filePalette'
		),
		\TYPO3\CMS\Core\Resource\File::FILETYPE_AUDIO => array(
			'showitem' => '
				--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.basicoverlayPalette;basicoverlayPalette,
				--palette--;;filePalette'
		),
		\TYPO3\CMS\Core\Resource\File::FILETYPE_VIDEO => array(
			'showitem' => '
				--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.basicoverlayPalette;basicoverlayPalette,
				--palette--;;filePalette'
		),
		\TYPO3\CMS\Core\Resource\File::FILETYPE_APPLICATION => array(
			'showitem' => '
				--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.basicoverlayPalette;basicoverlayPalette,
				--palette--;;filePalette'
		)
	),
	'palettes' => array(
		// Used for basic overlays: having a file list etc
		'basicoverlayPalette' => array(
			'showitem' => 'title,description',
			'canNotCollapse' => TRUE
		),
		// Used for everything that is an image (because it has a link and a alternative text)
		'imageoverlayPalette' => array(
			'showitem' => '
				title,alternative;;;;3-3-3,--linebreak--,
				link,description
				',
			'canNotCollapse' => TRUE
		),
		// File palette, hidden but needs to be included all the time
		'filePalette' => array(
			'showitem' => 'uid_local, hidden, sys_language_uid, l10n_parent',
			'isHiddenPalette' => TRUE
		)
	)
);
?>

<?php
return array(
	'ctrl' => array(
		'title' => 'LLL:EXT:lang/locallang_tca.xlf:sys_category',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'delete' => 'deleted',
		'sortby' => 'sorting',
		'dividers2tabs' => TRUE,
		'versioningWS' => 2,
		'rootLevel' => -1,
		'versioning_followPages' => TRUE,
		'origUid' => 't3_origuid',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l10n_parent',
		'transOrigDiffSourceField' => 'l10n_diffsource',
		'searchFields' => 'title,description',
		'enablecolumns' => array(
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime'
		),
		'typeicon_classes' => array(
			'default' => 'mimetypes-x-sys_category'
		),
		'security' => array(
			'ignoreRootLevelRestriction' => TRUE,
		)
	),
	'interface' => array(
		'showRecordFieldList' => 'title,description'
	),
	'types' => array(
		'1' => array('showitem' => 'title;;1, parent,description,--div--;LLL:EXT:lang/locallang_tca.xlf:sys_category.tabs.items,items,--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,starttime, endtime')
	),
	'palettes' => array(
		'1' => array('showitem' => 'sys_language_uid, l10n_parent, hidden')
	),
	'columns' => array(
		't3ver_label' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.versionLabel',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'max' => '30'
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
					array('LLL:EXT:lang/locallang_general.xlf:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.xlf:LGL.default_value', 0)
				)
			)
		),
		'l10n_parent' => array(
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.l18n_parent',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0)
				),
				'foreign_table' => 'sys_category',
				'foreign_table_where' => 'AND sys_category.uid=###REC_FIELD_l10n_parent### AND sys_category.sys_language_uid IN (-1,0)'
			)
		),
		'l10n_diffsource' => array(
			'config' => array(
				'type' => 'passthrough'
			)
		),
		'hidden' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.hidden',
			'config' => array(
				'type' => 'check'
			)
		),
		'starttime' => array(
			'exclude' => 1,
			'l10n_mode' => 'mergeIfNotBlank',
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.starttime',
			'config' => array(
				'type' => 'input',
				'size' => '10',
				'max' => '20',
				'eval' => 'datetime',
				'checkbox' => '0',
				'default' => '0'
			)
		),
		'endtime' => array(
			'exclude' => 1,
			'l10n_mode' => 'mergeIfNotBlank',
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.endtime',
			'config' => array(
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'datetime',
				'checkbox' => '0',
				'default' => '0',
				'range' => array(
					'upper' => mktime(0, 0, 0, 1, 1, 2038),
				)
			)
		),
		'title' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_category.title',
			'config' => array(
				'type' => 'input',
				'width' => '200',
				'eval' => 'trim,required'
			)
		),
		'description' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_category.description',
			'config' => array(
				'type' => 'text'
			)
		),
		'parent' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_category.parent',
			'config' => array(
				'minitems' => 0,
				'maxitems' => 1,
				'type' => 'select',
				'renderMode' => 'tree',
				'foreign_table' => 'sys_category',
				'foreign_table_where' => ' AND sys_category.sys_language_uid IN (-1,0) ORDER BY sys_category.sorting ASC',
				'treeConfig' => array(
					'parentField' => 'parent',
					'appearance' => array(
						'expandAll' => TRUE,
						'showHeader' => TRUE,
						'maxLevels' => 99,
					),
				)
			)
		),
		'items' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_category.items',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => '*',
				'MM' => 'sys_category_record_mm',
				'MM_oppositeUsage' => array(),
				'size' => 10,
				'show_thumbs' => FALSE
			)
		)
	)
);

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
	),
	'interface' => array(
		'showRecordFieldList' => 'sys_language_uid, l18n_parent, l18n_diffsource, hidden, parentid, parenttable, input_1, inline_1',
	),
	'types' => array(
		'0' => array(
			'showitem' => '
				--div--;General, input_1, inline_1,
				--div--;Visibility, sys_language_uid, l18n_parent, l18n_diffsource, hidden, parentid, parenttable
			',
		),
	),
);
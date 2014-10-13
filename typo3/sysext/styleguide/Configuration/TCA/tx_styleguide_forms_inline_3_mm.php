<?php
return array(
	'ctrl' => array(
		'title'	=> 'Form engine tests - inline_3 mm',
		'label' => 'select_child',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('styleguide') . 'Resources/Public/Icons/tx_styleguide_forms.png',

		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
		),

		'dividers2tabs' => TRUE,
	),

	'columns' => array(
		'select_parent' => array(
			'label' => 'select parent',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'tx_styleguide_forms',
				'size' => 1,
				'minitems' => 1,
				'maxitems' => 1,
			),
		),
		'select_child' => array(
			'label' => 'select child',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'tx_styleguide_forms_inline_3_child',
				'size' => 1,
				'minitems' => 1,
				'maxitems' => 1,
			),
		),
	),

	'interface' => array(
		'showRecordFieldList' => 'select_parent, select_child',
	),

	'types' => array(
		'1' => array(
			'showitem' => 'select_parent, select_child',
		),
	),

	'palettes' => array(
		'1' => array(
			'showitem' => '',
		),
	),
);
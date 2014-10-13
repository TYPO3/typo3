<?php
return array(
	'ctrl' => array(
		'title'	=> 'Form engine tests - inline_3 child',
		'label' => 'input_1',
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
		'input_1' => array(
			'label' => 'input_1',
			'config' => array(
				'type' => 'input',
				'size' => 30,
				'eval' => 'trim,required'
			),
		),
	),

	'interface' => array(
		'showRecordFieldList' => 'inpput_1',
	),

	'types' => array(
		'1' => array(
			'showitem' => 'input_1',
		),
	),

	'palettes' => array(
		'1' => array(
			'showitem' => '',
		),
	),
);

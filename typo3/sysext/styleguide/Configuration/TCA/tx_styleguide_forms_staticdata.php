<?php
return array(
	'ctrl' => array (
		'title' => 'Form engine tests - static data',
		'label' => 'value_1',
		'rootLevel' => 1,
		'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('styleguide') . 'Resources/Public/Icons/tx_styleguide_forms_staticdata.png',
	),

	'columns' => array(
		'value_1' => array(
			'label' => 'Value',
			'config' => array(
				'type' => 'input',
				'size' => 10,
			),
		),
	),

	'interface' => array(
		'showRecordFieldList' => 'value_1',
	),

	'types' => array(
		'0' => array(
			'showitem' => 'value_1',
		),
	),
);

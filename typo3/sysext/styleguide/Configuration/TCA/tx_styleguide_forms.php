<?php
return array(
	'ctrl' => array (
		'title' => 'Form engine tests',
		'label' => 'uid',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'delete' => 'deleted',
		'sortby' => 'sorting',
		'default_sortby' => 'ORDER BY crdate',
		'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('styleguide') . 'Resources/Public/Icons/tx_styleguide_forms.png',

		'versioningWS' => TRUE,
		'origUid' => 't3_origuid',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l10n_parent',
		'transOrigDiffSourceField' => 'l10n_diffsource',

		'enablecolumns' => array(
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
		),

		'dividers2tabs' => 1,
	),

	'interface' => array(
		'showRecordFieldList' => 'hidden,starttime,endtime'
			. ',input_1'
			,
	),

	'columns' => array(
		'hidden' => array (
			'config' => array(
				'type' => 'check',
				'items' => array(
					'1' => array(
						'0' => 'Disable'
					),
				),
			),
		),
		'starttime' => array(
			'label' => 'Publish Date',
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
			'label' => 'Expiration Date',
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

		'input_1' => array(
			"label" => "Tests size: Is set to 30",
			"config" => array(
				"type" => "input",
				"max" => "30",
			),
		),


	),

	'types' => array(
		'0' => array(
			'showitem' => '
				input_1,
				--div--;Access,
					--palette--;Visibility;visibility,
					--palette--;Access;access
			',
		),
	),

	'palettes' => array(
		'visibility' => array(
			'showitem' => 'hidden;Shown in frontend',
			'canNotCollapse' => 1
		),
		'access' => array(
			'showitem' => 'starttime;Publish Date, endtime;Expiration Date',
			'canNotCollapse' => 1
		),
	),

);

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
		'showRecordFieldList' => 'hidden,starttime,endtime,
			input_1, input_2, input_3, input_4, input_5, input_6, input_7, input_8, input_9, input_10,
			input_11, input_12, input_13, input_14, input_15, input_16, input_17, input_18, input_19, input_20,
			input_21, input_22, input_23, input_24, input_25, input_26, input_27, input_28, input_29,
			',
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
			'label' => '1 Size is set to 10',
			'config' => array(
				'type' => 'input',
				'size' => 10,
			),
		),
		'input_2' => array(
			'label' => '2 Max is set to 4',
			'config' => array(
				'type' => 'input',
				'max' => '4',
			),
		),
		'input_3' => array(
			'label' => '3 Default value',
			'config' => array(
				'type' => 'input',
				'default' => 'Default value',
			),
		),
		'input_4' => array(
			'label' => '4 Eval alpha',
			'config' => array(
				'type' => 'input',
				'eval' => 'alpha',
			),
		),
		'input_5' => array(
			'label' => '5 Eval alphanum',
			'config' => array(
				'type' => 'input',
				'eval' => 'alphanum',
			),
		),
		'input_6' => array(
			'label' => '6 Eval date',
			'config' => array(
				'type' => 'input',
				'eval' => 'date',
			),
		),
		'input_7' => array(
			'label' => '7 Eval datetime',
			'config' => array(
				'type' => 'input',
				'eval' => 'datetime',
			),
		),
		'input_8' => array(
			'label' => '8 Eval double2',
			'config' => array(
				'type' => 'input',
				'eval' => 'double2',
			),
		),
		'input_9' => array(
			'label' => '9 Eval int',
			'config' => array(
				'type' => 'input',
				'eval' => 'int',
			),
		),
		'input_10' => array(
			'label' => '10 Eval is_in abc123',
			'config' => array(
				'type' => 'input',
				'eval' => 'is_in',
				'is_in' => 'abc123',
			),
		),
		'input_11' => array(
			'label' => '11 Eval lower',
			'config' => array(
				'type' => 'input',
				'eval' => 'lower',
			),
		),
		'input_12' => array(
			'label' => '12 Eval md5',
			'config' => array(
				'type' => 'input',
				'eval' => 'md5',
			),
		),
		'input_13' => array(
			'label' => '13 Eval nospace',
			'config' => array(
				'type' => 'input',
				'eval' => 'nospace',
			),
		),
		'input_14' => array(
			'label' => '14 Eval null',
			'config' => array(
				'type' => 'input',
				'eval' => 'null',
			),
		),
		'input_15' => array(
			'label' => '15 Eval num',
			'config' => array(
				'type' => 'input',
				'eval' => 'num',
			),
		),
		'input_16' => array(
			'label' => '16 Eval password',
			'config' => array(
				'type' => 'input',
				'eval' => 'password',
			),
		),
		'input_17' => array(
			'label' => '17 Eval required',
			'config' => array(
				'type' => 'input',
				'eval' => 'required',
			),
		),
		'input_18' => array(
			'label' => '18 Eval time',
			'config' => array(
				'type' => 'input',
				'eval' => 'time',
			),
		),
		'input_19' => array(
			'label' => '19 Eval timesec',
			'config' => array(
				'type' => 'input',
				'eval' => 'timesec',
			),
		),
		'input_20' => array(
			'label' => '20 Eval trim',
			'config' => array(
				'type' => 'input',
				'eval' => 'trim',
			),
		),
		/**
		 * @TODO
		'input_21' => array(
			'label' => 'Eval tx_*',
			'config' => array(
				'type' => 'input',
				'eval' => '',
			),
		),
		 */
		'input_22' => array(
			'label' => '22 Eval unique',
			'config' => array(
				'type' => 'input',
				'eval' => 'unique',
			),
		),
		'input_23' => array(
			'label' => '23 Eval uniqueInPid',
			'config' => array(
				'type' => 'input',
				'eval' => 'uniqueInPid',
			),
		),
		'input_24' => array(
			'label' => '24 Eval upper',
			'config' => array(
				'type' => 'input',
				'eval' => 'upper',
			),
		),
		'input_25' => array(
			'label' => '25 Eval year',
			'config' => array(
				'type' => 'input',
				'eval' => 'year',
			),
		),
		'input_26' => array(
			'label' => '26 Readonly datetime size 12',
			'config' => array(
				'type' => 'input',
				'readOnly' => '1',
				'size' => '12',
				'eval' => 'datetime',
				'default' => 0,
			),
		),
		'input_27' => array(
			'label' => '27 Eval int range 2 to 7',
			'config' => array(
				'type' => 'input',
				'eval' => 'int',
				'range' => array(
					'lower' => 2,
					'upper' => 7,
				),
			),
		),
		'input_28' => array(
			'label' => '28 Placeholder value from input_1',
			'config' => array(
				'type' => 'input',
				'placeholder' => '__row|input_1',
			),
		),
		'input_29' => array(
			'label' => '29 Placeholder value from input_1 with mode',
			'config' => array(
				'type' => 'input',
				'placeholder' => '__row|input_1',
				'eval' => 'null',
				'mode' => 'useOrOverridePlaceholder',
			),
		),
	),

	'types' => array(
		'0' => array(
			'showitem' => '
				--div--;Input,
					input_1, input_28, input_29, input_2, input_3, input_4, input_5, input_6, input_7, input_8, input_9,
					input_27, input_10, input_11, input_12, input_13, input_14, input_15, input_16, input_17, input_18,
					input_19, input_20, input_21, input_22, input_23, input_24, input_25, input_26,
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

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
				'max' => 4,
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
			'label' => '4 eval alpha',
			'config' => array(
				'type' => 'input',
				'eval' => 'alpha',
			),
		),
		'input_5' => array(
			'label' => '5 eval alphanum',
			'config' => array(
				'type' => 'input',
				'eval' => 'alphanum',
			),
		),
		'input_6' => array(
			'label' => '6 eval date',
			'config' => array(
				'type' => 'input',
				'eval' => 'date',
			),
		),
		'input_7' => array(
			'label' => '7 eval datetime',
			'config' => array(
				'type' => 'input',
				'eval' => 'datetime',
			),
		),
		'input_8' => array(
			'label' => '8 eval double2',
			'config' => array(
				'type' => 'input',
				'eval' => 'double2',
			),
		),
		'input_9' => array(
			'label' => '9 eval int',
			'config' => array(
				'type' => 'input',
				'eval' => 'int',
			),
		),
		'input_10' => array(
			'label' => '10 eval is_in abc123',
			'config' => array(
				'type' => 'input',
				'eval' => 'is_in',
				'is_in' => 'abc123',
			),
		),
		'input_11' => array(
			'label' => '11 eval lower',
			'config' => array(
				'type' => 'input',
				'eval' => 'lower',
			),
		),
		'input_12' => array(
			'label' => '12 eval md5',
			'config' => array(
				'type' => 'input',
				'eval' => 'md5',
			),
		),
		'input_13' => array(
			'label' => '13 eval nospace',
			'config' => array(
				'type' => 'input',
				'eval' => 'nospace',
			),
		),
		'input_14' => array(
			'label' => '14 eval null',
			'config' => array(
				'type' => 'input',
				'eval' => 'null',
			),
		),
		'input_15' => array(
			'label' => '15 eval num',
			'config' => array(
				'type' => 'input',
				'eval' => 'num',
			),
		),
		'input_16' => array(
			'label' => '16 eval password',
			'config' => array(
				'type' => 'input',
				'eval' => 'password',
			),
		),
		'input_17' => array(
			'label' => '17 eval required',
			'config' => array(
				'type' => 'input',
				'eval' => 'required',
			),
		),
		'input_18' => array(
			'label' => '18 eval time',
			'config' => array(
				'type' => 'input',
				'eval' => 'time',
			),
		),
		'input_19' => array(
			'label' => '19 eval timesec',
			'config' => array(
				'type' => 'input',
				'eval' => 'timesec',
			),
		),
		'input_20' => array(
			'label' => '20 eval trim',
			'config' => array(
				'type' => 'input',
				'eval' => 'trim',
			),
		),
		/**
		 * @TODO Add evaluation with a userfunc
		'input_21' => array(
			'label' => 'eval tx_*',
			'config' => array(
				'type' => 'input',
				'eval' => '',
			),
		),
		 */
		'input_22' => array(
			'label' => '22 eval unique',
			'config' => array(
				'type' => 'input',
				'eval' => 'unique',
			),
		),
		'input_23' => array(
			'label' => '23 eval uniqueInPid',
			'config' => array(
				'type' => 'input',
				'eval' => 'uniqueInPid',
			),
		),
		'input_24' => array(
			'label' => '24 eval upper',
			'config' => array(
				'type' => 'input',
				'eval' => 'upper',
			),
		),
		'input_25' => array(
			'label' => '25 eval year',
			'config' => array(
				'type' => 'input',
				'eval' => 'year',
			),
		),
		'input_26' => array(
			'label' => '26 Readonly datetime size 12',
			'config' => array(
				'type' => 'input',
				'readOnly' => 1,
				'size' => 12,
				'eval' => 'datetime',
				'default' => 0,
			),
		),
		'input_27' => array(
			'label' => '27 eval int range 2 to 7',
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
			'label' => '29 Placeholder value from input_1 with mode useOrOverridePlaceholder',
			'config' => array(
				'type' => 'input',
				'placeholder' => '__row|input_1',
				'eval' => 'null',
				'mode' => 'useOrOverridePlaceholder',
			),
		),
		"input_30" => array(
			"label" => "30 Link wizard, no _PADDING",
			"config" => Array (
				"type" => "input",
				'wizards' => array(
					'link' => array(
						'type' => 'popup',
						'title' => 'Link',
						'icon' => 'link_popup.gif',
						'script' => 'browse_links.php?mode=wizard',
						'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1',
					),
				),
			),
		),
		"input_31" => array(
			"label" => "31 Color picker wizard, _PADDING 6",
			"config" => array(
				"type" => "input",
				'wizards' => array(
					'_PADDING' => 6,
					'colorpicker' => array(
						'type' => 'colorbox',
						'title' => 'Color picker',
						'icon' => 'link_popup.gif',
						'script' => 'wizard_colorpicker.php',
						'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1',
					),
				),
			),
		),


		'text_1' => array(
			'label' => '1 no cols, no rows',
			'config' => array(
				'type' => 'text',
			),
		),
		'text_2' => array(
			'label' => '2 cols=20',
			'config' => array(
				'type' => 'text',
				'cols' => 20,
			),
		),
		'text_3' => array(
			'label' => '3 rows=2',
			'config' => array(
				'type' => 'text',
				'rows' => 2,
			),
		),
		'text_4' => array(
			'label' => '4 cols=20, rows=2',
			'config' => array(
				'type' => 'text',
				'cols' => 20,
				'rows' => 2,
			),
		),
		'text_5' => array(
			'label' => '5 wrap=off with default',
			'config' => array(
				'type' => 'text',
				'wrap' => 'off',
				'default' => 'This textbox has wrap set to "off", so these long paragraphs should appear in one line: Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean non luctus elit. In sed nunc velit. Donec gravida eros sollicitudin ligula mollis id eleifend mauris laoreet. Donec turpis magna, pulvinar id pretium eu, blandit et nisi. Nulla facilisi. Vivamus pharetra orci sed nunc auctor condimentum. Aenean volutpat posuere scelerisque. Nullam sed dolor justo. Pellentesque id tellus nunc, id sodales diam. Sed rhoncus risus a enim lacinia tincidunt. Aliquam ut neque augue.',
			),
		),
		'text_6' => array(
			'label' => '6 wrap=virtual with default',
			'config' => array(
				'type' => 'text',
				'wrap' => 'virtual',
				'default' => 'This textbox has wrap set to "virtual", so these long paragraphs should appear in multiple lines (wrapped at the end of the textbox): Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean non luctus elit. In sed nunc velit. Donec gravida eros sollicitudin ligula mollis id eleifend mauris laoreet. Donec turpis magna, pulvinar id pretium eu, blandit et nisi. Nulla facilisi. Vivamus pharetra orci sed nunc auctor condimentum. Aenean volutpat posuere scelerisque. Nullam sed dolor justo. Pellentesque id tellus nunc, id sodales diam. Sed rhoncus risus a enim lacinia tincidunt. Aliquam ut neque augue.',
			),
		),
		'text_7' => array(
			'label' => '7 eval required',
			'config' => array(
				'type' => 'text',
				'eval' => 'required',
			),
		),
		'text_8' => array(
			'label' => '8 eval trim',
			'config' => array(
				'type' => 'text',
				'eval' => 'trim',
			),
		),
		/**
		 * @TODO: Add evaluation with a userfunc
		'text_9' => array(
			'label' => '9 eval userfunc',
			'config' => array(
				'type' => 'text',
				'eval' => 'trim',
				// 'is_in' => 'abc123',
			),
		),
		 */
		'text_10' => array(
			'label' => '10 readOnly',
			'config' => array(
				'type' => 'text',
				'readOnly' => 1,
			),
		),
		/**
		 * @TODO: This throws a warning
		'text_11' => array(
			'label' => '10 readOnly with format datetime',
			'config' => array(
				'type' => 'text',
				'readOnly' => 1,
				'format' => 'datetime',
			),
		),
		 */
		'text_12' => array(
			'label' => '12 placeholder value from input_1',
			'config' => array(
				'type' => 'text',
				'placeholder' => '__row|input_1',
			),
		),
		'text_13' => array(
			'label' => '13 placeholder value from input_1 with mode useOrOverridePlaceholder',
			'config' => array(
				'type' => 'text',
				'placeholder' => '__row|input_1',
				'eval' => 'null',
				'mode' => 'useOrOverridePlaceholder',
			),
		),
		/**
		 * @TODO: Add type text wizards
		 */


		'checkbox_1' => array(
			'label' => '1 Single',
			'config' => array(
				'type' => 'check',
			)
		),
		'checkbox_2' => array(
			'label' => '2 Single default=1',
			'config' => array(
				'type' => 'check',
				'default' => 1,
			)
		),
		'checkbox_3' => array(
			'label' => '3 One checkbox with label',
			'config' => array(
				'type' => 'check',
				'items' => array(
					array('foo', ''),
				),
			)
		),
		'checkbox_4' => array(
			'label' => '4 One checkbox with label, pre-selected',
			'config' => array(
				'type' => 'check',
				'items' => array(
					array('foo', ''),
				),
				'default' => 1
			)
		),
		'checkbox_5' => array(
			'label' => '5 Three checkboxes with labels',
			'config' => array(
				'type' => 'check',
				'items' => array(
					array('foo', ''),
					array('bar', ''),
					array('foobar', ''),
				),
			),
		),
		'checkbox_6' => array(
			'label' => '6 Four checkboxes with labels, 1 and 3 pre-selected',
			'config' => array(
				'type' => 'check',
				'items' => array(
					array('foo', ''),
					array(
						'foo and this here is very long text that maybe does not really fit into the form in one line. Ok let us add even more text to see how this looks like if wrapped. Is this enough now?',
						''
					),
					array('foobar', ''),
					array('foobar', ''),
				),
				'default' => 5,
			),
		),
		'checkbox_7' => array(
			'label' => '7 Seven checkboxes with labels, 4 cols',
			'config' => array(
				'type' => 'check',
				'items' => array(
					array('foo1', ''),
					array('foo2', ''),
					array('foo3', ''),
					array('foo4', ''),
					array('foo5', ''),
					array('foo6', ''),
					array('foo7', ''),
				),
				'cols' => '4',
			),
		),
		'checkbox_8' => array(
			'label' => '8 showIfRTE (?)',
			'config' => array(
				'type' => 'check',
				'items' => array(
					array('foo', ''),
				),
				'showIfRTE' => 1,
			),
		),
		/**
		 * @TODO Add a itemsProcFunc
		'checkbox_9' => array(
			'label' => '9 itemsProcFunc',
			'config' => array(
				'type' => 'check',
				'items' => array(
					array('foo', ''),
					array('bar', ''),
				),
				'itemsProcFunc' => '',
			),
		),
		 */
		'checkbox_10' => array(
			'label' => '10 eval maximumRecordsChecked = 1 - table wide',
			'config' => array(
				'type' => 'check',
				'eval' => 'maximumRecordsChecked',
				'validation' => array(
					'maximumRecordsChecked' => 1,
				),
			),
		),
		'checkbox_11' => array(
			'label' => '11 eval maximumRecordsCheckedInPid = 1 - for this PID',
			'config' => array(
				'type' => 'check',
				'eval' => 'maximumRecordsCheckedInPid',
				'validation' => array(
					'maximumRecordsCheckedInPid' => 1,
				),
			),
		),


		'radio_1' => array(
			'label' => '1 Three options',
			'config' => array(
				'type' => 'radio',
				'items' => array(
					array('foo', 1),
					array('bar', 2),
					array('foobar', 3),
				),
			),
		),
		'radio_2' => array(
			'label' => '2 Three options, second pre-selected',
			'config' => array(
				'type' => 'radio',
				'items' => array(
					array(
						'foo and this here is very long text that maybe does not really fit into the form in one line. Ok let us add even more text to see how this looks like if wrapped. Is this enough now?',
						1
					),
					array('bar', 2),
					array('foobar', 3),
				),
				'default' => 2,
			),
		),
		'radio_3' => array(
			'label' => '3 Lots of options',
			'config' => array(
				'type' => 'radio',
				'items' => array(
					array('foo1', 1),
					array('foo2', 2),
					array('foo3', 3),
					array('foo4', 4),
					array('foo5', 5),
					array('foo6', 6),
					array('foo7', 7),
					array('foo8', 8),
					array('foo9', 9),
					array('foo10', 10),
					array('foo11', 11),
					array('foo12', 12),
					array('foo13', 13),
					array('foo14', 14),
					array('foo15', 15),
					array('foo16', 16),
				),
			),
		),
		'radio_4' => array(
			'label' => '4 String values',
			'config' => array(
				'type' => 'radio',
				'items' => array(
					array('foo', 'foo'),
					array('bar', 'bar'),
				),
			),
		),
		/**
		 * @TODO Add a itemsProcFunc
		'radio_5' => array(
			'label' => '5 itemsProcFunc',
			'config' => array(
				'type' => 'radio',
				'items' => array(
					array('foo', ''),
					array('bar', ''),
				),
				'itemsProcFunc' => '',
			),
		),
		 */


	),

	'interface' => array(
		'showRecordFieldList' => 'hidden,starttime,endtime,
			input_1, input_2, input_3, input_4, input_5, input_6, input_7, input_8, input_9, input_10,
			input_11, input_12, input_13, input_14, input_15, input_16, input_17, input_18, input_19, input_20,
			input_21, input_22, input_23, input_24, input_25, input_26, input_27, input_28, input_29, input_30,
			input_31,
			text_1, text_2, text_3, text_4, text_5, text_6, text_7, text_8, text_9, text_10,
			text_11, text_12, text_13,
			checkbox_1, checkbox_2, checkbox_3, checkbox_4, checkbox_5, checkbox_6, checkbox_7, checkbox_8, checkbox_9, checkbox_10,
			checkbox_11,
			radio_1, radio_2, radio_3, radio_4, radio_5,
			',
	),

	'types' => array(
		'0' => array(
			'showitem' => '
				--div--;Input,
					input_1, input_28, input_29, input_2, input_3, input_4, input_5, input_6, input_7, input_8, input_9,
					input_27, input_10, input_11, input_12, input_13, input_14, input_15, input_16, input_17, input_18,
					input_19, input_20, input_21, input_22, input_23, input_24, input_25, input_26, input_30, input_31,
				--div--;Text,
					text_1, text_2, text_3, text_4, text_5, text_6, text_7, text_8, text_9,
					text_10, text_11, text_12, text_13,
				--div--;Check,
					checkbox_1, checkbox_2, checkbox_3, checkbox_4, checkbox_5, checkbox_6, checkbox_7, checkbox_8, checkbox_9,
					checkbox_10, checkbox_11,
				--div--;Radio,
					radio_1, radio_2, radio_3, radio_4, radio_5,
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

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
		'input_21' => array(
			'label' => '21 eval with user function',
			'config' => array(
				'type' => 'input',
				'eval' => 'TYPO3\\CMS\\Styleguide\\UserFunctions\\TypeInput21Eval',
			),
		),
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
		'input_30' => array(
			'label' => '30 Link wizard, no _PADDING',
			'config' => array(
				'type' => 'input',
				'wizards' => array(
					'link' => array(
						'type' => 'popup',
						'title' => 'Link',
						'icon' => 'link_popup.gif',
						'module' => array(
							'name' => 'wizard_element_browser',
							'urlParameters' => array(
								'mode' => 'wizard',
							),
						),
						'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1',
					),
				),
			),
		),
		'input_31' => array(
			'label' => '31 Color picker wizard, _PADDING 6',
			'config' => array(
				'type' => 'input',
				'wizards' => array(
					'_PADDING' => 6,
					'colorpicker' => array(
						'type' => 'colorbox',
						'title' => 'Color picker',
						'icon' => 'link_popup.gif',
						'module' => array(
							'name' => 'wizard_colorpicker',
						),
						'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1',
					),
				),
			),
		),
		'input_32' => array(
			'label' => '32 Slider wizard, step=10, width=200, eval=trim,int',
			'config' => array(
				'type' => 'input',
				'size' => 5,
				'eval' => 'trim,int',
				'range' => array(
					'lower' => -90,
					'upper' => 90,
				),
				'default' => 0,
				'wizards' => array(
					'angle' => array(
						'type' => 'slider',
						'step' => 10,
						'width' => 200,
					),
				),
			),
		),
		'input_33' => array(
			'label' => '33 userFunc wizard',
			'config' => array(
				'type' => 'input',
				'size' => 10,
				'eval' => 'int',
				'wizards' => array(
					'userFuncInputWizard' => array(
						'type' => 'userFunc',
						'userFunc' => 'TYPO3\\CMS\\Styleguide\\UserFunctions\\WizardInput33->render',
						'params' => array(
							'color' => 'green',
						),
					),
				),
			),
		),
		'input_34' => array(
			'label' => '34 select wizard',
			'config' => array(
				'type' => 'input',
				'size' => 20,
				'eval' => 'trim',
				'wizards' => array(
					'season_picker' => array(
						'type' => 'select',
						'mode' => '',
						'items' => array(
							array('spring', 'Spring'),
							array('summer', 'Summer'),
							array('autumn', 'Autumn'),
							array('winter', 'Winter'),
						),
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
		'text_14' => array(
			'label' => '14 fixed font & tabs enabled',
			'config' => array(
				'type' => 'text',
			),
			'defaultExtras' => 'fixed-font : enable-tab'
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
						'foo and this here is very long text that maybe does not really fit into the form in one line. Ok let us add even more text to see how this looks like if wrapped. Is this enough now? No? Then let us add some even more useless text here!',
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
						'foo and this here is very long text that maybe does not really fit into the form in one line. Ok let us add even more text to see how this looks like if wrapped. Is this enough now? No? Then let us add some even more useless text here!',
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
		'radio_6' => array(
			'label' => '6 readonly',
			'config' => array(
				'type' => 'radio',
				'readOnly' => 1,
				'items' => array(
					array('foo', 'foo'),
					array('bar', 'bar'),
				),
			),
		),


		'select_1' => array(
			'label' => '1 Two items, one with really long text',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('foo and this here is very long text that maybe does not really fit into the form in one line. Ok let us add even more text to see how this looks like if wrapped. Is this enough now? No? Then let us add some even more useless text here!', 1),
					array('bar', 'bar'),
				),
			),
		),
		'select_2' => array(
			'label' => '2 itemsProcFunc',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('foo', 1),
					array('bar', 'bar'),
				),
				'itemsProcFunc' => 'TYPO3\\CMS\\Styleguide\\UserFunctions\\TypeSelect2->itemsProcFunc',
			),
		),
		'select_3' => array(
			'label' => '3 Three items, second pre-selected, size=2',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('foo1', 1),
					array('foo2', 2),
					array('foo3', 4),
				),
				'default' => 2,
			),
		),
		'select_4' => array(
			'label' => '4 Static values, dividers, merged with entries from staticdata table containing word "foo"',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('Static values', '--div--'),
					array('static -2', -2),
					array('static -1', -1),
					array('DB values', '--div--'),
				),
				'foreign_table' => 'tx_styleguide_forms_staticdata',
				'foreign_table_where' => 'AND tx_styleguide_forms_staticdata.value_1 LIKE \'%foo%\' ORDER BY uid',
				'rootLevel' => 1, // @TODO: docu of rootLevel says, foreign_table_where is *ignored*, which is NOT true.
				'foreign_table_prefix' => 'A prefix: ',
			),
		),
		'select_5' => array(
			'label' => '5 Items with icons',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('Icon using EXT:', 'foo', 'EXT:styleguide/Resources/Public/Icons/tx_styleguide_forms.png'),
					array('Icon from typo3/gfx', 'es', 'flags/es.gif'), // @TODO: docu says typo3/sysext/t3skin/icons/gfx/, but in fact it is typo3/gfx.
				),
			),
		),
		'select_6' => array(
			'label' => '6 Items with icons, iconsInOptionTags',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('Icon using EXT:', 'foo', 'EXT:styleguide/Resources/Public/Icons/tx_styleguide_forms.png'),
					array('Icon from typo3/gfx', 'es', 'flags/es.gif'),
				),
				'iconsInOptionTags' => TRUE,
			),
		),
		'select_7' => array(
			'label' => '7 Items with icons, iconsInOptionTags, noIconsBelowSelect',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('Icon using EXT:', 'foo', 'EXT:styleguide/Resources/Public/Icons/tx_styleguide_forms.png'),
					array('Icon from typo3/gfx', 'es', 'flags/es.gif'),
				),
				'iconsInOptionTags' => TRUE,
				'noIconsBelowSelect' => TRUE,
			),
		),
		'select_8' => array(
			'label' => '8 Items with icons, selicon_cols set to 3',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('foo1', 'es', 'flags/es.gif'),
					array('foo2', 'fr', 'flags/fr.gif'),
					array('foo3', 'de', 'flags/de.gif'),
					array('foo4', 'us', 'flags/us.gif'),
					array('foo5', 'gr', 'flags/gr.gif'),
				),
				'selicon_cols' => 3,
			),
		),
		'select_9' => array(
			'label' => '9 fileFolder Icons from EXT:styleguide/Resources/Public/Icons and a dummy first entry, iconsInOptionTags, two columns',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0),
				),
				'fileFolder' => 'EXT:styleguide/Resources/Public/Icons',
				'fileFolder_extList' => 'png',
				'fileFolder_recursions' => 1,
				'iconsInOptionTags' => TRUE,
				'selicon_cols' => 2,
			),
		),
		'select_10' => array(
			'label' => '10 three options, size=6',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('foo1', 1),
					array('foo2', 2),
					array('a divider', '--div--'),
					array('foo3', 3),
				),
				'size' => 6,
			),
		),
		'select_11' => array(
			'label' => '11 two options, size=2',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('foo1', 1),
					array('foo2', 2),
				),
				'size' => 2,
			),
		),
		'select_12' => array(
			'label' => '12 multiple, maxitems=5, minitems=2, autoSizeMax=4, size=3',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('foo1', 1),
					array('foo2', 2),
					array('a divider', '--div--'),
					array('foo3', 3),
					array('foo4', 4),
					array('foo5', 5),
					array('foo6', 6),
				),
				'size' => 3,
				'autoSizeMax' => 5,
				'maxitems' => 5,
				'minitems' => 2,
				'multiple' => TRUE, // @TODO: multiple does not seem to have any effect at all? Can be commented without change.
			),
		),
		'select_13' => array(
			'label' => '13 multiple, exclusiveKeys for 1 and 2',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('exclusive', '--div--'),
					array('foo 1', 1),
					array('foo 2', 2),
					array('multiple', '--div--'),
					array('foo 3', 3),
					array('foo 4', 4),
					array('foo 5', 5),
					array('foo 6', 6),
				),
				'multiple' => TRUE, // @TODO: multiple does not seem to have any effect at all?! Can be commented without change.
				'size' => 5,
				'maxitems' => 20,
				'exclusiveKeys' => '1,2',
			),
		),
		'select_14' => array(
			'label' => '14 special tables, 12 icons in a row',
			'config' => array(
				'type' => 'select',
				'special' => 'tables',
				'selicon_cols' => 15,
				'iconsInOptionTags' => TRUE, // @TODO: Has no effect - intended?
			),
		),
		'select_15' => array(
			'label' => '15 special=tables, suppress_icons=1',
			'config' => array(
				'type' => 'select',
				'special' => 'tables',
				'suppress_icons' => '1',
			),
		),
		'select_16' => array(
			'label' => '16 special=pagetypes',
			'config' => array(
				'type' => 'select',
				'special' => 'pagetypes',
			),
		),
		'select_17' => array(
			'label' => '17 special=exclude',
			'config' => array(
				'type' => 'select',
				'special' => 'exclude',
				'size' => 10,
			),
		),
		'select_18' => array(
			'label' => '18 special=modListGroup',
			'config' => array(
				'type' => 'select',
				'special' => 'modListGroup',
			),
		),
		'select_19' => array(
			'label' => '19 special=modListUser',
			'config' => array(
				'type' => 'select',
				'special' => 'modListUser',
			),
		),
		'select_20' => array(
			'label' => '20 special=languages',
			'config' => array(
				'type' => 'select',
				'special' => 'languages',
				'size' => 5,
			),
		),
		'select_21' => array(
			'label' => '21 itemListStyle: green, 250 width and selectedListStyle: red, width 350',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('foo 1', 1),
					array('foo 2', 2),
					array('foo 3', 3),
				),
				'itemListStyle' => 'width:250px;background-color:#ffcccc;',
				'selectedListStyle' => 'width:250px;background-color:#ccffcc;',
				'size' => 2,
				'maxitems' => 2,
			),
		),
		'select_22' => array(
			'label' => '22 renderMode=checkbox',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('item 1', 1),
					array('item 2', 2),
					array('item 3', 3),
				),
				'renderMode' => 'checkbox',
				'maxitems' => 2,
			),
		),
		'select_23' => array(
			'label' => '23 renderMode=checkbox with icons and description',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('foo 1', 1, '', 'optional description'), // @TODO: In contrast to "items" documentation, description seems not to have an effect for renderMode=checkbox
					array('foo 2', 2, 'EXT:styleguide/Resources/Public/Icons/tx_styleguide_forms.png', 'other description'),
					array('foo 3', 3, '', ''),
				),
				'renderMode' => 'checkbox',
				'maxitems' => 2,
			),
		),
		'select_24' => array(
			'label' => '24 renderMode=singlebox',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('foo 1', 1),
					array('foo 2', 2),
					array('foo 3', 4),
				),
				'renderMode' => 'singlebox',
				'maxitems' => 2,
			),
		),
		'select_25' => array(
			'label' => '25 renderMode=tree of pages',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'pages',
				'size' => 20,
				'maxitems' => 4, // @TODO: *must* be set, otherwise invalid upon checking first item?!
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
		'select_26' => array(
			'label' => '26 renderMode=tree of pages showHeader=FALSE, nonSelectableLevels=0,1, allowRecursiveMode=TRUE, width=400',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'pages',
				'maxitems' => 4, // @TODO: *must* be set, otherwise invalid upon checking first item?!
				'size' => 10,
				'renderMode' => 'tree',
				'treeConfig' => array(
					'expandAll' => true,
					'parentField' => 'pid',
					'appearance' => array(
						'showHeader' => FALSE,
						'nonSelectableLevels' => '0,1',
						'allowRecursiveMode' => TRUE, // @TODO: No effect?
						'width' => 400,
					),
				),
			),
		),
		'select_27' => array(
			'label' => '27 enableMultiSelectFilterTextfield',
			'config' => array(
			'type' => 'select',
				'items' => array(
					array('foo 1', 1),
					array('foo 2', 2),
					array('foo 3', 4),
					array('bar', 4),
				),
				'size' => 5,
				'minitems' => 0,
				'maxitems' => 999,
				'enableMultiSelectFilterTextfield' => TRUE,
			),
		),
		'select_28' => array(
			'label' => '27 enableMultiSelectFilterTextfield, multiSelectFilterItems',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('foo 1', 1),
					array('foo 2', 2),
					array('foo 3', 4),
					array('bar', 4),
				),
				'size' => 5,
				'minitems' => 0,
				'maxitems' => 999,
				'enableMultiSelectFilterTextfield' => TRUE,
				'multiSelectFilterItems' => array(
					array('', ''),
					array('foo', 'foo'),
					array('bar', 'bar'),
				),
			),
		),
		'select_29' => array(
			'label' => '27 wizards',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'tx_styleguide_forms_staticdata',
				'rootLevel' => 1,
				'size' => 5,
				'autoSizeMax' => 20,
				'minitems' => 0,
				'maxitems' => 999,
				'wizards' => array(
					'_PADDING' => 1, // @TODO: Has no sane effect
					'_VERTICAL' => 1,
					'edit' => array(
						'type' => 'popup',
						'title' => 'edit',
						'module' => array( // @TODO: TCA documentation is not up to date at least in "Adding wizards" section of type=select here
							'name' => 'wizard_edit',
						),
						'icon' => 'edit2.gif',
						'popup_onlyOpenIfSelected' => 1,
						'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
					),
					'add' => array(
						'type' => 'script',
						'title' => 'add',
						'icon' => 'add.gif',
						'module' => array(
							'name' => 'wizard_add',
						),
						'params' => array(
							'table' => 'tx_styleguide_forms_staticdata',
							'pid' => '0',
							'setValue' => 'prepend',
						),
					),
					'list' => array(
						'type' => 'script',
						'title' => 'list',
						'icon' => 'list.gif',
						'module' => array(
							'name' => 'wizard_list',
						),
						'params' => array(
							'table' => 'tx_styleguide_forms_staticdata',
							'pid' => '0',
						),
					),
				),
			),
		),


		'group_1' => array(
			'label' => '1 internal_type=db, two tables allowed',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'be_users,be_groups',
			),
		),
		'group_2' => array(
			'label' => '2 internal_type=db, two tables allowed, show thumbs',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'be_users,be_groups',
				'show_thumbs' => TRUE,
			),
		),
		'group_3' => array(
			'label' => '3 internal_type=db, suggest wizard, disable_controls=browser',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tx_styleguide_forms_staticdata',
				'disable_controls' => 'browser',
				'wizards' => array(
					'suggest' => array(
						'type' => 'suggest',
					),
				),
			),
		),
		'group_4' => array(
			'label' => '4 internal_type=db, show_thumbs, maxitems=1, size=1, suggest wizard',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'tx_styleguide_forms_staticdata',
				'show_thumbs' => TRUE,
				'size' => 1,
				'maxitems' => 1,
				'wizards' => array(
					'suggest' => array(
						'type' => 'suggest',
					),
				),
			),
		),
		'group_5' => array(
			'label' => '5 internal_type=file, lots of file types allowed, show thumbs',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => 'jpg, jpeg, png, gif',
				'show_thumbs' => TRUE,
				'size' => 3,
				'uploadfolder' => 'uploads/pics/',
				'disable_controls' => 'upload', // @TODO: Documented feature has no effect since upload field in form is not shown anymore (since fal?)
				'max_size' => 2000,
			),
		),
		'group_6' => array(
			'label' => '6 internal_type=file, delete control disabled, no thumbs',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => 'jpg',
				'size' => 3,
				'uploadfolder' => 'uploads/pics/',
				'disable_controls' => 'delete',
			),
		),
		'group_7' => array(
			'label' => '7 internal_type=file, size=1',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => 'jpg',
				'size' => 1,
				'uploadfolder' => 'uploads/pics/',
			),
		),
		'group_8' => array(
			'label' => '8 internal_type=file, selectedListStyles set',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => 'jpg',
				'uploadfolder' => 'uploads/pics/',
				'selectedListStyle' => 'width:400px;background-color:#ccffcc;',
			),
		),
		'group_9' => array(
			'label' => '9 internal_type=file, maxitems=2',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => 'jpg',
				'uploadfolder' => 'uploads/pics/',
				'maxitems' => 2, // @TODO: Warning sign is missing if too many entries are added
			),
		),
		'group_10' => array(
			'label' => '10 internal_type=file, maxitems=2',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => 'jpg',
				'uploadfolder' => 'uploads/pics/',
				'maxitems' => 2, // @TODO: Warning sign is missing if too many entries are added
			),
		),
		'group_11' => array(
			'label' => '11 group FAL field',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'sys_file',
				'MM' => 'sys_file_reference',
				'MM_match_fields' => array(
					'fieldname' => 'image_fal_group',
				),
				'prepend_tname' => TRUE,
				'appearance' => array(
					'elementBrowserAllowed' => 'jpg, png, gif',
					'elementBrowserType' => 'file',
				),
				'max_size' => 2000,
				'show_thumbs' => TRUE,
				'size' => '3',
				'maxitems' => 200,
				'minitems' => 0,
				'autoSizeMax' => 40,
			),
		),
		/**
		 * @TODO: Add some more wizards for group and select, especially play with _ parameters like _POSITION and _VERTICAL
		 */


		'none_1' => array(
			'label' => '1 pass_content=1',
			'config' => array(
				'type' => 'none',
				'pass_content' => 1,
			),
		),
		'none_2' => array(
			'label' => '2 pass_content=0',
			'config' => array(
				'type' => 'none',
				'pass_content' => 0,
			),
		),
		'none_3' => array(
			'label' => '3 rows=2',
			'config' => array(
				'type' => 'none',
				'rows' => 2,
			),
		),
		'none_4' => array(
			'label' => '4 cols=2',
			'config' => array(
				'type' => 'none',
				'cols' => 2,
			),
		),
		'none_5' => array(
			'label' => '5 rows=2, fixedRows=2',
			'config' => array(
				'type' => 'none',
				'rows' => 2,
				'fixedRows' => 2,
			),
		),
		'none_6' => array(
			'label' => '6 size=6',
			'config' => array(
				'type' => 'none',
				'size' => 6,
			),
		),
		/**
		 * @TODO: Add a default record to adt.sql to show all the format options of type=none
		 */


		'passthrough_1' => array(
			'label' => '1 this should NOT be shown',
			'config' => array(
				'type' => 'passthrough',
			),
		),


		'user_1' => array(
			'label' => '1 parameter color used as border color',
			'config' => array(
				'type' => 'user',
				'userFunc' => 'TYPO3\\CMS\\Styleguide\\UserFunctions\\TypeUser1->render',
				'parameters' => array(
					'color' => 'green',
				),
			),
		),
		'user_2' => array(
			'label' => '2 noTableWrapping',
			'config' => array(
				'type' => 'user',
				'userFunc' => 'TYPO3\\CMS\\Styleguide\\UserFunctions\\TypeUser2->render',
				'parameters' => array(
					'color' => 'green',
				),
				'noTableWrapping' => TRUE,
			),
		),


		'flex_1' => array(
			'label' => '1 simple flex form',
			'config' => array(
				'type' => 'flex',
				'ds' => array(
					'default' => '
						<T3DataStructure>
							<ROOT>
								<type>array</type>
								<el>
									<input_1>
										<TCEforms>
											<label>Some input field</label>
											<config>
												<type>input</type>
												<size>23</size>
											</config>
										</TCEforms>
									</input_1>
								</el>
							</ROOT>
						</T3DataStructure>
					',
				),
			),
		),
		'flex_2' => array(
			'label' => '2 simple flex form with langDisable=1',
			'config' => array(
				'type' => 'flex',
				'ds' => array(
					'default' => '
						<T3DataStructure>
							<meta>
								<langDisable>1</langDisable>
							</meta>
							<ROOT>
								<type>array</type>
								<el>
									<input_1>
										<TCEforms>
											<label>Some input field</label>
											<config>
												<type>input</type>
												<size>23</size>
											</config>
										</TCEforms>
									</input_1>
								</el>
							</ROOT>
						</T3DataStructure>
					',
				),
			),
		),
		'flex_3' => array(
			'label' => '3 complex flexform in an external file',
			'config' => array(
				'type' => 'flex',
				'ds' => array(
					'default' => 'FILE:EXT:styleguide/Configuration/Flexform/Flex_3.xml',
				),
			),
		),


		'inline_1' => array(
			'label' => '1 typical FAL field',
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
				'createNewRelationLinkTitle' => "LLL:EXT:cms/locallang_ttc.xlf:images.addFileReference",
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
		'inline_2' => array( /** Taken from irre_tutorial 1nff */
			'label' => '2 1:n foreign field to table with sheets with a custom text',
			'config' => array(
				'type' => 'inline',
				'foreign_table' => 'tx_styleguide_forms_inline_2_child1',
				'foreign_field' => 'parentid',
				'foreign_table_field' => 'parenttable',
				'maxitems' => 10,
				'appearance' => array(
					'showSynchronizationLink' => TRUE,
					'showAllLocalizationLink' => TRUE,
					'showPossibleLocalizationRecords' => TRUE,
					'showRemovedLocalizationRecords' => TRUE,
					'newRecordLinkTitle' => 'Create a new relation "inline_2"',
				),
				'behaviour' => array(
					'localizationMode' => 'select',
					'localizeChildrenAtParentLocalization' => TRUE,
				),
			),
		),
		'inline_3' => array(
			'exclude' => 1,
			'label' => '3 m:m async, useCombination, newRecordLinkAddTitle',
			'config' => array(
				'type' => 'inline',
				'foreign_table' => 'tx_styleguide_forms_inline_3_mm',
				'foreign_field' => 'select_parent',
				'foreign_selector' => 'select_child',
				'foreign_unique' => 'select_child',
				'maxitems' => 9999,
				'appearance' => array(
					'newRecordLinkAddTitle' => 1,
					'useCombination' => TRUE, /** @TODO: The "Create new relation" button throws a JS error */
					'collapseAll' => FALSE,
					'levelLinksPosition' => 'top',
					'showSynchronizationLink' => 1,
					'showPossibleLocalizationRecords' => 1,
					'showAllLocalizationLink' => 1,
				),
			),
		),


	),

	'interface' => array(
		'showRecordFieldList' => 'hidden,starttime,endtime,
			input_1, input_2, input_3, input_4, input_5, input_6, input_7, input_8, input_9, input_10,
			input_11, input_12, input_13, input_14, input_15, input_16, input_17, input_18, input_19, input_20,
			input_21, input_22, input_23, input_24, input_25, input_26, input_27, input_28, input_29, input_30,
			input_31, input_32, input_33, input_34,
			text_1, text_2, text_3, text_4, text_5, text_6, text_7, text_8, text_9, text_10,
			text_11, text_12, text_13,text_14,
			checkbox_1, checkbox_2, checkbox_3, checkbox_4, checkbox_5, checkbox_6, checkbox_7, checkbox_8, checkbox_9, checkbox_10,
			checkbox_11,
			radio_1, radio_2, radio_3, radio_4, radio_5, radio_6,
			select_1, select_2, select_3, select_4, select_5, select_6, select_7, select_8, select_9, select_10,
			select_11, select_12, select_13, select_14, select_15, select_16, select_17, select_18, select_19, select_20,
			select_21, select_22, select_23, select_24, select_25, select_26, select_27, select_28, select_29,
			group_1, group_2, group_3, group_4, group_5, group_6, group_7, group_8, group_9, group_10,
			group_11,
			none_1, none_2, none_3, none_4, none_5, none_6,
			passthrough_1,
			user_1, user_2,
			flex_1, flex_2, flex_3,
			inline_1, inline_2, inline_3,
			',
	),

	'types' => array(
		'0' => array(
			'showitem' => '
				--div--;Input,
					input_1, input_28, input_29, input_2, input_3, input_4, input_5, input_6, input_7, input_8, input_9,
					input_27, input_10, input_11, input_12, input_13, input_14, input_15, input_16, input_17, input_18,
					input_19, input_20, input_21, input_22, input_23, input_24, input_25, input_26, input_30, input_31,
					input_32, input_33, input_34,
				--div--;Text,
					text_1, text_2, text_3, text_4, text_5, text_6, text_7, text_8, text_9,
					text_10, text_11, text_12, text_13, text_14,
				--div--;Check,
					checkbox_1, checkbox_2, checkbox_3, checkbox_4, checkbox_5, checkbox_6, checkbox_7, checkbox_8, checkbox_9,
					checkbox_10, checkbox_11,
				--div--;Radio,
					radio_1, radio_2, radio_3, radio_4, radio_5, radio_6,
				--div--;Select,
					select_1, select_2, select_3, select_4, select_5, select_6, select_7, select_8, select_9, select_10,
					select_11, select_12, select_13, select_14, select_15, select_16, select_17, select_18, select_19, select_20,
					select_21, select_22, select_23, select_24, select_25, select_26, select_27, select_28, select_29,
				--div--;Group,
					group_1, group_2, group_3, group_4, group_5, group_6, group_7, group_8, group_9, group_10,
					group_11,
				--div--;Passthrough,
					passthrough_1,
				--div--;None,
					none_1, none_2, none_3, none_4, none_5, none_6,
				--div--;User,
					user_1, user_2,
				--div--;Flex,
					flex_1, flex_2, flex_3,
				--div--;Inline,
					inline_1, inline_2, inline_3,
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

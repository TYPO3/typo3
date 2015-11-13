<?php
return array(
    'ctrl' => array(
        'title' => 'Form engine tests - Top record',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'default_sortby' => 'ORDER BY crdate',
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide_forms.svg',

        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',

        'enablecolumns' => array(
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ),

        'type' => 'type_field',
    ),

    'columns' => array(
        'hidden' => array(
            'exclude' => 1,
            'config' => array(
                'type' => 'check',
                'items' => array(
                    '1' => array(
                        '0' => 'Disable',
                    ),
                ),
            ),
        ),
        'starttime' => array(
            'exclude' => 1,
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
            'exclude' => 1,
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


        'type_field' => array(
            'exclude' => 1,
            'label' => 'TYPE FIELD',
            'config' => array(
                'type' => 'select',
                'items' => array(
                    array('type standard', '0'),
                    array('type test', 'test'),
                ),
            ),
        ),


        'input_1' => array(
            'exclude' => 1,
            'label' => 'INPUT: 1 Size is set to 10',
            'config' => array(
                'type' => 'input',
                'size' => 10,
            ),
        ),
        'input_2' => array(
            'exclude' => 1,
            'label' => 'INPUT: 2 Max is set to 4',
            'config' => array(
                'type' => 'input',
                'max' => 4,
            ),
        ),
        'input_3' => array(
            'exclude' => 1,
            'label' => 'INPUT: 3 Default value',
            'config' => array(
                'type' => 'input',
                'default' => 'Default value',
            ),
        ),
        'input_4' => array(
            'exclude' => 1,
            'label' => 'INPUT: 4 eval alpha',
            'config' => array(
                'type' => 'input',
                'eval' => 'alpha',
            ),
        ),
        'input_5' => array(
            'exclude' => 1,
            'label' => 'INPUT: 5 eval alphanum',
            'config' => array(
                'type' => 'input',
                'eval' => 'alphanum',
            ),
        ),
        'input_6' => array(
            'exclude' => 1,
            'label' => 'INPUT: 6 eval date',
            'config' => array(
                'type' => 'input',
                'eval' => 'date',
            ),
        ),
        'input_7' => array(
            'exclude' => 1,
            'label' => 'INPUT: 7 eval datetime',
            'config' => array(
                'type' => 'input',
                'eval' => 'datetime',
            ),
        ),
        'input_8' => array(
            'exclude' => 1,
            'label' => 'INPUT: 8 eval double2',
            'config' => array(
                'type' => 'input',
                'eval' => 'double2',
            ),
        ),
        'input_9' => array(
            'exclude' => 1,
            'label' => 'INPUT: 9 eval int',
            'config' => array(
                'type' => 'input',
                'eval' => 'int',
            ),
        ),
        'input_10' => array(
            'exclude' => 1,
            'label' => 'INPUT: 10 eval is_in abc123',
            'config' => array(
                'type' => 'input',
                'eval' => 'is_in',
                'is_in' => 'abc123',
            ),
        ),
        'input_11' => array(
            'exclude' => 1,
            'label' => 'INPUT: 11 eval lower',
            'config' => array(
                'type' => 'input',
                'eval' => 'lower',
            ),
        ),
        'input_12' => array(
            'exclude' => 1,
            'label' => 'INPUT: 12 eval md5',
            'config' => array(
                'type' => 'input',
                'eval' => 'md5',
            ),
        ),
        'input_13' => array(
            'exclude' => 1,
            'label' => 'INPUT: 13 eval nospace',
            'config' => array(
                'type' => 'input',
                'eval' => 'nospace',
            ),
        ),
        'input_14' => array(
            'exclude' => 1,
            'label' => 'INPUT: 14 eval null',
            'config' => array(
                'type' => 'input',
                'eval' => 'null',
            ),
        ),
        'input_15' => array(
            'exclude' => 1,
            'label' => 'INPUT: 15 eval num',
            'config' => array(
                'type' => 'input',
                'eval' => 'num',
            ),
        ),
        'input_16' => array(
            'exclude' => 1,
            'label' => 'INPUT: 16 eval password',
            'config' => array(
                'type' => 'input',
                'eval' => 'password',
            ),
        ),
        'input_18' => array(
            'exclude' => 1,
            'label' => 'INPUT: 18 eval time',
            'config' => array(
                'type' => 'input',
                'eval' => 'time',
            ),
        ),
        'input_19' => array(
            'exclude' => 1,
            'label' => 'INPUT: 19 eval timesec',
            'config' => array(
                'type' => 'input',
                'eval' => 'timesec',
            ),
        ),
        'input_20' => array(
            'exclude' => 1,
            'label' => 'INPUT: 20 eval trim',
            'config' => array(
                'type' => 'input',
                'eval' => 'trim',
            ),
        ),
        'input_21' => array(
            'exclude' => 1,
            'label' => 'INPUT: 21 eval with user function',
            'config' => array(
                'type' => 'input',
                'eval' => 'TYPO3\\CMS\\Styleguide\\UserFunctions\\FormEngine\\TypeInput21Eval',
            ),
        ),
        'input_22' => array(
            'exclude' => 1,
            'label' => 'INPUT: 22 eval unique',
            'config' => array(
                'type' => 'input',
                'eval' => 'unique',
            ),
        ),
        'input_23' => array(
            'exclude' => 1,
            'label' => 'INPUT: 23 eval uniqueInPid',
            'config' => array(
                'type' => 'input',
                'eval' => 'uniqueInPid',
            ),
        ),
        'input_24' => array(
            'exclude' => 1,
            'label' => 'INPUT: 24 eval upper',
            'config' => array(
                'type' => 'input',
                'eval' => 'upper',
            ),
        ),
        'input_25' => array(
            'exclude' => 1,
            'label' => 'INPUT: 25 eval year',
            'config' => array(
                'type' => 'input',
                'eval' => 'year',
            ),
        ),
        'input_26' => array(
            'exclude' => 1,
            'label' => 'INPUT: 26 Readonly datetime size 12',
            'config' => array(
                'type' => 'input',
                'readOnly' => 1,
                'size' => 12,
                'eval' => 'datetime',
                'default' => 0,
            ),
        ),
        'input_27' => array(
            'exclude' => 1,
            'label' => 'INPUT: 27 eval int range 2 to 7',
            'config' => array(
                'type' => 'input',
                'eval' => 'int',
                'range' => array(
                    'lower' => 2,
                    'upper' => 7,
                ),
                'default' => 3,
            ),
        ),
        'input_28' => array(
            'exclude' => 1,
            'label' => 'INPUT: 28 Placeholder value from input_1',
            'config' => array(
                'type' => 'input',
                'placeholder' => '__row|input_1',
            ),
        ),
        'input_29' => array(
            'exclude' => 1,
            'label' => 'INPUT: 29 Placeholder value from input_1 with mode useOrOverridePlaceholder',
            'config' => array(
                'type' => 'input',
                'placeholder' => '__row|input_1',
                'eval' => 'null',
                'mode' => 'useOrOverridePlaceholder',
            ),
        ),
        'input_30' => array(
            'exclude' => 1,
            'label' => 'INPUT: 30 Link wizard, no _PADDING',
            'config' => array(
                'type' => 'input',
                'wizards' => array(
                    'link' => array(
                        'type' => 'popup',
                        'title' => 'Link',
                        'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_link.gif',
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
        'input_32' => array(
            'exclude' => 1,
            'label' => 'INPUT: 32 Slider wizard, step=10, width=200, eval=trim,int',
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
            'exclude' => 1,
            'label' => 'INPUT: 33 userFunc wizard',
            'config' => array(
                'type' => 'input',
                'size' => 10,
                'eval' => 'int',
                'wizards' => array(
                    'userFuncInputWizard' => array(
                        'type' => 'userFunc',
                        'userFunc' => 'TYPO3\\CMS\\Styleguide\\UserFunctions\\FormEngine\\WizardInput33->render',
                        'params' => array(
                            'color' => 'green',
                        ),
                    ),
                ),
            ),
        ),
        'input_34' => array(
            'exclude' => 1,
            'label' => 'INPUT: 34 select wizard',
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
        'input_36' => array(
            'exclude' => 1,
            'label' => 'INPUT: 36 Slider wizard, default=14.5, step=0.5, width=150, eval=trim,double2',
            'config' => array(
                'type' => 'input',
                'size' => 5,
                'eval' => 'trim,double2',
                'range' => array(
                    'lower' => -90,
                    'upper' => 90,
                ),
                'default' => 14.5,
                'wizards' => array(
                    'angle' => array(
                        'type' => 'slider',
                        'step' => 0.5,
                        'width' => 150,
                    ),
                ),
            ),
        ),

        'text_1' => array(
            'exclude' => 1,
            'label' => 'TEXT: 1 no cols, no rows',
            'config' => array(
                'type' => 'text',
            ),
        ),
        'text_2' => array(
            'exclude' => 1,
            'label' => 'TEXT: 2 cols=20',
            'config' => array(
                'type' => 'text',
                'cols' => 20,
            ),
        ),
        'text_3' => array(
            'exclude' => 1,
            'label' => 'TEXT: 3 rows=2',
            'config' => array(
                'type' => 'text',
                'rows' => 2,
            ),
        ),
        'text_4' => array(
            'exclude' => 1,
            'label' => 'TEXT: 4 cols=20, rows=2',
            'config' => array(
                'type' => 'text',
                'cols' => 20,
                'rows' => 2,
            ),
        ),
        'text_5' => array(
            'exclude' => 1,
            'label' => 'TEXT: 5 wrap=off with default',
            'config' => array(
                'type' => 'text',
                'wrap' => 'off',
                'default' => 'This textbox has wrap set to "off", so these long paragraphs should appear in one line: Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean non luctus elit. In sed nunc velit. Donec gravida eros sollicitudin ligula mollis id eleifend mauris laoreet. Donec turpis magna, pulvinar id pretium eu, blandit et nisi. Nulla facilisi. Vivamus pharetra orci sed nunc auctor condimentum. Aenean volutpat posuere scelerisque. Nullam sed dolor justo. Pellentesque id tellus nunc, id sodales diam. Sed rhoncus risus a enim lacinia tincidunt. Aliquam ut neque augue.',
            ),
        ),
        'text_6' => array(
            'exclude' => 1,
            'label' => 'TEXT: 6 wrap=virtual with default',
            'config' => array(
                'type' => 'text',
                'wrap' => 'virtual',
                'default' => 'This textbox has wrap set to "virtual", so these long paragraphs should appear in multiple lines (wrapped at the end of the textbox): Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean non luctus elit. In sed nunc velit. Donec gravida eros sollicitudin ligula mollis id eleifend mauris laoreet. Donec turpis magna, pulvinar id pretium eu, blandit et nisi. Nulla facilisi. Vivamus pharetra orci sed nunc auctor condimentum. Aenean volutpat posuere scelerisque. Nullam sed dolor justo. Pellentesque id tellus nunc, id sodales diam. Sed rhoncus risus a enim lacinia tincidunt. Aliquam ut neque augue.',
            ),
        ),
        'text_8' => array(
            'exclude' => 1,
            'label' => 'TEXT: 8 eval trim',
            'config' => array(
                'type' => 'text',
                'eval' => 'trim',
            ),
        ),
        'text_9' => array(
            'exclude' => 1,
            'label' => 'TEXT: 9 eval with user function',
            'config' => array(
                'type' => 'text',
                'eval' => 'TYPO3\\CMS\\Styleguide\\UserFunctions\\FormEngine\\TypeText9Eval',
            ),
        ),
        'text_10' => array(
            'exclude' => 1,
            'label' => 'TEXT: 10 readOnly',
            'config' => array(
                'type' => 'text',
                'readOnly' => 1,
            ),
        ),
        'text_11' => array(
            'exclude' => 1,
            'label' => 'TEXT: 11 readOnly with format datetime',
            'config' => array(
                'type' => 'text',
                'readOnly' => 1,
                'format' => 'datetime',
            ),
        ),
        'text_12' => array(
            'exclude' => 1,
            'label' => 'TEXT: 12 placeholder value from input_1',
            'config' => array(
                'type' => 'text',
                'placeholder' => '__row|input_1',
            ),
        ),
        'text_13' => array(
            'exclude' => 1,
            'label' => 'TEXT: 13 placeholder value from input_1 with mode useOrOverridePlaceholder',
            'config' => array(
                'type' => 'text',
                'placeholder' => '__row|input_1',
                'eval' => 'null',
                'mode' => 'useOrOverridePlaceholder',
            ),
        ),
        'text_14' => array(
            'exclude' => 1,
            'label' => 'TEXT: 14 fixed font & tabs enabled',
            'config' => array(
                'type' => 'text',
            ),
            'defaultExtras' => 'fixed-font : enable-tab'
        ),
        'text_15' => array(
            'exclude' => 1,
            'label' => 'TEXT: 15 max=30',
            'config' => array(
                'type' => 'text',
                'cols' => 30,
                'rows' => 4,
                'max' => 30,
            ),
        ),
        /**
         * @TODO: Add type text wizards
         */


        'checkbox_1' => array(
            'exclude' => 1,
            'label' => 'CHECKBOX: 1 Single',
            'config' => array(
                'type' => 'check',
            )
        ),
        'checkbox_2' => array(
            'exclude' => 1,
            'label' => 'CHECKBOX: 2 Single default=1',
            'config' => array(
                'type' => 'check',
                'default' => 1,
            )
        ),
        'checkbox_3' => array(
            'exclude' => 1,
            'label' => 'CHECKBOX: 3 One checkbox with label',
            'config' => array(
                'type' => 'check',
                'items' => array(
                    array('foo', ''),
                ),
            )
        ),
        'checkbox_4' => array(
            'exclude' => 1,
            'label' => 'CHECKBOX: 4 One checkbox with label, pre-selected',
            'config' => array(
                'type' => 'check',
                'items' => array(
                    array('foo', ''),
                ),
                'default' => 1
            )
        ),
        'checkbox_5' => array(
            'exclude' => 1,
            'label' => 'CHECKBOX: 5 Three checkboxes, two with labels, one without',
            'config' => array(
                'type' => 'check',
                'items' => array(
                    array('foo', ''),
                    array('', ''),
                    array('foobar', ''),
                ),
            ),
        ),
        'checkbox_6' => array(
            'exclude' => 1,
            'label' => 'CHECKBOX: 6 Four checkboxes with labels, 1 and 3 pre-selected',
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
            'exclude' => 1,
            'label' => 'CHECKBOX: 7 showIfRTE - only shown if RTE is enabled for user',
            'config' => array(
                'type' => 'check',
                'items' => array(
                    array('foo', ''),
                ),
                'showIfRTE' => 1,
            ),
        ),
        'checkbox_8' => array(
            // @todo: Checking a checkbox that is added by itemsProcFunc is not persisted correctly.
            // @todo: HTML looks good, so this is probably an issue in DataHandler?
            'label' => 'CHECKBOX: 8 itemsProcFunc',
            'config' => array(
                'type' => 'check',
                'items' => array(
                    array('foo', ''),
                    array('bar', ''),
                ),
                'itemsProcFunc' => 'TYPO3\\CMS\\Styleguide\\UserFunctions\\FormEngine\\TypeCheckbox8ItemsProcFunc->itemsProcFunc',
            ),
        ),
        'checkbox_9' => array(
            'exclude' => 1,
            'label' => 'CHECKBOX: 9 eval maximumRecordsChecked = 1 - table wide',
            'config' => array(
                'type' => 'check',
                'eval' => 'maximumRecordsChecked',
                'validation' => array(
                    'maximumRecordsChecked' => 1,
                ),
            ),
        ),
        'checkbox_10' => array(
            'exclude' => 1,
            'label' => 'CHECKBOX: 10 eval maximumRecordsCheckedInPid = 1 - for this PID',
            'config' => array(
                'type' => 'check',
                'eval' => 'maximumRecordsCheckedInPid',
                'validation' => array(
                    'maximumRecordsCheckedInPid' => 1,
                ),
            ),
        ),
        'checkbox_11' => array(
            'exclude' => 1,
            'label' => 'CHECKBOX: 11 some checkboxes, readonly',
            'config' => array(
                'type' => 'check',
                'readOnly' => 1,
                'items' => array(
                    array('foo1', ''),
                    array('foo2', ''),
                ),
            ),
        ),
        'checkbox_12' => array(
            'exclude' => 1,
            'label' => 'CHECKBOX: 12 1 cols',
            'config' => array(
                'type' => 'check',
                'items' => array(
                    array('foo1', ''),
                    array('foo2', ''),
                    array('foo3', ''),
                ),
                'cols' => '1',
            ),
        ),
        'checkbox_13' => array(
            'exclude' => 1,
            'label' => 'CHECKBOX: 13 2 cols',
            'config' => array(
                'type' => 'check',
                'items' => array(
                    array('foo1', ''),
                    array('foo2', ''),
                    array('foo3', ''),
                ),
                'cols' => '2',
            ),
        ),
        'checkbox_14' => array(
            'exclude' => 1,
            'label' => 'CHECKBOX: 14 3 cols',
            'config' => array(
                'type' => 'check',
                'items' => array(
                    array('foo1', ''),
                    array('foo2', ''),
                    array('foo3', ''),
                    array('foo4', ''),
                ),
                'cols' => '3',
            ),
        ),
        'checkbox_15' => array(
            'exclude' => 1,
            'label' => 'CHECKBOX: 15 4 cols',
            'config' => array(
                'type' => 'check',
                'items' => array(
                    array('foo1', ''),
                    array('foo2', ''),
                    array('foo3 and this here is very long text that maybe does not really fit into the form in one line. Ok let us add even more text to see how', ''),
                    array('foo4', ''),
                    array('foo5', ''),
                    array('foo6', ''),
                    array('foo7', ''),
                    array('foo8', ''),
                ),
                'cols' => '4',
            ),
        ),
        'checkbox_16' => array(
            'exclude' => 1,
            'label' => 'CHECKBOX: 16 5 cols',
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
                'cols' => '5',
            ),
        ),
        'checkbox_17' => array(
            'exclude' => 1,
            'label' => 'CHECKBOX: 17 6 cols',
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
                'cols' => '6',
            ),
        ),
        'checkbox_18' => array(
            'exclude' => 1,
            'label' => 'CHECKBOX: 18 inline',
            'config' => array(
                'type' => 'check',
                'items' => array(
                    array('Mo', ''),
                    array('Tu', ''),
                    array('We', ''),
                    array('Th', ''),
                    array('Fr', ''),
                    array('Sa', ''),
                    array('Su', ''),
                ),
                'cols' => 'inline',
            ),
        ),



        'radio_1' => array(
            'exclude' => 1,
            'label' => 'RADIO: 1 Three options, one without label',
            'config' => array(
                'type' => 'radio',
                'items' => array(
                    array('foo', 1),
                    array('', 2),
                    array('foobar', 3),
                ),
            ),
        ),
        'radio_2' => array(
            'exclude' => 1,
            'label' => 'RADIO: 2 Three options, second pre-selected',
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
            'exclude' => 1,
            'label' => 'RADIO: 3 Lots of options',
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
            'exclude' => 1,
            'label' => 'RADIO: 4 String values',
            'config' => array(
                'type' => 'radio',
                'items' => array(
                    array('foo', 'foo'),
                    array('bar', 'bar'),
                ),
            ),
        ),
        'radio_5' => array(
            // @todo: Radio elements added by itemsProcFunc are not persisted correctly.
            // @todo: HTML looks good, so this is probably an issue in DataHandler?
            'exclude' => 1,
            'label' => 'RADIO: 5 itemsProcFunc',
            'config' => array(
                'type' => 'radio',
                'items' => array(
                    array('foo', 1),
                    array('bar', 2),
                ),
                'itemsProcFunc' => 'TYPO3\\CMS\\Styleguide\\UserFunctions\\FormEngine\\TypeRadio5ItemsProcFunc->itemsProcFunc',
            ),
        ),
        'radio_6' => array(
            'exclude' => 1,
            'label' => 'RADIO: 6 readonly',
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
            'exclude' => 1,
            'label' => 'SELECT: 1 Two items, one with really long text',
            'config' => array(
                'type' => 'select',
                'items' => array(
                    array('foo and this here is very long text that maybe does not really fit into the form in one line. Ok let us add even more text to see how this looks like if wrapped. Is this enough now? No? Then let us add some even more useless text here!', 1),
                    array('bar', 'bar'),
                ),
            ),
        ),
        'select_2' => array(
            'exclude' => 1,
            'label' => 'SELECT: 2 itemsProcFunc',
            'config' => array(
                'type' => 'select',
                'items' => array(
                    array('foo', 1),
                    array('bar', 'bar'),
                ),
                'itemsProcFunc' => 'TYPO3\\CMS\\Styleguide\\UserFunctions\\FormEngine\\TypeSelect2ItemsProcFunc->itemsProcFunc',
            ),
        ),
        'select_33' => array(
            'exclude' => 1,
            'label' => 'SELECT: 33 itemsProcFunc with maxitems > 1',
            'config' => array(
                'type' => 'select',
                'maxitems' => 42,
                'items' => array(
                    array('foo', 1),
                    array('bar', 'bar'),
                ),
                'itemsProcFunc' => 'TYPO3\\CMS\\Styleguide\\UserFunctions\\FormEngine\\TypeSelect33ItemsProcFunc->itemsProcFunc',
            ),
        ),
        'select_34' => array(
            'exclude' => 1,
            'label' => 'SELECT: 34 maxitems=1, renderType=selectSingleBox',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingleBox',
                'items' => array(
                    array('foo 1', 1),
                    array('foo 2', 2),
                    array('foo 3', 3),
                    array('foo 4', 4),
                    array('foo 5', 5),
                    array('foo 6', 6),
                ),
                'maxitems' => 1,
            ),
        ),
        'select_3' => array(
            'exclude' => 1,
            'label' => 'SELECT: 3 Three items, second pre-selected, size=2',
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
            'exclude' => 1,
            'label' => 'SELECT: 4 Static values, dividers, merged with entries from staticdata table containing word "foo"',
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
            'exclude' => 1,
            'label' => 'SELECT: 5 Items with icons',
            'config' => array(
                'type' => 'select',
                'items' => array(
                    array('Icon using EXT:', 'foo', 'EXT:styleguide/Resources/Public/Icons/tx_styleguide_forms.svg'),
                    array('Icon from typo3/gfx', 'es', 'flags/es.gif'), // @TODO: docu says typo3/sysext/t3skin/icons/gfx/, but in fact it is typo3/gfx.
                ),
            ),
        ),
        'select_6' => array(
            'exclude' => 1,
            'label' => 'SELECT: 6 Items with icons, iconsInOptionTags',
            'config' => array(
                'type' => 'select',
                'items' => array(
                    array('Icon using EXT:', 'foo', 'EXT:styleguide/Resources/Public/Icons/tx_styleguide_forms.svg'),
                    array('Icon from typo3/gfx', 'es', 'flags/es.gif'),
                ),
                'iconsInOptionTags' => true,
            ),
        ),
        'select_7' => array(
            'exclude' => 1,
            'label' => 'SELECT: 7 Items with icons, iconsInOptionTags, noIconsBelowSelect',
            'config' => array(
                'type' => 'select',
                'items' => array(
                    array('Icon using EXT:', 'foo', 'EXT:styleguide/Resources/Public/Icons/tx_styleguide_forms.svg'),
                    array('Icon from typo3/gfx', 'es', 'flags/es.gif'),
                ),
                'iconsInOptionTags' => true,
                'noIconsBelowSelect' => true,
            ),
        ),
        'select_8' => array(
            'exclude' => 1,
            'label' => 'SELECT: 8 Items with icons, selicon_cols set to 3',
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
            'exclude' => 1,
            'label' => 'SELECT: 9 fileFolder Icons from EXT:styleguide/Resources/Public/Icons and a dummy first entry, iconsInOptionTags, two columns',
            'config' => array(
                'type' => 'select',
                'items' => array(
                    array('', 0),
                ),
                'fileFolder' => 'EXT:styleguide/Resources/Public/Icons',
                'fileFolder_extList' => 'png',
                'fileFolder_recursions' => 1,
                'iconsInOptionTags' => true,
                'selicon_cols' => 2,
            ),
        ),
        'select_10' => array(
            'exclude' => 1,
            'label' => 'SELECT: 10 three options, size=6',
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
            'exclude' => 1,
            'label' => 'SELECT: 11 two options, size=2',
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
            'exclude' => 1,
            'label' => 'SELECT: 12 multiple, autoSizeMax=4, size=3',
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
                'minitems' => 0,
                'multiple' => true, // @TODO: multiple does not seem to have any effect at all? Can be commented without change.
            ),
        ),
        'select_13' => array(
            'exclude' => 1,
            'label' => 'SELECT: 13 multiple, exclusiveKeys for 1 and 2',
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
                'multiple' => true, // @TODO: multiple does not seem to have any effect at all?! Can be commented without change.
                'size' => 5,
                'maxitems' => 20,
                'exclusiveKeys' => '1,2',
            ),
        ),
        'select_14' => array(
            'exclude' => 1,
            'label' => 'SELECT: 14 maxitems=1, single',
            'config' => array(
                'type' => 'select',
                'items' => array(
                    array('foo 1', 1),
                    array('foo 2', 2),
                    array('foo 3', 3),
                    array('foo 4', 4),
                    array('foo 5', 5),
                    array('foo 6', 6),
                ),
                'size' => 4,
                'maxitems' => 1,
            ),
        ),
        'select_15' => array(
            'exclude' => 1,
            'label' => 'SELECT: 15 Drop down with empty div',
            'config' => array(
                'type' => 'select',
                'items' => array(
                    array('First div with items', '--div--'),
                    array('item1', 1),
                    array('item2', 2),
                    array('Second div without items', '--div--'),
                    array('Third div with items', '--div--'),
                    array('item3', 3),
                ),
            ),
        ),
        'select_16' => array(
            'exclude' => 1,
            'label' => 'SELECT: 16 maxitems=10, no size set',
            'config' => array(
                'type' => 'select',
                'items' => array(
                    array('foo 1', 1),
                    array('foo 2', 2),
                    array('foo 3', 3),
                    array('foo 4', 4),
                    array('foo 5', 5),
                    array('foo 6', 6),
                    array('foo 7', 7),
                    array('foo 8', 8),
                    array('foo 9', 9),
                    array('foo 10', 10),
                    array('foo 11', 11),
                    array('foo 12', 12),
                ),
                'maxitems' => 10,
            ),
        ),
        'select_17' => array(
            'exclude' => 1,
            'label' => 'SELECT: 17 multiple size=1',
            'config' => array(
                'type' => 'select',
                'multiple' => true,
                'maxItems' => 1,
                'items' => array(
                    array('foo 1', 1),
                    array('foo 2', 2),
                    array('foo 3', 3),
                    array('foo 4', 4),
                    array('foo 5', 5),
                    array('foo 6', 6),
                    array('foo 7', 7),
                    array('foo 8', 8),
                    array('foo 9', 9),
                    array('foo 10', 10),
                    array('foo 11', 11),
                    array('foo 12', 12),
                ),
            ),
        ),
        'select_21' => array(
            'exclude' => 1,
            'label' => 'SELECT: 21 itemListStyle: green, 250 width and selectedListStyle: red, width 350',
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
            'exclude' => 1,
            'label' => 'SELECT: 22 renderMode=checkbox',
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
            'exclude' => 1,
            'label' => 'SELECT: 23 renderMode=checkbox with icons and description',
            'config' => array(
                'type' => 'select',
                'items' => array(
                    array('foo 1', 1, '', 'optional description'), // @TODO: In contrast to "items" documentation, description seems not to have an effect for renderMode=checkbox
                    array('foo 2', 2, 'EXT:styleguide/Resources/Public/Icons/tx_styleguide_forms.svg', 'other description'),
                    array('foo 3', 3, '', ''),
                ),
                'renderMode' => 'checkbox',
                'maxitems' => 2,
            ),
        ),
        'select_24' => array(
            'exclude' => 1,
            'label' => 'SELECT: 24 renderMode=singlebox',
            'config' => array(
                'type' => 'select',
                'items' => array(
                    array('foo 1', 1),
                    array('foo 2', 2),
                    array('divider', '--div--'),
                    array('foo 3', 3),
                    array('foo 4', 4),
                ),
                'renderMode' => 'singlebox',
                'maxitems' => 2,
            ),
        ),
        'select_25' => array(
            'exclude' => 1,
            'label' => 'SELECT: 25 renderMode=tree of pages',
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
                        'showHeader' => true,
                    ),
                ),
            ),
        ),
        'select_26' => array(
            'exclude' => 1,
            'label' => 'SELECT: 26 renderMode=tree of pages showHeader=FALSE, nonSelectableLevels=0,1, allowRecursiveMode=TRUE, width=400',
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
                        'showHeader' => false,
                        'nonSelectableLevels' => '0,1',
                        'allowRecursiveMode' => true, // @TODO: No effect?
                        'width' => 400,
                    ),
                ),
            ),
        ),
        'select_27' => array(
            'exclude' => 1,
            'label' => 'SELECT: 27 enableMultiSelectFilterTextfield',
            'config' => array(
            'type' => 'select',
                'items' => array(
                    array('foo 1', 1),
                    array('foo 2', 2),
                    array('foo 3', 3),
                    array('bar', 4),
                ),
                'size' => 5,
                'minitems' => 0,
                'maxitems' => 999,
                'enableMultiSelectFilterTextfield' => true,
            ),
        ),
        'select_28' => array(
            'exclude' => 1,
            'label' => 'SELECT: 28 enableMultiSelectFilterTextfield, multiSelectFilterItems',
            'config' => array(
                'type' => 'select',
                'items' => array(
                    array('foo 1', 1),
                    array('foo 2', 2),
                    array('foo 3', 3),
                    array('bar', 4),
                ),
                'size' => 5,
                'minitems' => 0,
                'maxitems' => 999,
                'enableMultiSelectFilterTextfield' => true,
                'multiSelectFilterItems' => array(
                    array('', ''),
                    array('foo', 'foo'),
                    array('bar', 'bar'),
                ),
            ),
        ),
        'select_29' => array(
            'exclude' => 1,
            'label' => 'SELECT: 29 wizards',
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
                        'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_edit.gif',
                        'popup_onlyOpenIfSelected' => 1,
                        'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
                    ),
                    'add' => array(
                        'type' => 'script',
                        'title' => 'add',
                        'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_add.gif',
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
                        'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_list.gif',
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
        'select_30' => array(
            'exclude' => 1,
            'label' => 'SELECT: 30 Slider wizard, step=1, width=200, items',
            'config' => array(
                'type' => 'select',
                'items' => array(
                    array('foo1', 1),
                    array('foo2', 2),
                    array('foo3', 4),
                    array('foo4', 7),
                    array('foo5', 8),
                    array('foo6', 11),
                ),
                'default' => 4,
                'wizards' => array(
                    'angle' => array(
                        'type' => 'slider',
                        'step' => 1,
                        'width' => 200,
                    ),
                ),
            ),
        ),
        'select_31' => array(
            'exclude' => 1,
            'label' => 'SELECT: 31 renderMode=tree of pages with maxLevels=1',
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
                        'showHeader' => true,
                        'maxLevels' => 1,
                    ),
                ),
            ),
        ),
        'select_32' => array(
            'exclude' => 1,
            'label' => 'SELECT: 32 renderMode=tree of pages with maxLevels=2',
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
                        'showHeader' => true,
                        'maxLevels' => 2,
                    ),
                ),
            ),
        ),


        'group_1' => array(
            'exclude' => 1,
            'label' => 'GROUP: 1 internal_type=db, maxitems=999, two tables allowed',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'be_users,be_groups',
                'maxitems' => 999,
            ),
        ),
        'group_2' => array(
            'exclude' => 1,
            'label' => 'GROUP: 2 internal_type=db, maxitems=999, two tables allowed, show thumbs',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'be_users,be_groups',
                'show_thumbs' => true,
                'maxitems' => 999,
            ),
        ),
        'group_3' => array(
            'exclude' => 1,
            'label' => 'GROUP: 3 internal_type=db, maxitems=999, suggest wizard, disable_controls=browser',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_styleguide_forms_staticdata',
                'disable_controls' => 'browser',
                'wizards' => array(
                    'suggest' => array(
                        'type' => 'suggest',
                    ),
                ),
                'maxitems' => 999,
            ),
        ),
        'group_4' => array(
            'exclude' => 1,
            'label' => 'GROUP: 4 internal_type=db, show_thumbs, maxitems=1, size=1, suggest wizard',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_styleguide_forms_staticdata',
                'show_thumbs' => true,
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
            'exclude' => 1,
            'label' => 'GROUP: 5 internal_type=file, lots of file types allowed, show thumbs',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => 'jpg, jpeg, png, gif',
                'disallowed' => 'ai',
                'show_thumbs' => true,
                'size' => 3,
                'uploadfolder' => 'uploads/pics/',
                'disable_controls' => 'upload', // @TODO: Documented feature has no effect since upload field in form is not shown anymore (since fal?)
                'max_size' => 2000,
                // @todo: does maxitems = 1 default hit here? YES!
                'maxitems' => 999,
            ),
        ),
        'group_6' => array(
            'exclude' => 1,
            'label' => 'GROUP: 6 internal_type=file, delete control disabled, no thumbs',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => 'jpg',
                'size' => 3,
                'uploadfolder' => 'uploads/pics/',
                'disable_controls' => 'delete',
            ),
        ),
        'group_7' => array(
            'exclude' => 1,
            'label' => 'GROUP: 7 internal_type=file, size=1',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => 'jpg',
                'size' => 1,
                'uploadfolder' => 'uploads/pics/',
            ),
        ),
        'group_8' => array(
            'exclude' => 1,
            'label' => 'GROUP: 8 internal_type=file, selectedListStyles set',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => 'jpg',
                'uploadfolder' => 'uploads/pics/',
                'selectedListStyle' => 'width:400px;background-color:#ccffcc;',
            ),
        ),
        'group_9' => array(
            'exclude' => 1,
            'label' => 'GROUP: 9 internal_type=file, maxitems=2',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => 'jpg',
                'uploadfolder' => 'uploads/pics/',
                'maxitems' => 4,
            ),
        ),
        'group_10' => array(
            'exclude' => 1,
            'label' => 'GROUP: 10 internal_type=file, maxitems=2',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => 'jpg',
                'uploadfolder' => 'uploads/pics/',
                'maxitems' => 2, // @TODO: Warning sign is missing if too many entries are added
            ),
        ),
        'group_11' => array(
            'exclude' => 1,
            'label' => 'GROUP: 11 group FAL field',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'sys_file',
                'MM' => 'sys_file_reference',
                'MM_match_fields' => array(
                    'fieldname' => 'image_fal_group',
                ),
                'prepend_tname' => true,
                'appearance' => array(
                    'elementBrowserAllowed' => 'jpg, png, gif',
                    'elementBrowserType' => 'file',
                ),
                'max_size' => 2000,
                'show_thumbs' => true,
                'size' => '3',
                'maxitems' => 200,
                'minitems' => 0,
                'autoSizeMax' => 40,
            ),
        ),
        'group_12' => array(
            'exclude' => 1,
            'label' => 'GROUP: 12 readonly',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'be_users,be_groups',
                'readOnly' => 1,
            )
        ),
        'group_13' => array(
            'exclude' => 1,
            'label' => 'GROUP: 13 internal_type=folder, maxitems=1',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'folder',
                'maxitems' => 1,
            ),
        ),

        'none_1' => array(
            'exclude' => 1,
            'label' => 'NONE: 1 pass_content=1',
            'config' => array(
                'type' => 'none',
                'pass_content' => 1,
            ),
        ),
        'none_2' => array(
            'exclude' => 1,
            'label' => 'NONE: 2 pass_content=0',
            'config' => array(
                'type' => 'none',
                'pass_content' => 0,
            ),
        ),
        'none_3' => array(
            'exclude' => 1,
            'label' => 'NONE: 3 rows=2',
            'config' => array(
                'type' => 'none',
                'rows' => 2,
            ),
        ),
        'none_4' => array(
            'exclude' => 1,
            'label' => 'NONE: 4 cols=2',
            'config' => array(
                'type' => 'none',
                'cols' => 2,
            ),
        ),
        'none_5' => array(
            'exclude' => 1,
            'label' => 'NONE: 5 rows=2, fixedRows=2',
            'config' => array(
                'type' => 'none',
                'rows' => 2,
                'fixedRows' => 2,
            ),
        ),
        'none_6' => array(
            'exclude' => 1,
            'label' => 'NONE: 6 size=6',
            'config' => array(
                'type' => 'none',
                'size' => 6,
            ),
        ),
        /**
         * @TODO: Add a default record to adt.sql to show all the format options of type=none
         */


        'passthrough_1' => array(
            'exclude' => 1,
            'label' => 'PASSTHROUGH: 1 this should NOT be shown',
            'config' => array(
                'type' => 'passthrough',
            ),
        ),


        'user_1' => array(
            'exclude' => 1,
            'label' => 'USER: 1 parameter color used as border color',
            'config' => array(
                'type' => 'user',
                'userFunc' => 'TYPO3\\CMS\\Styleguide\\UserFunctions\\FormEngine\\TypeUser1->render',
                'parameters' => array(
                    'color' => 'green',
                ),
            ),
        ),
        'user_2' => array(
            'exclude' => 1,
            'label' => 'USER: 2 noTableWrapping',
            'config' => array(
                'type' => 'user',
                'userFunc' => 'TYPO3\\CMS\\Styleguide\\UserFunctions\\FormEngine\\TypeUser2->render',
                'parameters' => array(
                    'color' => 'green',
                ),
                'noTableWrapping' => true,
            ),
        ),


        'flex_1' => array(
            'exclude' => 1,
            'label' => 'FLEX: 1 simple flex form',
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
												<default>a default value</default>
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
            'exclude' => 1,
            'label' => 'FLEX: 2 simple flex form with langDisable=1',
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
            'exclude' => 1,
            'label' => 'FLEX: 3 complex flexform in an external file',
            'config' => array(
                'type' => 'flex',
                'ds' => array(
                    'default' => 'FILE:EXT:styleguide/Configuration/Flexform/Flex_3.xml',
                ),
            ),
        ),
        'flex_4' => array(
            'exclude' => 1,
            'label' => 'FLEX: 4 multiple items',
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
									<input_2>
										<TCEforms>
											<label>Some input field</label>
											<config>
												<type>input</type>
												<size>23</size>
											</config>
										</TCEforms>
									</input_2>
								</el>
							</ROOT>
						</T3DataStructure>
					',
                ),
            ),
        ),
        'flex_5' => array(
            'exclude' => 1,
            'label' => 'FLEX: 5 condition',
            'config' => array(
                'type' => 'flex',
                'ds' => array(
                    'default' => 'FILE:EXT:styleguide/Configuration/Flexform/Condition.xml',
                ),
            ),
        ),


        'inline_1' => array(
            'exclude' => 1,
            'label' => 'IRRE: 1 typical FAL field',
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
                    'useSortable' => true,
                    'headerThumbnail' => array(
                        'field' => "uid_local",
                        'width' => "45",
                        'height' => "45c",
                    ),
                ),
                'showPossibleLocalizationRecords' => false,
                'showRemovedLocalizationRecords' => false,
                'showSynchronizationLink' => false,
                'showAllLocalizationLink' => false,
                'enabledControls' => array(
                    'info' => true,
                    'new' => false,
                    'dragdrop' => true,
                    'sort' => false,
                    'hide' => true,
                    'delete' => true,
                    'localize' => true,
                ),
                'createNewRelationLinkTitle' => "LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference",
                'behaviour' => array(
                    'localizationMode' => "select",
                    'localizeChildrenAtParentLocalization' => true,
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
            'exclude' => 1,
            'label' => 'IRRE: 2 1:n foreign field to table with sheets with a custom text expandSingle',
            'config' => array(
                'type' => 'inline',
                'foreign_table' => 'tx_styleguide_forms_inline_2_child1',
                'foreign_field' => 'parentid',
                'foreign_table_field' => 'parenttable',
                'maxitems' => 10,
                'appearance' => array(
                    'expandSingle' => true,
                    'showSynchronizationLink' => true,
                    'showAllLocalizationLink' => true,
                    'showPossibleLocalizationRecords' => true,
                    'showRemovedLocalizationRecords' => true,
                    'newRecordLinkTitle' => 'Create a new relation "inline_2"',
                ),
                'behaviour' => array(
                    'localizationMode' => 'select',
                    'localizeChildrenAtParentLocalization' => true,
                ),
            ),
        ),
        'inline_3' => array(
            'exclude' => 1,
            'label' => 'IRRE: 3 m:m async, useCombination, newRecordLinkAddTitle',
            'config' => array(
                'type' => 'inline',
                'foreign_table' => 'tx_styleguide_forms_inline_3_mm',
                'foreign_field' => 'select_parent',
                'foreign_selector' => 'select_child',
                'foreign_unique' => 'select_child',
                'maxitems' => 9999,
                'appearance' => array(
                    'newRecordLinkAddTitle' => 1,
                    'useCombination' => true,
                    'collapseAll' => false,
                    'levelLinksPosition' => 'top',
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1,
                ),
            ),
        ),
        'inline_4' => array(
            'label' => 'IRRE: 4 media FAL field',
            'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig('inline_4', array(
                'appearance' => array(
                    'createNewRelationLinkTitle' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:images.addFileReference'
                ),
                // custom configuration for displaying fields in the overlay/reference table
                // to use the imageoverlayPalette instead of the basicoverlayPalette
                'foreign_types' => array(
                    '0' => array(
                        'showitem' => '
							--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
							--palette--;;filePalette'
                    ),
                    \TYPO3\CMS\Core\Resource\File::FILETYPE_TEXT => array(
                        'showitem' => '
							--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
							--palette--;;filePalette'
                    ),
                    \TYPO3\CMS\Core\Resource\File::FILETYPE_IMAGE => array(
                        'showitem' => '
							--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
							--palette--;;filePalette'
                    ),
                    \TYPO3\CMS\Core\Resource\File::FILETYPE_AUDIO => array(
                        'showitem' => '
							--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.audioOverlayPalette;audioOverlayPalette,
							--palette--;;filePalette'
                    ),
                    \TYPO3\CMS\Core\Resource\File::FILETYPE_VIDEO => array(
                        'showitem' => '
							--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.videoOverlayPalette;videoOverlayPalette,
							--palette--;;filePalette'
                    ),
                    \TYPO3\CMS\Core\Resource\File::FILETYPE_APPLICATION => array(
                        'showitem' => '
							--palette--;LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.imageoverlayPalette;imageoverlayPalette,
							--palette--;;filePalette'
                    )
                )
            ), $GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext'])
        ),
        'inline_5' => array(
            'exclude' => 1,
            'label' => 'IRRE: 5 tt_content child with foreign_record_defaults',
            'config' => array(
                'type' => 'inline',
                'allowed' => 'tt_content',
                'foreign_table' => 'tt_content',
                'foreign_record_defaults' => array(
                    'CType' => 'text'
                ),
                'minitems' => 0,
                'maxitems' => 1,
                'appearance' => array(
                    'collapseAll' => 0,
                    'expandSingle' => 1,
                    'levelLinksPosition' => 'bottom',
                    'useSortable' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showRemovedLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1,
                    'showSynchronizationLink' => 1,
                    'enabledControls' => array(
                        'info' => false,
                        'new' => false,
                        'dragdrop' => true,
                        'sort' => false,
                        'hide' => true,
                        'delete' => true,
                        'localize' => true,
                    ),
                ),
            ),
        ),
        'palette_1_1' => array(
            'exclude' => 0,
            'label' => 'checkbox is type check',
            'config' => array(
                'type' => 'check',
                'default' => 1,
            ),
        ),
        'palette_1_2' => array(
            'exclude' => 0,
            'label' => 'checkbox type is user',
            'config' => array(
                'default' => true,
                'type' => 'user',
                'userFunc' => 'TYPO3\\CMS\\Styleguide\\UserFunctions\\FormEngine\\TypeUserPalette->render',
            ),
        ),
        'palette_1_3' => array(
            'exclude' => 0,
            'label' => 'checkbox is type check',
            'config' => array(
                'type' => 'check',
                'default' => 1,
            ),
        ),
        'palette_2_1' => array(
            'label' => 'Palette Field',
            'config' => array(
                'type' => 'input',
            ),
        ),
        'palette_3_1' => array(
            'label' => 'Palette Field',
            'config' => array(
                'type' => 'input',
            ),
        ),
        'palette_3_2' => array(
            'label' => 'Palette Field',
            'config' => array(
                'type' => 'input',
            ),
        ),
        'palette_4_1' => array(
            'label' => 'Palette Field',
            'config' => array(
                'type' => 'input',
            ),
        ),
        'palette_4_2' => array(
            'label' => 'Palette Field',
            'config' => array(
                'type' => 'input',
            ),
        ),
        'palette_4_3' => array(
            'label' => 'Palette Field',
            'config' => array(
                'type' => 'input',
            ),
        ),
        'palette_4_4' => array(
            'label' => 'Palette Field',
            'config' => array(
                'type' => 'input',
            ),
        ),
        'palette_5_1' => array(
            'label' => 'Palette Field',
            'config' => array(
                'type' => 'input',
            ),
        ),
        'palette_5_2' => array(
            'label' => 'Palette Field',
            'config' => array(
                'type' => 'input',
            ),
        ),
        'palette_6_1' => array(
            'label' => 'PALETTE: Simple field with palette below',
            'exclude' => 1,
            'config' => array(
                'type' => 'input',
            ),
        ),
        'palette_6_2' => array(
            'label' => 'Palette Field',
            'config' => array(
                'type' => 'input',
            ),
        ),
        'palette_6_3' => array(
            'label' => 'Palette Field',
            'config' => array(
                'type' => 'input',
            ),
        ),


        'wizard_1' => array(
            'label' => 'WIZARD: 1 vertical, edit, add, list',
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
                        'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_edit.gif',
                        'popup_onlyOpenIfSelected' => 1,
                        'JSopenParams' => 'height=350,width=580,status=0,menubar=0,scrollbars=1',
                    ),
                    'add' => array(
                        'type' => 'script',
                        'title' => 'add',
                        'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_add.gif',
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
                        'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_list.gif',
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
        'wizard_2' => array(
            'exclude' => 1,
            'label' => 'WIZARD: 2 colorbox',
            'config' => array(
                'type' => 'input',
                'wizards' => array(
                    '_PADDING' => 6,
                    'colorpicker' => array(
                        'type' => 'colorbox',
                        'title' => 'Color picker',
                        'module' => array(
                            'name' => 'wizard_colorpicker',
                        ),
                        'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1',
                    ),
                ),
            ),
        ),
        'wizard_3' => array(
            'label' => 'WIZARD: 3 colorbox, with image',
            'config' => array(
                'type' => 'input',
                'wizards' => array(
                    'colorpicker' => array(
                        'type' => 'colorbox',
                        'title' => 'Color picker',
                        'module' => array(
                            'name' => 'wizard_colorpicker',
                        ),
                        'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1',
                        'exampleImg' => 'EXT:styleguide/Resources/Public/Images/colorpicker.jpg',
                    ),
                ),
            ),
        ),
        'wizard_4' => array(
            'label' => 'WIZARD: 4 suggest wizard, position top',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_styleguide_forms_staticdata',
                'disable_controls' => 'browser',
                'maxitems' => 999,
                'wizards' => array(
                    '_POSITION' => 'top',
                    'suggest' => array(
                        'type' => 'suggest',
                    ),
                ),
            ),
        ),
        'wizard_5' => array(
            'label' => 'WIZARD: 5 suggest wizard, position bottom',
            'config' => array(
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_styleguide_forms_staticdata',
                'disable_controls' => 'browser',
                'maxitems' => 999,
                'wizards' => array(
                    '_POSITION' => 'bottom',
                    'suggest' => array(
                        'type' => 'suggest',
                    ),
                ),
            ),
        ),
        'wizard_6' => array(
            'exclude' => 1,
            'label' => 'WIZARD 6: Flex forms',
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
									<link_1>
										<TCEforms>
											<label>LINK 1</label>
											<config>
												<type>input</type>
												<eval>trim</eval>
												<softref>typolink</softref>
												<wizards type="array">
													<link type="array">
														<type>popup</type>
														<title>Link</title>
														<icon>EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_link.gif</icon>
														<module type="array">
															<name>wizard_element_browser</name>
															<urlParameters type="array">
																<mode>wizard</mode>
																<act>file|url</act>
															</urlParameters>
														</module>
														<params type="array">
															<blindLinkOptions>mail,folder,spec</blindLinkOptions>
														</params>
														<JSopenParams>height=300,width=500,status=0,menubar=0,scrollbars=1</JSopenParams>
													</link>
												</wizards>
											</config>
										</TCEforms>
									</link_1>
									<table_1>
										<TCEforms>
											<label>TABLE 1</label>
												<config>
													<type>text</type>
													<cols>30</cols>
													<rows>5</rows>
													<wizards>
														<table type="array">
															<type>script</type>
															<title>Table wizard</title>
															<icon>EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_table.gif</icon>
															<module type="array">
																<name>wizard_table</name>
															</module>
															<params type="array">
																<xmlOutput>0</xmlOutput>
															</params>
															<notNewRecords>1</notNewRecords>
														</table>
													</wizards>
												</config>
										</TCEforms>
									</table_1>
								</el>
							</ROOT>
						</T3DataStructure>
					',
                ),
            ),
        ),
        'wizard_7' => array(
            'label' => 'WIZARD: 7 table',
            'config' => array(
                'type' => 'text',
                'cols' => '40',
                'rows' => '5',
                'wizards' => array(
                    'table' => array(
                        'type' => 'script',
                        'title' => 'Table wizard',
                        'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_table.gif',
                        'module' => array(
                            'name' => 'wizard_table'
                        ),
                        'params' => array(
                            'xmlOutput' => 0
                        ),
                        'notNewRecords' => 1,
                    ),
                ),
            ),
        ),


        'rte_1' => array(
            'exclude' => 1,
            'label' => 'RTE 1',
            'config' => array(
                'type' => 'text',
            ),
            'defaultExtras' => 'richtext[*]:rte_transform[mode=ts_css]',
        ),
        'rte_2' => array(
            'exclude' => 1,
            'label' => 'RTE 2',
            'config' => array(
                'type' => 'text',
                'cols' => 30,
                'rows' => 6,
            ),
            'defaultExtras' => 'richtext[]:rte_transform[mode=ts_css]',
        ),
        'rte_3' => array(
            'exclude' => 1,
            'label' => 'RTE 3: In inline child',
            'config' => array(
                'type' => 'inline',
                'foreign_table' => 'tx_styleguide_forms_rte_3_child1',
                'foreign_field' => 'parentid',
                'foreign_table_field' => 'parenttable',
            ),
        ),
        'rte_4' => array(
            'exclude' => 1,
            'label' => 'RTE 4: type flex, rte in a tab, rte in section container, rte in inline',
            'config' => array(
                'type' => 'flex',
                'ds' => array(
                    'default' => '
						<T3DataStructure>
							<sheets>
								<sGeneral>
									<ROOT>
										<TCEforms>
											<sheetTitle>RTE in tab</sheetTitle>
										</TCEforms>
										<type>array</type>
										<el>
											<rte_1>
												<TCEforms>
													<label>RTE 1</label>
													<config>
														<type>text</type>
													</config>
													<defaultExtras>richtext[]:rte_transform[mode=ts_css]</defaultExtras>
												</TCEforms>
											</rte_1>
										</el>
									</ROOT>
								</sGeneral>
								<sSections>
									<ROOT>
										<TCEforms>
											<sheetTitle>RTE in section</sheetTitle>
										</TCEforms>
										<type>array</type>
										<el>
											<section_1>
												<title>section_1</title>
												<type>array</type>
												<section>1</section>
												<el>
													<container_1>
														<type>array</type>
														<title>1 RTE field</title>
														<el>
															<rte_2>
																<TCEforms>
																	<label>RTE 2</label>
																	<config>
																		<type>text</type>
																	</config>
																	<defaultExtras>richtext[]:rte_transform[mode=ts_css]</defaultExtras>
																</TCEforms>
															</rte_2>
														</el>
													</container_1>
												</el>
											</section_1>
										</el>
									</ROOT>
								</sSections>
								<sInline>
									<ROOT>
										<TCEforms>
											<sheetTitle>RTE in inline</sheetTitle>
										</TCEforms>
										<type>array</type>
										<el>
											<inline_1>
												<TCEforms>
													<label>inline_1 to one field</label>
													<config>
														<type>inline</type>
														<foreign_table>tx_styleguide_forms_rte_4_flex_inline_1_child1</foreign_table>
														<foreign_field>parentid</foreign_field>
														<foreign_table_field>parenttable</foreign_table_field>
													</config>
												</TCEforms>
											</inline_1>
										</el>
									</ROOT>
								</sInline>
							</sheets>
						</T3DataStructure>
					',
                ),
            ),
        ),

        't3editor_1' => array(
            'exclude' => 1,
            'label' => 'T3EDITOR: 1',
            'config' => array(
                'type' => 'text',
                'renderType' => 't3editor',
                'format' => 'html',
                'rows' => 7,
            ),
        ),
        't3editor_2' => array(
            'exclude' => 1,
            'label' => 'T3EDITOR: 2 Enabled on type 0 via columnsOverride',
            'config' => array(
                'type' => 'text',
                'rows' => 7,
            ),
        ),
        't3editor_5' => array(
            'exclude' => 1,
            'label' => 'T3EDITOR: 5 In inline child',
            'config' => array(
                'type' => 'inline',
                'foreign_table' => 'tx_styleguide_forms_t3editor_5_child1',
                'foreign_field' => 'parentid',
                'foreign_table_field' => 'parenttable',
            ),
        ),
        't3editor_6' => array(
            'exclude' => 1,
            'label' => 'T3EDITOR 6: type flex, t3editor in a tab, t3editor in section container, t3editor in inline',
            'config' => array(
                'type' => 'flex',
                'ds' => array(
                    'default' => '
						<T3DataStructure>
							<sheets>
								<sGeneral>
									<ROOT>
										<TCEforms>
											<sheetTitle>t3editor in tab</sheetTitle>
										</TCEforms>
										<type>array</type>
										<el>
											<t3editor_1>
												<TCEforms>
													<label>T3EDITOR 1: New syntax</label>
													<config>
														<type>text</type>
														<renderType>t3editor</renderType>
													</config>
												</TCEforms>
											</t3editor_1>
										</el>
									</ROOT>
								</sGeneral>
								<sSections>
									<ROOT>
										<TCEforms>
											<sheetTitle>T3EDITOR in section</sheetTitle>
										</TCEforms>
										<type>array</type>
										<el>
											<section_1>
												<title>section_1</title>
												<type>array</type>
												<section>1</section>
												<el>
													<container_1>
														<type>array</type>
														<title>1 t3editor field</title>
														<el>
															<t3editor_3>
																<TCEforms>
																	<label>T3EDITOR 3</label>
																	<config>
																		<type>text</type>
																		<renderType>t3editor</renderType>
																	</config>
																</TCEforms>
															</t3editor_3>
														</el>
													</container_1>
												</el>
											</section_1>
										</el>
									</ROOT>
								</sSections>
								<sInline>
									<ROOT>
										<TCEforms>
											<sheetTitle>T3EDITOR in inline</sheetTitle>
										</TCEforms>
										<type>array</type>
										<el>
											<inline_1>
												<TCEforms>
													<label>inline_1 to one field</label>
													<config>
														<type>inline</type>
														<foreign_table>tx_styleguide_forms_t3editor_6_flex_inline_1_child1</foreign_table>
														<foreign_field>parentid</foreign_field>
														<foreign_table_field>parenttable</foreign_table_field>
													</config>
												</TCEforms>
											</inline_1>
										</el>
									</ROOT>
								</sInline>
							</sheets>
						</T3DataStructure>
					',
                ),
            ),
        ),


        'system_1' => array(
            'exclude' => 1,
            'label' => 'SYSTEM: 1 type select, special tables, renderMode checkbox, identical to be_groups tables_modify & tables_select',
            'config' => array(
                'type' => 'select',
                'special' => 'tables',
                'size' => '5',
                'autoSizeMax' => 50,
                'maxitems' => 100,
                'renderMode' => 'checkbox',
                'iconsInOptionTags' => 1,
            ),
        ),
        'system_2' => array(
            'exclude' => 1,
            'label' => 'SYSTEM: 2 type select, special tables, identical to index_config table2index',
            'config' => array(
                'type' => 'select',
                'items' => array(
                    array('dummy extra entry', '0')
                ),
                'special' => 'tables',
                'size' => 1, // @todo size & maxitems probably obsolete, see example below
                'maxitems' => 1,
            ),
        ),
        'system_3' => array(
            'exclude' => 1,
            'label' => 'SYSTEM: 3 type select, special tables, identical to sys_collection table_name',
            'config' => array(
                'type' => 'select',
                'special' => 'tables',
            ),
        ),
        'system_4' => array(
            'exclude' => 1,
            'label' => 'SYSTEM: 4 type select, special languages, renderMode checkbox, identical to be_groups allowed_languages',
            'config' => array(
                'type' => 'select',
                'special' => 'languages',
                'maxitems' => 1000,
                'renderMode' => 'checkbox',
            ),
        ),
        'system_5' => array(
            'exclude' => 1,
            'label' => 'SYSTEM: 5 type select, special custom, renderMode checkbox, identical to be_groups custom_options',
            'config' => array(
                'type' => 'select',
                'special' => 'custom',
                'maxitems' => 1000,
                'renderMode' => 'checkbox',
            ),
        ),
        'system_6' => array(
            'exclude' => 1,
            'label' => 'SYSTEM: 6 type select, special custom, renderMode checkbox, identical to be_groups custom',
            'config' => array(
                'type' => 'select',
                'special' => 'custom',
                'maxitems' => 1000,
                'renderMode' => 'checkbox',
            ),
        ),
        'system_7' => array(
            'exclude' => 1,
            'label' => 'SYSTEM: 7 type select, special modListGroup, renderMode checkbox, identical to be_groups groupMods',
            'config' => array(
                'type' => 'select',
                'special' => 'modListGroup',
                'size' => '5',
                'autoSizeMax' => 50,
                'maxitems' => 100,
                'renderMode' => 'checkbox',
                'iconsInOptionTags' => 1,
            ),
        ),
        'system_8' => array(
            'exclude' => 1,
            'label' => 'SYSTEM: 8 type select, special modListUser, renderMode checkbox, identical to be_users userMods',
            'config' => array(
                'type' => 'select',
                'special' => 'modListUser',
                'size' => '5',
                'autoSizeMax' => 50,
                'maxitems' => '100',
                'renderMode' => 'checkbox',
                'iconsInOptionTags' => 1
            ),
        ),
        'system_9' => array(
            'exclude' => 1,
            'label' => 'SYSTEM: 9 type select, special pagetypes, renderMode checkbox, identical to be_groups pagetypes_select',
            'config' => array(
                'type' => 'select',
                'special' => 'pagetypes',
                'size' => '5',
                'autoSizeMax' => 50,
                'maxitems' => 20,
                'renderMode' => 'checkbox',
                'iconsInOptionTags' => 1,
            ),
        ),
        'system_10' => array(
            'exclude' => 1,
            'label' => 'SYSTEM: 10 type select, special explicitValues, renderMode checkbox, identical to be_groups explicit_allowdeny',
            'config' => array(
                'type' => 'select',
                'special' => 'explicitValues',
                'maxitems' => 1000,
                'renderMode' => 'checkbox',
            ),
        ),
        'system_11' => array(
            'exclude' => 1,
            'label' => 'SYSTEM: 11 type select, special exclude, renderMode checkbox, identical to be_groups non_exclude_fields',
            'config' => array(
                'type' => 'select',
                'special' => 'exclude',
                'size' => '25',
                'maxitems' => 1000,
                'autoSizeMax' => 50,
                'renderMode' => 'checkbox'
            ),
        ),

    ),


    'interface' => array(
        'showRecordFieldList' => 'hidden,starttime,endtime,
			type_field,
			input_1, input_2, input_3, input_4, input_5, input_6, input_7, input_8, input_9, input_10,
			input_11, input_12, input_13, input_14, input_15, input_16, input_18, input_19, input_20,
			input_21, input_22, input_23, input_24, input_25, input_26, input_27, input_28, input_29, input_30,
			input_32, input_33, input_34, input_36,
			text_1, text_2, text_3, text_4, text_5, text_6, text_8, text_9, text_10,
			text_11, text_12, text_13,text_14, text_15,
			checkbox_1, checkbox_2, checkbox_3, checkbox_4, checkbox_5, checkbox_6, checkbox_7, checkbox_8, checkbox_9, checkbox_10,
			checkbox_11, checkbox_12, checkbox_13, checkbox_14, checkbox_15, checkbox_16, checkbox_17, checkbox_18,
			radio_1, radio_2, radio_3, radio_4, radio_5, radio_6,
			select_1, select_2, select_3, select_4, select_5, select_6, select_7, select_8, select_9, select_10,
			select_11, select_12, select_13, select_14, select_15, select_16,
			select_21, select_22, select_23, select_24, select_25, select_26, select_27, select_28, select_29,
			select_30, select_31, select_32, select_33, select_34,
			group_1, group_2, group_3, group_4, group_5, group_6, group_7, group_8, group_9, group_10,
			group_11, group_12, group_13,
			none_1, none_2, none_3, none_4, none_5, none_6,
			passthrough_1,
			user_1, user_2,
			flex_1, flex_2, flex_3,
			inline_1, inline_2, inline_3, inline_4, inline_5,
			wizard_1, wizard_2, wizard_3, wizard_4, wizard_5, wizard_6, wizard_7,
			rte_1, rte_2, rte_3, rte_4,
			t3editor_1, t3editor_2, t3editor_5, t3editor_6,
			system_1, system_2, system_3, system_4, system_5, system_6, system_7, system_8, system_9, system_10,
			system_11,
			',
    ),

    'types' => array(
        '0' => array(
            'showitem' => '
				--div--;Type,
					type_field,
				--div--;Input,
					input_1, input_28, input_29, input_2, input_3, input_4, input_5, input_6, input_7, input_8, input_9,
					input_27, input_10, input_11, input_12, input_13, input_14, input_15, input_16, input_18,
					input_19, input_20, input_21, input_22, input_23, input_24, input_25, input_26, input_30,
					input_32, input_33, input_34, input_36,
				--div--;Text,
					text_1, text_2, text_3, text_4, text_5, text_6, text_8, text_9,
					text_10, text_11, text_12, text_13, text_14, text_15,
				--div--;Check,
					checkbox_1, checkbox_2, checkbox_3, checkbox_4, checkbox_5, checkbox_6, checkbox_7, checkbox_8, checkbox_9,
					checkbox_10, checkbox_11, checkbox_12, checkbox_13, checkbox_14, checkbox_15, checkbox_16, checkbox_17, checkbox_18,
				--div--;Radio,
					radio_1, radio_2, radio_3, radio_4, radio_5, radio_6,
				--div--;Select,
					select_1, select_2, select_33, select_34, select_3, select_4, select_5, select_6, select_7, select_8, select_9, select_10,
					select_11, select_12, select_13, select_14, select_15, select_16, select_17,
					select_21, select_22, select_23, select_24, select_25, select_26, select_27, select_28, select_29,
					select_30, select_31, select_32,
				--div--;Group,
					group_1, group_12, group_2, group_3, group_4, group_5, group_6, group_7, group_8, group_9, group_10,
					group_11, group_13,
				--div--;Passthrough,
					passthrough_1,
				--div--;None,
					none_1, none_2, none_3, none_4, none_5, none_6,
				--div--;User,
					user_1, user_2,
				--div--;Flex,
					flex_1, flex_2, flex_3, flex_4, flex_5,
				--div--;Inline,
					inline_1, inline_2, inline_3, inline_4, inline_5,
				--div--;Palettes,
					--palette--;Palettes 1;palettes_1,
					--palette--;Palettes 2;palettes_2,
					--palette--;Palettes 3;palettes_3,
					--palette--;;palettes_4,
					--palette--;Palettes 5;palettes_5,
					palette_6_1;Field with palette below, --palette--;;palettes_6,
				--div--;Wizards,
					wizard_1, wizard_2, wizard_3, wizard_7, wizard_4, wizard_5, wizard_6,
				--div--;RTE,
					rte_1, --palette--;RTE in palette;rte_2_palette, rte_3, rte_4,
				--div--;t3editor,
					t3editor_1, t3editor_2, t3editor_5, t3editor_6,
				--div--;Access Rights,
					system_1, system_2, system_3, system_4, system_5, system_6, system_7, system_8, system_9, system_10,
					system_11,
			',
            'columnsOverrides' => array(
                't3editor_2' => array(
                    'config' => array(
                        'renderType' => 't3editor',
                        'format' => 'html',
                    ),
                ),
            ),
        ),
        'test' => array(
            'showitem' => '
				--div--;Type,
					type_field,
				--div--;t3editor,
					t3editor_2;T3EDITOR: 2 Should be usual text field,
			',
        ),
    ),

    'palettes' => array(
        'palettes_1' => array(
            'showitem' => 'palette_1_1, palette_1_2, palette_1_3',
            'canNotCollapse' => 1,
        ),
        'palettes_2' => array(
            'showitem' => 'palette_2_1',
        ),
        'palettes_3' => array(
            'showitem' => 'palette_3_1, palette_3_2',
        ),
        'palettes_4' => array(
            'showitem' => 'palette_4_1, palette_4_2, palette_4_3, --linebreak--, palette_4_4',
        ),
        'palettes_5' => array(
            'showitem' => 'palette_5_1, --linebreak--, palette_5_2',
            'canNotCollapse' => 1,
        ),
        'palettes_6' => array(
            'showitem' => 'palette_6_2, palette_6_3',
        ),
        'rte_2_palette' => array(
            'showitem' => 'rte_2',
        ),
        'visibility' => array(
            'showitem' => 'hidden;Shown in frontend',
            'canNotCollapse' => 1,
        ),
    ),

);

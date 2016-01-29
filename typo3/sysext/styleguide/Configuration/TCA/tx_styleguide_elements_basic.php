<?php
return [
    'ctrl' => [
        'title' => 'Form engine fields - input, text, checkbox, radio, none, passthrough, user',
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
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
    ],


    'columns' => [


        'hidden' => [
            'exclude' => 1,
            'config' => [
                'type' => 'check',
                'items' => [
                    '1' => [
                        '0' => 'Disable',
                    ],
                ],
            ],
        ],
        'starttime' => [
            'exclude' => 1,
            'label' => 'Publish Date',
            'config' => [
                'type' => 'input',
                'size' => '13',
                'max' => '20',
                'eval' => 'datetime',
                'default' => '0'
            ],
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly'
        ],
        'endtime' => [
            'exclude' => 1,
            'label' => 'Expiration Date',
            'config' => [
                'type' => 'input',
                'size' => '13',
                'max' => '20',
                'eval' => 'datetime',
                'default' => '0',
                'range' => [
                    'upper' => mktime(0, 0, 0, 12, 31, 2020)
                ]
            ],
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly'
        ],


        'input_1' => [
            'exclude' => 1,
            'label' => 'input_1',
            'config' => [
                'type' => 'input',
            ],
        ],
        'input_2' => [
            'exclude' => 1,
            'label' => 'input_2, size=10',
            'config' => [
                'type' => 'input',
                'size' => 10,
            ],
        ],
        'input_3' => [
            'exclude' => 1,
            'label' => 'input_3 max=4',
            'config' => [
                'type' => 'input',
                'max' => 4,
            ],
        ],
        'input_4' => [
            'exclude' => 1,
            'label' => 'input_4 default=Default value"',
            'config' => [
                'type' => 'input',
                'default' => 'Default value',
            ],
        ],
        'input_5' => [
            'exclude' => 1,
            'label' => 'input_5 eval=alpha',
            'config' => [
                'type' => 'input',
                'eval' => 'alpha',
            ],
        ],
        'input_6' => [
            'exclude' => 1,
            'label' => 'input_6 eval=alphanum',
            'config' => [
                'type' => 'input',
                'eval' => 'alphanum',
            ],
        ],
        'input_7' => [
            'exclude' => 1,
            'label' => 'input_7 eval=date',
            'config' => [
                'type' => 'input',
                'eval' => 'date',
            ],
        ],
        'input_8' => [
            'exclude' => 1,
            'label' => 'input_8 eval=datetime',
            'config' => [
                'type' => 'input',
                'eval' => 'datetime',
            ],
        ],
        'input_9' => [
            'exclude' => 1,
            'label' => 'input_9 eval=double2',
            'config' => [
                'type' => 'input',
                'eval' => 'double2',
            ],
        ],
        'input_10' => [
            'exclude' => 1,
            'label' => 'input_10 eval=int',
            'config' => [
                'type' => 'input',
                'eval' => 'int',
            ],
        ],
        'input_11' => [
            'exclude' => 1,
            'label' => 'input_11 eval=is_in, is_in=abc123',
            'config' => [
                'type' => 'input',
                'eval' => 'is_in',
                'is_in' => 'abc123',
            ],
        ],
        'input_12' => [
            'exclude' => 1,
            'label' => 'input_12 eval=lower',
            'config' => [
                'type' => 'input',
                'eval' => 'lower',
            ],
        ],
        'input_13' => [
            'exclude' => 1,
            'label' => 'input_13 eval=md5',
            'config' => [
                'type' => 'input',
                'eval' => 'md5',
            ],
        ],
        'input_14' => [
            'exclude' => 1,
            'label' => 'input_14 eval=nospace',
            'config' => [
                'type' => 'input',
                'eval' => 'nospace',
            ],
        ],
        'input_15' => [
            'exclude' => 1,
            'label' => 'input_15 eval=null',
            'config' => [
                'type' => 'input',
                'eval' => 'null',
            ],
        ],
        'input_16' => [
            'exclude' => 1,
            'label' => 'input_16 eval=num',
            'config' => [
                'type' => 'input',
                'eval' => 'num',
            ],
        ],
        'input_17' => [
            'exclude' => 1,
            'label' => 'input_17 eval=password',
            'config' => [
                'type' => 'input',
                'eval' => 'password',
            ],
        ],
        'input_18' => [
            'exclude' => 1,
            'label' => 'input_18 eval=time',
            'config' => [
                'type' => 'input',
                'eval' => 'time',
            ],
        ],
        'input_19' => [
            'exclude' => 1,
            'label' => 'input_19 eval=timesec',
            'config' => [
                'type' => 'input',
                'eval' => 'timesec',
            ],
        ],
        'input_20' => [
            'exclude' => 1,
            'label' => 'input_20 eval=trim',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
            ],
        ],
        'input_21' => [
            'exclude' => 1,
            'label' => 'input_21 eval with user function',
            'config' => [
                'type' => 'input',
                'eval' => 'TYPO3\\CMS\\Styleguide\\UserFunctions\\FormEngine\\TypeInput21Eval',
            ],
        ],
        'input_22' => [
            'exclude' => 1,
            'label' => 'input_22 eval=unique',
            'config' => [
                'type' => 'input',
                'eval' => 'unique',
            ],
        ],
        'input_23' => [
            'exclude' => 1,
            'label' => 'input_23 eval=uniqueInPid',
            'config' => [
                'type' => 'input',
                'eval' => 'uniqueInPid',
            ],
        ],
        'input_24' => [
            'exclude' => 1,
            'label' => 'input_24 eval=upper',
            'config' => [
                'type' => 'input',
                'eval' => 'upper',
            ],
        ],
        'input_25' => [
            'exclude' => 1,
            'label' => 'input_25 eval=year',
            'config' => [
                'type' => 'input',
                'eval' => 'year',
            ],
        ],
        'input_26' => [
            'exclude' => 1,
            'label' => 'input_26 eval=datetime, readonly=1, size=12',
            'config' => [
                'type' => 'input',
                'readOnly' => 1,
                'size' => 12,
                'eval' => 'datetime',
                'default' => 0,
            ],
        ],
        'input_27' => [
            'exclude' => 1,
            'label' => 'input_27 eval=int, default=3, range lower=2, range upper=7',
            'config' => [
                'type' => 'input',
                'eval' => 'int',
                'range' => [
                    'lower' => 2,
                    'upper' => 7,
                ],
                'default' => 3,
            ],
        ],
        'input_28' => [
            'exclude' => 1,
            'label' => 'input_28 default="input_28"',
            'config' => [
                'type' => 'input',
                'default' => 'input_28',
            ],
        ],
        'input_29' => [
            'exclude' => 1,
            'label' => 'input_29 placeholder=__row|input_28',
            'config' => [
                'type' => 'input',
                'placeholder' => '__row|input_28',
            ],
        ],
        'input_30' => [
            'exclude' => 1,
            'label' => 'input_30 placeholder=__row|input_28, mode=useOrOverridePlaceholder, eval=null',
            'config' => [
                'type' => 'input',
                'placeholder' => '__row|input_28',
                'eval' => 'null',
                'mode' => 'useOrOverridePlaceholder',
            ],
        ],
        'input_31' => [
            'exclude' => 1,
            'label' => 'input_31 wizard link',
            'config' => [
                'type' => 'input',
                'wizards' => [
                    'link' => [
                        'type' => 'popup',
                        'title' => 'Link',
                        'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_link.gif',
                        'module' => [
                            'name' => 'wizard_link',
                        ],
                        'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1',
                    ],
                ],
            ],
        ],
        'input_32' => [
            'exclude' => 1,
            'label' => 'input_32 wizard slider, step=10, width=200, eval=trim,int',
            'config' => [
                'type' => 'input',
                'size' => 5,
                'eval' => 'trim,int',
                'range' => [
                    'lower' => -90,
                    'upper' => 90,
                ],
                'default' => 0,
                'wizards' => [
                    'angle' => [
                        'type' => 'slider',
                        'step' => 10,
                        'width' => 200,
                    ],
                ],
            ],
        ],
        'input_33' => [
            'exclude' => 1,
            'label' => 'input_33 wizard slider, default=14.5, step=0.5, width=150, eval=trim,double2',
            'config' => [
                'type' => 'input',
                'size' => 5,
                'eval' => 'trim,double2',
                'range' => [
                    'lower' => -90,
                    'upper' => 90,
                ],
                'default' => 14.5,
                'wizards' => [
                    'angle' => [
                        'type' => 'slider',
                        'step' => 0.5,
                        'width' => 150,
                    ],
                ],
            ],
        ],
        'input_34' => [
            'exclude' => 1,
            'label' => 'input_34 wizard userFunc',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'int',
                'wizards' => [
                    'userFuncInputWizard' => [
                        'type' => 'userFunc',
                        'userFunc' => 'TYPO3\\CMS\\Styleguide\\UserFunctions\\FormEngine\\WizardInput33->render',
                        'params' => [
                            'color' => 'green',
                        ],
                    ],
                ],
            ],
        ],
        'input_35' => [
            'exclude' => 1,
            'label' => 'input_35 wizard select',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim',
                'wizards' => [
                    'season_picker' => [
                        'type' => 'select',
                        'mode' => '',
                        'items' => [
                            ['spring', 'Spring'],
                            ['summer', 'Summer'],
                            ['autumn', 'Autumn'],
                            ['winter', 'Winter'],
                        ],
                    ],
                ],
            ],
        ],


        'text_1' => [
            'exclude' => 1,
            'label' => 'text_1',
            'config' => [
                'type' => 'text',
            ],
        ],
        'text_2' => [
            'exclude' => 1,
            'label' => 'text_2 cols=20',
            'config' => [
                'type' => 'text',
                'cols' => 20,
            ],
        ],
        'text_3' => [
            'exclude' => 1,
            'label' => 'text_3 rows=2',
            'config' => [
                'type' => 'text',
                'rows' => 2,
            ],
        ],
        'text_4' => [
            'exclude' => 1,
            'label' => 'text_4 cols=20, rows=2',
            'config' => [
                'type' => 'text',
                'cols' => 20,
                'rows' => 2,
            ],
        ],
        'text_5' => [
            'exclude' => 1,
            'label' => 'text_5 wrap=off, long default text',
            'config' => [
                'type' => 'text',
                'wrap' => 'off',
                'default' => 'This textbox has wrap set to "off", so these long paragraphs should appear in one line:'
                    . ' Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean non luctus elit. In sed nunc velit.'
                    . ' Donec gravida eros sollicitudin ligula mollis id eleifend mauris laoreet. Donec turpis magna, pulvinar'
                    . ' id pretium eu, blandit et nisi. Nulla facilisi. Vivamus pharetra orci sed nunc auctor condimentum.'
                    . ' Aenean volutpat posuere scelerisque. Nullam sed dolor justo. Pellentesque id tellus nunc, id sodales'
                    . ' diam. Sed rhoncus risus a enim lacinia tincidunt. Aliquam ut neque augue.',
            ],
        ],
        'text_6' => [
            'exclude' => 1,
            'label' => 'text_6 wrap=virtual, long default text',
            'config' => [
                'type' => 'text',
                'wrap' => 'virtual',
                'default' => 'This textbox has wrap set to "virtual", so these long paragraphs should'
                    . ' appear in multiple lines (wrapped at the end of the textbox):'
                    . ' Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean non luctus elit. In sed nunc velit.'
                    . ' Donec gravida eros sollicitudin ligula mollis id eleifend mauris laoreet. Donec turpis magna, pulvinar'
                    . ' id pretium eu, blandit et nisi. Nulla facilisi. Vivamus pharetra orci sed nunc auctor condimentum.'
                    . ' Aenean volutpat posuere scelerisque. Nullam sed dolor justo. Pellentesque id tellus nunc, id sodales'
                    . ' diam. Sed rhoncus risus a enim lacinia tincidunt. Aliquam ut neque augue.',
            ],
        ],
        'text_7' => [
            'exclude' => 1,
            'label' => 'text_7 eval=trim',
            'config' => [
                'type' => 'text',
                'eval' => 'trim',
            ],
        ],
        'text_8' => [
            'exclude' => 1,
            'label' => 'text_8 eval with user function',
            'config' => [
                'type' => 'text',
                'eval' => 'TYPO3\\CMS\\Styleguide\\UserFunctions\\FormEngine\\TypeText9Eval',
            ],
        ],
        'text_9' => [
            'exclude' => 1,
            'label' => 'text_9 readOnly=1',
            'config' => [
                'type' => 'text',
                'readOnly' => 1,
            ],
        ],
        'text_10' => [
            'exclude' => 1,
            'label' => 'text_10 readOnly=1, format=datetime',
            'config' => [
                'type' => 'text',
                'readOnly' => 1,
                'format' => 'datetime',
            ],
        ],
        'text_11' => [
            'exclude' => 1,
            'label' => 'text_11 max=30',
            'config' => [
                'type' => 'text',
                'cols' => 30,
                'rows' => 4,
                'max' => 30,
            ],
        ],
        'text_12' => [
            'exclude' => 1,
            'label' => 'text_12 default="text_12"',
            'config' => [
                'type' => 'text',
                'default' => 'text_12',
            ],
        ],
        'text_13' => [
            'exclude' => 1,
            'label' => 'text_13 placeholder=__row|text_12',
            'config' => [
                'type' => 'text',
                'placeholder' => '__row|text_12',
            ],
        ],
        'text_14' => [
            'exclude' => 1,
            'label' => 'text_14 placeholder=__row|text_12, mode=useOrOverridePlaceholder, eval=null',
            'config' => [
                'type' => 'text',
                'placeholder' => '__row|text_12',
                'eval' => 'null',
                'mode' => 'useOrOverridePlaceholder',
            ],
        ],
        'text_15' => [
            'exclude' => 1,
            'label' => 'text_15 defaultExtras="fixed-font : enable-tab"',
            'config' => [
                'type' => 'text',
            ],
            'defaultExtras' => 'fixed-font : enable-tab'
        ],


        'checkbox_1' => [
            'exclude' => 1,
            'label' => 'checkbox_1',
            'config' => [
                'type' => 'check',
            ]
        ],
        'checkbox_2' => [
            'exclude' => 1,
            'label' => 'checkbox_2 default=1',
            'config' => [
                'type' => 'check',
                'default' => 1,
            ]
        ],
        'checkbox_3' => [
            'exclude' => 1,
            'label' => 'checkbox_3 One checkbox with label',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['foo', ''],
                ],
            ]
        ],
        'checkbox_4' => [
            'exclude' => 1,
            'label' => 'checkbox_4 One checkbox with label, pre-selected',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['foo', ''],
                ],
                'default' => 1
            ]
        ],
        'checkbox_5' => [
            'exclude' => 1,
            'label' => 'checkbox_5 Three checkboxes, two with labels, one without',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['foo', ''],
                    ['', ''],
                    ['foobar', ''],
                ],
            ],
        ],
        'checkbox_6' => [
            'exclude' => 1,
            'label' => 'checkbox_6 Four checkboxes with labels, 1 and 3 pre-selected, long text',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['foo', ''],
                    [
                        'foo and this here is very long text that maybe does not really fit into the form in one line.'
                            . ' Ok let us add even more text to see how this looks like if wrapped. Is this enough now? No?'
                            . ' Then let us add some even more useless text here!',
                        ''
                    ],
                    ['foobar', ''],
                    ['foobar', ''],
                ],
                'default' => 5,
            ],
        ],
        'checkbox_7' => [
            'exclude' => 1,
            'label' => 'checkbox_7 showIfRTE=1 (only shown if RTE is enabled for user)',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['foo', ''],
                ],
                'showIfRTE' => 1,
            ],
        ],
        'checkbox_8' => [
            // @todo: Checking a checkbox that is added by itemsProcFunc is not persisted correctly.
            // @todo: HTML looks good, so this is probably an issue in DataHandler?
            'label' => 'checkbox_8 itemsProcFunc',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['foo', ''],
                    ['bar', ''],
                ],
                'itemsProcFunc' => 'TYPO3\\CMS\\Styleguide\\UserFunctions\\FormEngine\\TypeCheckbox8ItemsProcFunc->itemsProcFunc',
            ],
        ],
        'checkbox_9' => [
            'exclude' => 1,
            'label' => 'checkbox_9 eval=maximumRecordsChecked, table wide',
            'config' => [
                'type' => 'check',
                'eval' => 'maximumRecordsChecked',
                'validation' => [
                    'maximumRecordsChecked' => 1,
                ],
            ],
        ],
        'checkbox_10' => [
            'exclude' => 1,
            'label' => 'checkbox_10 eval=maximumRecordsCheckedInPid, for this PID',
            'config' => [
                'type' => 'check',
                'eval' => 'maximumRecordsCheckedInPid',
                'validation' => [
                    'maximumRecordsCheckedInPid' => 1,
                ],
            ],
        ],
        'checkbox_11' => [
            'exclude' => 1,
            'label' => 'checkbox_11 readonly=1',
            'config' => [
                'type' => 'check',
                'readOnly' => 1,
                'items' => [
                    ['foo1', ''],
                    ['foo2', ''],
                ],
            ],
        ],
        'checkbox_12' => [
            'exclude' => 1,
            'label' => 'checkbox_12 cols=1',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['foo1', ''],
                    ['foo2', ''],
                    ['foo3', ''],
                ],
                'cols' => '1',
            ],
        ],
        'checkbox_13' => [
            'exclude' => 1,
            'label' => 'checkbox_13 cols=2',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['foo1', ''],
                    ['foo2', ''],
                    ['foo3', ''],
                ],
                'cols' => '2',
            ],
        ],
        'checkbox_14' => [
            'exclude' => 1,
            'label' => 'checkbox_14 cols=3',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['foo1', ''],
                    ['foo2', ''],
                    ['foo3', ''],
                    ['foo4', ''],
                ],
                'cols' => '3',
            ],
        ],
        'checkbox_15' => [
            'exclude' => 1,
            'label' => 'checkbox_15 cols=4',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['foo1', ''],
                    ['foo2', ''],
                    [
                        'foo3 and this here is very long text that maybe does not really fit into the'
                            . ' form in one line. Ok let us add even more text to see how',
                        ''
                    ],
                    ['foo4', ''],
                    ['foo5', ''],
                    ['foo6', ''],
                    ['foo7', ''],
                    ['foo8', ''],
                ],
                'cols' => '4',
            ],
        ],
        'checkbox_16' => [
            'exclude' => 1,
            'label' => 'checkbox_16 cols=5',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['foo1', ''],
                    ['foo2', ''],
                    ['foo3', ''],
                    ['foo4', ''],
                    ['foo5', ''],
                    ['foo6', ''],
                    ['foo7', ''],
                ],
                'cols' => '5',
            ],
        ],
        'checkbox_17' => [
            'exclude' => 1,
            'label' => 'checkbox_17 cols=6',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['foo1', ''],
                    ['foo2', ''],
                    ['foo3', ''],
                    ['foo4', ''],
                    ['foo5', ''],
                    ['foo6', ''],
                    ['foo7', ''],
                ],
                'cols' => '6',
            ],
        ],
        'checkbox_18' => [
            'exclude' => 1,
            'label' => 'checkbox_18 cols=inline',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['Mo', ''],
                    ['Tu', ''],
                    ['We', ''],
                    ['Th', ''],
                    ['Fr', ''],
                    ['Sa', ''],
                    ['Su', ''],
                ],
                'cols' => 'inline',
            ],
        ],


        'radio_1' => [
            'exclude' => 1,
            'label' => 'radio_1 Three options, one without label',
            'config' => [
                'type' => 'radio',
                'items' => [
                    ['foo', 1],
                    ['', 2],
                    ['foobar', 3],
                ],
            ],
        ],
        'radio_2' => [
            'exclude' => 1,
            'label' => 'radio_2 Three options, second pre-selected, long text',
            'config' => [
                'type' => 'radio',
                'items' => [
                    [
                        'foo and this here is very long text that maybe does not really fit into the form in one line.'
                            . ' Ok let us add even more text to see how this looks like if wrapped. Is this enough now?'
                            . ' No? Then let us add some even more useless text here!',
                        1
                    ],
                    ['bar', 2],
                    ['foobar', 3],
                ],
                'default' => 2,
            ],
        ],
        'radio_3' => [
            'exclude' => 1,
            'label' => 'radio_3 Many options',
            'config' => [
                'type' => 'radio',
                'items' => [
                    ['foo1', 1],
                    ['foo2', 2],
                    ['foo3', 3],
                    ['foo4', 4],
                    ['foo5', 5],
                    ['foo6', 6],
                    ['foo7', 7],
                    ['foo8', 8],
                    ['foo9', 9],
                    ['foo10', 10],
                    ['foo11', 11],
                    ['foo12', 12],
                    ['foo13', 13],
                    ['foo14', 14],
                    ['foo15', 15],
                    ['foo16', 16],
                ],
            ],
        ],
        'radio_4' => [
            'exclude' => 1,
            'label' => 'radio_4 String values',
            'config' => [
                'type' => 'radio',
                'items' => [
                    ['foo', 'foo'],
                    ['bar', 'bar'],
                ],
            ],
        ],
        'radio_5' => [
            // @todo: Radio elements added by itemsProcFunc are not persisted correctly.
            // @todo: HTML looks good, so this is probably an issue in DataHandler?
            'exclude' => 1,
            'label' => 'radio_5 itemsProcFunc',
            'config' => [
                'type' => 'radio',
                'items' => [
                    ['foo', 1],
                    ['bar', 2],
                ],
                'itemsProcFunc' => 'TYPO3\\CMS\\Styleguide\\UserFunctions\\FormEngine\\TypeRadio5ItemsProcFunc->itemsProcFunc',
            ],
        ],
        'radio_6' => [
            'exclude' => 1,
            'label' => 'radio_6 readonly=1',
            'config' => [
                'type' => 'radio',
                'readOnly' => 1,
                'items' => [
                    ['foo', 'foo'],
                    ['bar', 'bar'],
                ],
            ],
        ],


         // @todo Add default rows to show all the format options of type=none
        'none_1' => [
            'exclude' => 1,
            'label' => 'none_1 pass_content=1',
            'config' => [
                'type' => 'none',
                'pass_content' => 1,
            ],
        ],
        'none_2' => [
            'exclude' => 1,
            'label' => 'none_2 pass_content=0',
            'config' => [
                'type' => 'none',
                'pass_content' => 0,
            ],
        ],
        'none_3' => [
            'exclude' => 1,
            'label' => 'none_3 rows=2',
            'config' => [
                'type' => 'none',
                'rows' => 2,
            ],
        ],
        'none_4' => [
            'exclude' => 1,
            'label' => 'none_4 cols=2',
            'config' => [
                'type' => 'none',
                'cols' => 2,
            ],
        ],
        'none_5' => [
            'exclude' => 1,
            'label' => 'none_5 rows=2, fixedRows=2',
            'config' => [
                'type' => 'none',
                'rows' => 2,
                'fixedRows' => 2,
            ],
        ],
        'none_6' => [
            'exclude' => 1,
            'label' => 'none_6 size=6',
            'config' => [
                'type' => 'none',
                'size' => 6,
            ],
        ],


        'passthrough_1' => [
            'exclude' => 1,
            'label' => 'passthrough_1 field should NOT be shown',
            'config' => [
                'type' => 'passthrough',
            ],
        ],


        'user_1' => [
            'exclude' => 1,
            'label' => 'user_1 parameter=color=green',
            'config' => [
                'type' => 'user',
                'userFunc' => 'TYPO3\\CMS\\Styleguide\\UserFunctions\\FormEngine\\TypeUser1->render',
                'parameters' => [
                    'color' => 'green',
                ],
            ],
        ],
        'user_2' => [
            'exclude' => 1,
            'label' => 'user_2 noTableWrapping=true',
            'config' => [
                'type' => 'user',
                'userFunc' => 'TYPO3\\CMS\\Styleguide\\UserFunctions\\FormEngine\\TypeUser2->render',
                'parameters' => [
                    'color' => 'green',
                ],
                'noTableWrapping' => true,
            ],
        ],


    ],


    'types' => [
        '0' => [
            'showitem' => '
                --div--;input,
                    input_1, input_2, input_3, input_4, input_5, input_6, input_7, input_8, input_9, input_10,
                    input_11, input_12, input_13, input_14, input_15, input_16, input_17, input_18, input_19, input_20,
                    input_21, input_22, input_23, input_24, input_25, input_26, input_27, input_28, input_29, input_30,
                    input_31, input_32, input_33, input_34, input_35,
                --div--;text,
                    text_1, text_2, text_3, text_4, text_5, text_6, text_8, text_9, text_10,
                    text_11, text_12, text_13, text_14, text_15,
                --div--;check,
                    checkbox_1, checkbox_2, checkbox_3, checkbox_4, checkbox_5, checkbox_6, checkbox_7, checkbox_8, checkbox_9, checkbox_10,
                    checkbox_11, checkbox_12, checkbox_13, checkbox_14, checkbox_15, checkbox_16, checkbox_17, checkbox_18,
                --div--;radio,
                    radio_1, radio_2, radio_3, radio_4, radio_5, radio_6,
                --div--;none,
                    none_1, none_2, none_3, none_4, none_5, none_6,
                --div--;passthrough,
                    passthrough_1,
                --div--;user,
                    user_1, user_2,
            ',
        ],
    ],


];

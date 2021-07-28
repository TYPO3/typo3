<?php

return [
    'ctrl' => [
        'title' => 'Form engine elements - input, text, checkbox, radio, none, passthrough, user',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'translationSource' => 'l10n_source',
        'enablecolumns' => [
            'disabled' => 'hidden',
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
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language'
            ]
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'Translation parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        '',
                        0
                    ]
                ],
                'foreign_table' => 'tx_styleguide_elements_basic',
                'foreign_table_where' => 'AND {#tx_styleguide_elements_basic}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_elements_basic}.{#sys_language_uid} IN (-1,0)',
                'default' => 0
            ]
        ],
        'l10n_source' => [
            'exclude' => true,
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'Translation source',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        '',
                        0
                    ]
                ],
                'foreign_table' => 'tx_styleguide_elements_basic',
                'foreign_table_where' => 'AND {#tx_styleguide_elements_basic}.{#pid}=###CURRENT_PID### AND {#tx_styleguide_elements_basic}.{#uid}!=###THIS_UID###',
                'default' => 0
            ]
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
                'default' => ''
            ]
        ],

        'input_1' => [
            'l10n_mode' => 'prefixLangTitle',
            'exclude' => 1,
            'label' => 'input_1 description',
            'description' => 'field description',
            'config' => [
                'type' => 'input',
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ]
            ],
        ],
        'input_2' => [
            'l10n_mode' => 'prefixLangTitle',
            'exclude' => 1,
            'label' => 'input_2',
            'description' => 'size=10',
            'config' => [
                'type' => 'input',
                'size' => 10,
            ],
        ],
        'input_3' => [
            'exclude' => 1,
            'label' => 'input_3',
            'description' => 'max=4',
            'config' => [
                'type' => 'input',
                'max' => 4,
            ],
        ],
        'input_4' => [
            'exclude' => 1,
            'label' => 'input_4',
            'description' => 'eval=alpha',
            'config' => [
                'type' => 'input',
                'eval' => 'alpha',
            ],
        ],
        'input_5' => [
            'exclude' => 1,
            'label' => 'input_5',
            'description' => 'eval=alphanum',
            'config' => [
                'type' => 'input',
                'eval' => 'alphanum',
            ],
        ],
        'input_8' => [
            'exclude' => 1,
            'label' => 'input_8',
            'description' => 'eval=double2',
            'config' => [
                'type' => 'input',
                'eval' => 'double2',
            ],
        ],
        'input_9' => [
            'exclude' => 1,
            'label' => 'input_9',
            'description' => 'eval=int',
            'config' => [
                'type' => 'input',
                'eval' => 'int',
            ],
        ],
        'input_10' => [
            'exclude' => 1,
            'label' => 'input_10',
            'description' => 'eval=is_in is_in=abc123',
            'config' => [
                'type' => 'input',
                'eval' => 'is_in',
                'is_in' => 'abc123',
            ],
        ],
        'input_11' => [
            'exclude' => 1,
            'label' => 'input_11',
            'description' => 'eval=lower',
            'config' => [
                'type' => 'input',
                'eval' => 'lower',
            ],
        ],
        'input_12' => [
            'exclude' => 1,
            'label' => 'input_12',
            'description' => 'eval=md5',
            'config' => [
                'type' => 'input',
                'eval' => 'md5',
            ],
        ],
        'input_13' => [
            'exclude' => 1,
            'label' => 'input_13',
            'description' => 'eval=nospace',
            'config' => [
                'type' => 'input',
                'eval' => 'nospace',
            ],
        ],
        'input_14' => [
            'exclude' => 1,
            'label' => 'input_14',
            'description' => 'eval=null',
            'config' => [
                'type' => 'input',
                'eval' => 'null',
            ],
        ],
        'input_15' => [
            'exclude' => 1,
            'label' => 'input_15',
            'description' => 'eval=num',
            'config' => [
                'type' => 'input',
                'eval' => 'num',
            ],
        ],
        'input_16' => [
            'exclude' => 1,
            'label' => 'input_16',
            'description' => 'eval=password',
            'config' => [
                'type' => 'input',
                'eval' => 'password',
            ],
        ],
        'input_19' => [
            'exclude' => 1,
            'label' => 'input_19',
            'description' => 'eval=trim',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
            ],
        ],
        'input_20' => [
            'exclude' => 1,
            'label' => 'input_20',
            'description' => 'eval with user function',
            'config' => [
                'type' => 'input',
                'eval' => 'TYPO3\\CMS\\Styleguide\\UserFunctions\\FormEngine\\TypeInput21Eval',
            ],
        ],
        'input_21' => [
            'exclude' => 1,
            'label' => 'input_21',
            'description' => 'eval=unique',
            'config' => [
                'type' => 'input',
                'eval' => 'unique',
            ],
        ],
        'input_22' => [
            'exclude' => 1,
            'label' => 'input_22',
            'description' => 'eval=uniqueInPid',
            'config' => [
                'type' => 'input',
                'eval' => 'uniqueInPid',
            ],
        ],
        'input_23' => [
            'exclude' => 1,
            'label' => 'input_23',
            'description' => 'eval=upper',
            'config' => [
                'type' => 'input',
                'eval' => 'upper',
            ],
        ],
        'input_24' => [
            'exclude' => 1,
            'label' => 'input_24',
            'description' => 'eval=year',
            'config' => [
                'type' => 'input',
                'eval' => 'year',
            ],
        ],
        'input_25' => [
            'exclude' => 1,
            'label' => 'input_25',
            'description' => 'eval=int default=0 range lower=-2 range upper=2',
            'config' => [
                'type' => 'input',
                'eval' => 'int',
                'range' => [
                    'lower' => -2,
                    'upper' => 2,
                ],
                'default' => 0,
            ],
        ],
        'input_26' => [
            'exclude' => 1,
            'label' => 'input_26',
            'description' => 'default="input_26", value for input_27 and input_28',
            'config' => [
                'type' => 'input',
                'default' => 'input_26',
            ],
        ],
        'input_27' => [
            'exclude' => 1,
            'label' => 'input_27',
            'description' => 'placeholder=__row|input_26',
            'config' => [
                'type' => 'input',
                'placeholder' => '__row|input_26',
            ],
        ],
        'input_28' => [
            'exclude' => 1,
            'label' => 'input_28',
            'description' => 'placeholder=__row|input_26 mode=useOrOverridePlaceholder eval=null default=null',
            'config' => [
                'type' => 'input',
                'placeholder' => '__row|input_26',
                'eval' => 'null',
                'default' => null,
                'mode' => 'useOrOverridePlaceholder',
            ],
        ],
        'input_29' => [
            'exclude' => 1,
            'label' => 'input_29',
            'description' => 'renderType=inputLink',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputLink',
            ],
        ],
        'input_30' => [
            'exclude' => 1,
            'label' => 'input_30',
            'description' => 'slider step=10 width=200 eval=trim,int',
            'config' => [
                'type' => 'input',
                'size' => 5,
                'eval' => 'trim,int',
                'range' => [
                    'lower' => -90,
                    'upper' => 90,
                ],
                'default' => 0,
                'slider' => [
                    'step' => 10,
                    'width' => 200,
                ],
            ],
        ],
        'input_31' => [
            'exclude' => 1,
            'label' => 'input_31',
            'description' => 'slider default=14.5 step=0.5 width=150 eval=trim,double2',
            'config' => [
                'type' => 'input',
                'size' => 5,
                'eval' => 'trim,double2',
                'range' => [
                    'lower' => -90,
                    'upper' => 90,
                ],
                'default' => 14.5,
                'slider' => [
                    'step' => 0.5,
                    'width' => 150,
                ],
            ],
        ],
        'input_32' => [
            'exclude' => 1,
            'label' => 'input_32',
            'description' => 'wizard userFunc',
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
        'input_33' => [
            'exclude' => 1,
            'label' => 'input_33',
            'description' => 'valuePicker',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim',
                'valuePicker' => [
                    'items' => [
                        [ 'spring', 'Spring'],
                        [ 'summer', 'Summer'],
                        [ 'autumn', 'Autumn'],
                        [ 'winter', 'Winter'],
                    ],
                ],
            ],
        ],
        'input_34' => [
            'exclude' => 1,
            'label' => 'input_34',
            'description' => 'renderType=colorpicker',
            'config' => [
                'type' => 'input',
                'renderType' => 'colorpicker',
                'size' => 10,
            ],
        ],
        'input_35' => [
            'exclude' => 1,
            'label' => 'input_35',
            'description' => 'valuePicker append',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim',
                'valuePicker' => [
                    'mode' => 'append',
                    'items' => [
                        [ 'spring', 'Spring'],
                        [ 'summer', 'Summer'],
                        [ 'autumn', 'Autumn'],
                        [ 'winter', 'Winter'],
                    ],
                ],
            ],
        ],
        'input_36' => [
            'exclude' => 1,
            'label' => 'input_36',
            'description' => 'valuePicker prepend',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim',
                'valuePicker' => [
                    'mode' => 'prepend',
                    'items' => [
                        [ 'spring', 'Spring'],
                        [ 'summer', 'Summer'],
                        [ 'autumn', 'Autumn'],
                        [ 'winter', 'Winter'],
                    ],
                ],
            ],
        ],
        'input_37' => [
            'exclude' => 1,
            'label' => 'input_37',
            'description' => 'renderType=colorpicker valuePicker',
            'config' => [
                'type' => 'input',
                'renderType' => 'colorpicker',
                'size' => 10,
                'valuePicker' => [
                    'items' => [
                        [ 'blue', '#0000FF'],
                        [ 'red', '#FF0000'],
                        [ 'typo3 orange', '#FF8700'],
                    ],
                ],
            ],
        ],
        'input_38' => [
            'exclude' => 1,
            'label' => 'input_38',
            'description' => 'inputLink allowedExtensions=png',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputLink',
                'fieldControl' => [
                    'linkPopup' => [
                        'options' => [
                            'allowedExtensions' => 'png',
                        ]
                    ]
                ],
            ],
        ],
        'input_39' => [
            'exclude' => 1,
            'label' => 'input_39',
            'description' => 'eval=email',
            'config' => [
                'type' => 'input',
                'eval' => 'email',
            ],
        ],
        'input_40' => [
            'exclude' => 1,
            'label' => 'input_40',
            'description' => 'readOnly',
            'config' => [
                'type' => 'input',
                'readOnly' => true,
            ],
        ],
        'input_41' => [
            'exclude' => 1,
            'label' => 'input_41',
            'description' => 'renderType=inputLink readOnly',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputLink',
                'readOnly' => true,
            ],
        ],
        'input_42' => [
            'exclude' => 1,
            'label' => 'input_42',
            'description' => 'renderType=colorpicker readOnly',
            'config' => [
                'type' => 'input',
                'renderType' => 'colorpicker',
                'readOnly' => true,
            ],
        ],

        'inputdatetime_1' => [
            'exclude' => 1,
            'label' => 'inputdatetime_1',
            'description' => 'eval=date',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'date',
            ],
        ],
        'inputdatetime_2' => [
            'exclude' => 1,
            'label' => 'inputdatetime_2',
            'description' => 'dbType=date eval=date',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'dbType' => 'date',
                'eval' => 'date',
                'default' => '0000-00-00'
            ],
        ],
        'inputdatetime_3' => [
            'exclude' => 1,
            'label' => 'inputdatetime_3',
            'description' => 'eval=datetime',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime',
            ],
        ],
        'inputdatetime_4' => [
            'exclude' => 1,
            'label' => 'inputdatetime_4',
            'description' => 'dbType=datetime eval=datetime',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'dbType' => 'datetime',
                'eval' => 'datetime',
                'default' => '0000-00-00 00:00:00'
            ],
        ],
        'inputdatetime_5' => [
            'exclude' => 1,
            'label' => 'inputdatetime_5',
            'description' => 'eval=time',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'time',
            ],
        ],
        'inputdatetime_6' => [
            'exclude' => 1,
            'label' => 'inputdatetime_6',
            'description' => 'eval=timesec',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'timesec',
            ],
        ],
        'inputdatetime_7' => [
            'exclude' => 1,
            'label' => 'inputdatetime_7',
            'description' => 'eval=date readOnly',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'date',
                'readOnly' => true,
            ],
        ],
        'inputdatetime_8' => [
            'exclude' => 1,
            'label' => 'inputdatetime_8',
            'description' => 'eval=datetime readOnly',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime',
                'readOnly' => true,
            ],
        ],
        'inputdatetime_9' => [
            'exclude' => 1,
            'label' => 'inputdatetime_9',
            'description' => 'eval=time readOnly',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'time',
                'readOnly' => true,
            ],
        ],
        'inputdatetime_10' => [
            'exclude' => 1,
            'label' => 'inputdatetime_10',
            'description' => 'eval=timesec readOnly',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'timesec',
                'readOnly' => true,
            ],
        ],
        'inputdatetime_11' => [
            'exclude' => 1,
            'label' => 'inputdatetime_11',
            'description' => 'eval=datetime, default=0, range.lower=1627208536',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime',
                'default' => 0,
                'range' => [
                    'lower' => 1627208536
                ]
            ],
        ],

        'text_1' => [
            'l10n_mode' => 'prefixLangTitle description',
            'description' => 'field description',
            'exclude' => 1,
            'label' => 'text_1',
            'config' => [
                'type' => 'text',
            ],
        ],
        'text_2' => [
            'l10n_mode' => 'prefixLangTitle',
            'exclude' => 1,
            'label' => 'text_2',
            'description' => 'cols=20',
            'config' => [
                'type' => 'text',
                'cols' => 20,
            ],
        ],
        'text_3' => [
            'exclude' => 1,
            'label' => 'text_3',
            'description' => 'rows=2',
            'config' => [
                'type' => 'text',
                'rows' => 2,
            ],
        ],
        'text_4' => [
            'exclude' => 1,
            'label' => 'text_4',
            'description' => 'cols=20, rows=2',
            'config' => [
                'type' => 'text',
                'cols' => 20,
                'rows' => 2,
            ],
        ],
        'text_5' => [
            'exclude' => 1,
            'label' => 'text_5',
            'description' => 'wrap=off, long default text',
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
            'label' => 'text_6',
            'description' => 'wrap=virtual, long default text',
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
            'label' => 'text_7',
            'description' => 'eval=trim',
            'config' => [
                'type' => 'text',
                'eval' => 'trim',
            ],
        ],
        'text_8' => [
            'exclude' => 1,
            'label' => 'text_8',
            'description' => 'eval with user function',
            'config' => [
                'type' => 'text',
                'eval' => 'TYPO3\\CMS\\Styleguide\\UserFunctions\\FormEngine\\TypeText9Eval',
            ],
        ],
        'text_9' => [
            'exclude' => 1,
            'label' => 'text_9',
            'description' => 'readOnly=1',
            'config' => [
                'type' => 'text',
                'readOnly' => 1,
            ],
        ],
        'text_10' => [
            'exclude' => 1,
            'label' => 'text_10',
            'description' => 'readOnly=1, format=datetime',
            'config' => [
                'type' => 'text',
                'readOnly' => 1,
                'format' => 'datetime',
            ],
        ],
        'text_11' => [
            'exclude' => 1,
            'label' => 'text_11',
            'description' => 'max=30',
            'config' => [
                'type' => 'text',
                'cols' => 30,
                'rows' => 4,
                'max' => 30,
            ],
        ],
        'text_12' => [
            'exclude' => 1,
            'label' => 'text_12',
            'description' => 'default="text_12", value for text_13 and text_14',
            'config' => [
                'type' => 'input',
                'default' => 'text_12',
            ],
        ],
        'text_13' => [
            'exclude' => 1,
            'label' => 'text_13',
            'description' => 'placeholder=__row|text_12',
            'config' => [
                'type' => 'text',
                'placeholder' => '__row|text_12',
            ],
        ],
        'text_14' => [
            'exclude' => 1,
            'label' => 'text_14',
            'description' => 'placeholder=__row|text_12, mode=useOrOverridePlaceholder, eval=null',
            'config' => [
                'type' => 'text',
                'placeholder' => '__row|text_12',
                'eval' => 'null',
                'mode' => 'useOrOverridePlaceholder',
            ],
        ],
        'text_15' => [
            'exclude' => 1,
            'label' => 'text_15',
            'description' => 'enableTabulator, fixedFont',
            'config' => [
                'type' => 'text',
                'enableTabulator' => true,
                'fixedFont' => true,
            ],
        ],
        'text_16' => [
            'label' => 'text_16',
            'description' => 'valuePicker',
            'config' => [
                'type' => 'text',
                'cols' => '40',
                'rows' => '5',
                'valuePicker' => [
                    'items' => [
                        ['Option 1', 'Dummy Text for Option 1'],
                        ['Option 2', 'Dummy Text for Option 2'],
                        ['Option 3', 'Dummy Text for Option 3'],
                    ],
                ],
            ],
        ],
        'text_17' => [
            'label' => 'text_17',
            'description' => 'renderType=textTable',
            'config' => [
                'type' => 'text',
                'renderType' => 'textTable',
                'cols' => '40',
                'rows' => '5',
            ],
        ],
        'text_18' => [
            'exclude' => 1,
            'label' => 'text_18',
            'description' => 'eval=null',
            'config' => [
                'type' => 'text',
                'eval' => 'null',
            ],
        ],
        'text_19' => [
            'label' => 'text_19',
            'description' => 'renderType=textTable readOnly',
            'config' => [
                'type' => 'text',
                'renderType' => 'textTable',
                'readOnly' => true,
                'cols' => '40',
                'rows' => '5',
            ],
        ],
        'text_20' => [
            'label' => 'text_20',
            'description' => 'renderType=belayoutwizard',
            'config' => [
                'type' => 'text',
                'renderType' => 'belayoutwizard',
                'default' => '
mod.web_layout.BackendLayouts {
  exampleKey {
    title = Example
    icon = EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg
    config {
      backend_layout {
        colCount = 2
        rowCount = 2
        rows {
          1 {
            columns {
              1 {
                name = Left
                rowspan = 2
                colPos = 1
              }
              2 {
                name = Main
                colPos = 0
              }
            }
          }
          2 {
            columns {
              1 {
                name = Footer
                colPos = 24
              }
            }
          }
        }
      }
    }
  }
}',
            ],
        ],

        'text_21' => [
            'label' => 'text_21',
            'description' => 'renderType=textTable tableWizard numNewRows=3',
            'config' => [
                'type' => 'text',
                'renderType' => 'textTable',
                'cols' => 40,
                'rows' => 5,
                'fieldControl' => [
                    'tableWizard' => [
                        'options' => [
                            'numNewRows' => 3,
                        ],
                    ],
                ],
            ],
        ],

        'checkbox_1' => [
            'exclude' => 1,
            'label' => 'checkbox_1',
            'description' => 'field description',
            'config' => [
                'type' => 'check',
            ]
        ],
        'checkbox_2' => [
            'exclude' => 1,
            'label' => 'checkbox_2',
            'description' => 'one checkbox with label',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['foo', ''],
                ],
            ]
        ],
        'checkbox_3' => [
            'exclude' => 1,
            'label' => 'checkbox_3',
            'description' => 'three checkboxes, two with labels, one without',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['foo', ''],
                    ['', ''],
                    [
                        'foobar',
                        '',
                        'iconIdentifierChecked' => 'content-beside-text-img-below-center',
                        'iconIdentifierUnchecked' => 'content-beside-text-img-below-center',
                    ],
                ],
            ],
        ],
        'checkbox_4' => [
            'exclude' => 1,
            'label' => 'checkbox_4',
            'description' => 'four checkboxes with labels, long text',
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
            ],
        ],
        'checkbox_6' => [
            // @todo: Checking a checkbox that is added by itemsProcFunc is not persisted correctly.
            // @todo: HTML looks good, so this is probably an issue in DataHandler?
            'label' => 'checkbox_6',
            'description' => 'itemsProcFunc',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['foo', ''],
                    ['bar', ''],
                ],
                'itemsProcFunc' => 'TYPO3\\CMS\\Styleguide\\UserFunctions\\FormEngine\\TypeCheckbox8ItemsProcFunc->itemsProcFunc',
            ],
        ],
        'checkbox_7' => [
            'exclude' => 1,
            'label' => 'checkbox_7',
            'description' => 'eval=maximumRecordsChecked, table wide',
            'config' => [
                'type' => 'check',
                'eval' => 'maximumRecordsChecked',
                'validation' => [
                    'maximumRecordsChecked' => 1,
                ],
            ],
        ],
        'checkbox_8' => [
            'exclude' => 1,
            'label' => 'checkbox_8',
            'description' => 'eval=maximumRecordsCheckedInPid, for this PID',
            'config' => [
                'type' => 'check',
                'eval' => 'maximumRecordsCheckedInPid',
                'validation' => [
                    'maximumRecordsCheckedInPid' => 1,
                ],
            ],
        ],
        'checkbox_9' => [
            'exclude' => 1,
            'label' => 'checkbox_9',
            'description' => 'readonly=1',
            'config' => [
                'type' => 'check',
                'readOnly' => 1,
                'items' => [
                    ['foo1', ''],
                    ['foo2', ''],
                ],
            ],
        ],
        'checkbox_10' => [
            'exclude' => 1,
            'label' => 'checkbox_10',
            'description' => 'cols=1',
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
        'checkbox_11' => [
            'exclude' => 1,
            'label' => 'checkbox_11',
            'description' => 'cols=2',
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
        'checkbox_12' => [
            'exclude' => 1,
            'label' => 'checkbox_12',
            'description' => 'cols=3',
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
        'checkbox_13' => [
            'exclude' => 1,
            'label' => 'checkbox_13',
            'description' => 'cols=4',
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
        'checkbox_14' => [
            'exclude' => 1,
            'label' => 'checkbox_14',
            'description' => 'cols=5',
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
        'checkbox_15' => [
            'exclude' => 1,
            'label' => 'checkbox_15',
            'description' => 'cols=6',
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
        'checkbox_16' => [
            'exclude' => 1,
            'label' => 'checkbox_16',
            'description' => 'cols=inline',
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
        'checkbox_17' => [
            'exclude' => 1,
            'label' => 'checkbox_17',
            'description' => 'renderType=checkboxToggle single',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        0 => 'foo',
                        1 => '',
                        'labelChecked' => 'Enabled',
                        'labelUnchecked' => 'Disabled'
                    ]
                ],
            ]
        ],
        'checkbox_18' => [
            'exclude' => 1,
            'label' => 'checkbox_18',
            'description' => 'renderType=checkboxToggle single inverted state display',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        0 => 'foo',
                        1 => '',
                        'labelChecked' => 'Enabled',
                        'labelUnchecked' => 'Disabled',
                        'invertStateDisplay' => true
                    ]
                ],
            ]
        ],
        'checkbox_19' => [
            'exclude' => 1,
            'label' => 'checkbox_19',
            'description' => 'renderType=checkboxLabeledToggle single',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxLabeledToggle',
                'items' => [
                    [
                        0 => 'foo',
                        1 => '',
                        'labelChecked' => 'Enabled',
                        'labelUnchecked' => 'Disabled',
                    ]
                ],
            ]
        ],
        'checkbox_20' => [
            'exclude' => 1,
            'label' => 'checkbox_20',
            'description' => 'renderType=checkboxLabeledToggle multiple',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxLabeledToggle',
                'items' => [
                    [
                        0 => 'foo',
                        1 => '',
                        'labelChecked' => 'On',
                        'labelUnchecked' => 'Off',
                    ],
                    [
                        0 => 'bar',
                        1 => '',
                        'labelChecked' => 'On',
                        'labelUnchecked' => 'Off',
                    ],
                    [
                        0 => 'inv',
                        1 => '',
                        'labelChecked' => 'On',
                        'labelUnchecked' => 'Off',
                        'invertStateDisplay' => true
                    ]
                ],
            ]
        ],
        'checkbox_21' => [
            'exclude' => 1,
            'label' => 'checkbox_21',
            'description' => 'renderType=checkboxLabeledToggle single inverted state display',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxLabeledToggle',
                'items' => [
                    [
                        0 => 'foo',
                        1 => '',
                        'labelChecked' => 'Enabled',
                        'labelUnchecked' => 'Disabled',
                        'invertStateDisplay' => true
                    ]
                ],
            ]
        ],
        'checkbox_24' => [
            'exclude' => 1,
            'label' => 'checkbox_24',
            'description' => 'renderType=checkboxToggle multiple',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        0 => 'foo',
                        1 => '',
                    ],
                    [
                        0 => 'bar',
                        1 => '',
                    ],
                    [
                        0 => 'baz',
                        1 => '',
                    ],
                    [
                        0 => 'husel',
                        1 => '',
                    ]
                ],
                'cols' => '4',
            ]
        ],
        'checkbox_25' => [
            'exclude' => 1,
            'label' => 'checkbox_25',
            'description' => 'renderType=checkboxToggle single readOnly',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'readOnly' => true,
                'items' => [
                    [
                        0 => 'foo',
                        1 => '',
                        'labelChecked' => 'Enabled',
                        'labelUnchecked' => 'Disabled'
                    ]
                ],
            ]
        ],
        'checkbox_26' => [
            'exclude' => 1,
            'label' => 'checkbox_26 description',
            'description' => 'renderType=checkboxLabeledToggle single readOnly',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxLabeledToggle',
                'readOnly' => true,
                'items' => [
                    [
                        0 => 'foo',
                        1 => '',
                        'labelChecked' => 'Enabled',
                        'labelUnchecked' => 'Disabled',
                    ]
                ],
            ]
        ],

        'radio_1' => [
            'exclude' => 1,
            'label' => 'description',
            'description' => 'radio_1 three options, one without label',
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
            'label' => 'radio_2',
            'description' => 'three options, long text',
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
            ],
        ],
        'radio_3' => [
            'exclude' => 1,
            'label' => 'radio_3',
            'description' => 'many options',
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
            'label' => 'radio_4',
            'description' => 'string values',
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
            'label' => 'radio_5',
            'description' => 'itemsProcFunc',
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
            'label' => 'radio_6',
            'description' => 'readonly=1',
            'config' => [
                'type' => 'radio',
                'readOnly' => 1,
                'items' => [
                    ['foo', 1],
                    ['bar', 2],
                ],
            ],
        ],

        'none_1' => [
            'exclude' => 1,
            'label' => 'none_1',
            'description' => 'pass_content=1',
            'config' => [
                'type' => 'none',
                'pass_content' => 1,
            ],
        ],
        'none_2' => [
            'exclude' => 1,
            'label' => 'none_2',
            'description' => 'pass_content=0',
            'config' => [
                'type' => 'none',
                'pass_content' => 0,
            ],
        ],
        'none_3' => [
            'exclude' => 1,
            'label' => 'none_3',
            'description' => 'cols=2',
            'config' => [
                'type' => 'none',
                'cols' => 2,
            ],
        ],
        'none_4' => [
            'exclude' => 1,
            'label' => 'none_4',
            'description' => 'size=6',
            'config' => [
                'type' => 'none',
                'size' => 6,
            ],
        ],
        'none_5' => [
            'exculde' => 1,
            'label' => 'none_5',
            'description' => 'format=datetime',
            'config' => [
                'type' => 'none',
                'format' => 'datetime',
            ],
        ],

        'passthrough_1' => [
            'exclude' => 1,
            'label' => 'passthrough_1',
            'description' => 'field should NOT be shown',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'passthrough_2' => [
            'exclude' => 1,
            'label' => 'passthrough_2',
            'description' => 'not shown, default applied',
            'config' => [
                'type' => 'passthrough',
                'default' => 42,
            ],
        ],

        'user_1' => [
            'exclude' => 1,
            'label' => 'user_1',
            'description' => 'parameter=color=green',
            'config' => [
                'type' => 'user',
                'renderType' => 'user1Element',
                'parameters' => [
                    'color' => 'green',
                ],
            ],
        ],

        'flex_1' => [
            'exclude' => 1,
            'label' => 'flex_1',
            'config' => [
                'type' => 'flex',
                'ds' => [
                    'default' => '
                        <T3DataStructure>
                            <sheets>

                                <sInput>
                                    <ROOT>
                                        <type>array</type>
                                        <TCEforms>
                                            <sheetTitle>input</sheetTitle>
                                        </TCEforms>
                                        <el>
                                            <input_1>
                                                <TCEforms>
                                                    <label>input_1 renderType inputLink description</label>
                                                    <description>field description</description>
                                                    <config>
                                                        <type>input</type>
                                                        <renderType>inputLink</renderType>
                                                        <eval>trim</eval>
                                                        <softref>typolink</softref>
                                                        <fieldControl>
                                                            <linkPopup>
                                                                <options>
                                                                    <title>Link</title>
                                                                    <blindLinkOptions>mail,folder,spec</blindLinkOptions>
                                                                </options>
                                                            </linkPopup>
                                                        </fieldControl>
                                                    </config>
                                                </TCEforms>
                                            </input_1>
                                            <input_2>
                                                <TCEforms>
                                                    <label>input_2 renderyType textTable</label>
                                                    <description>field description</description>
                                                    <config>
                                                        <type>text</type>
                                                        <renderType>textTable</renderType>
                                                        <cols>30</cols>
                                                        <rows>5</rows>
                                                    </config>
                                                </TCEforms>
                                            </input_2>
                                            <input_3>
                                                <TCEforms>
                                                    <label>input_3 valuePicker</label>
                                                    <config>
                                                        <type>input</type>
                                                        <valuePicker>
                                                            <items>
                                                                <numIndex index="0">
                                                                    <numIndex index="0">Foo</numIndex>
                                                                    <numIndex index="1">foo</numIndex>
                                                                </numIndex>
                                                                <numIndex index="1">
                                                                    <numIndex index="0">Bar</numIndex>
                                                                    <numIndex index="1">bar</numIndex>
                                                                </numIndex>
                                                            </items>
                                                        </valuePicker>
                                                    </config>
                                                </TCEforms>
                                            </input_3>
                                        </el>
                                    </ROOT>
                                </sInput>

                                <sInputDateTime>
                                    <ROOT>
                                        <type>array</type>
                                        <TCEforms>
                                            <sheetTitle>inputDateTime</sheetTitle>
                                        </TCEforms>
                                        <el>
                                            <inputDateTime_1>
                                                <TCEforms>
                                                    <label>inputDateTime_1 eval=date description</label>
                                                    <description>field description</description>
                                                    <config>
                                                        <type>input</type>
                                                        <renderType>inputDateTime</renderType>
                                                        <eval>date</eval>
                                                    </config>
                                                </TCEforms>
                                            </inputDateTime_1>
                                            <inputDateTime_2>
                                                <TCEforms>
                                                    <label>inputDateTime_2 dbType=date eval=date</label>
                                                    <config>
                                                        <type>input</type>
                                                        <renderType>inputDateTime</renderType>
                                                        <eval>date</eval>
                                                        <dbType>date</dbType>
                                                        <default>0000-00-00</default>
                                                    </config>
                                                </TCEforms>
                                            </inputDateTime_2>
                                            <inputDateTime_3>
                                                <TCEforms>
                                                    <label>inputDateTime_3 eval=datetime</label>
                                                    <config>
                                                        <type>input</type>
                                                        <renderType>inputDateTime</renderType>
                                                        <eval>datetime</eval>
                                                    </config>
                                                </TCEforms>
                                            </inputDateTime_3>
                                            <inputDateTime_4>
                                                <TCEforms>
                                                    <label>inputDateTime_4 dbType=datetime eval=datetime</label>
                                                    <config>
                                                        <type>input</type>
                                                        <renderType>inputDateTime</renderType>
                                                        <eval>date</eval>
                                                        <dbType>datetime</dbType>
                                                        <default>0000-00-00 00:00:00</default>
                                                    </config>
                                                </TCEforms>
                                            </inputDateTime_4>
                                            <inputDateTime_5>
                                                <TCEforms>
                                                    <label>inputDateTime_5 eval=time</label>
                                                    <config>
                                                        <type>input</type>
                                                        <renderType>inputDateTime</renderType>
                                                        <eval>time</eval>
                                                    </config>
                                                </TCEforms>
                                            </inputDateTime_5>
                                            <inputDateTime_6>
                                                <TCEforms>
                                                    <label>inputDateTime_6 eval=timesec</label>
                                                    <config>
                                                        <type>input</type>
                                                        <renderType>inputDateTime</renderType>
                                                        <eval>timesec</eval>
                                                    </config>
                                                </TCEforms>
                                            </inputDateTime_6>
                                        </el>
                                    </ROOT>
                                </sInputDateTime>

                                <sText>
                                    <ROOT>
                                        <type>array</type>
                                        <TCEforms>
                                            <sheetTitle>text</sheetTitle>
                                        </TCEforms>
                                        <el>
                                            <text_1>
                                                <TCEforms>
                                                    <label>text_1 cols=20, rows=4 description</label>
                                                    <description>field description</description>
                                                    <config>
                                                        <type>text</type>
                                                        <cols>20</cols>
                                                        <rows>4</rows>
                                                    </config>
                                                </TCEforms>
                                            </text_1>
                                        </el>
                                    </ROOT>
                                </sText>

                                <sCheck>
                                    <ROOT>
                                        <type>array</type>
                                        <TCEforms>
                                            <sheetTitle>check</sheetTitle>
                                        </TCEforms>
                                        <el>
                                            <check_1>
                                                <TCEforms>
                                                    <label>check_1 description</label>
                                                    <description>field description</description>
                                                    <config>
                                                        <type>check</type>
                                                        <items>
                                                            <numIndex index="0">
                                                                <numIndex index="0">Foo</numIndex>
                                                            </numIndex>
                                                            <numIndex index="1">
                                                                <numIndex index="0">Bar</numIndex>
                                                            </numIndex>
                                                            <numIndex index="2">
                                                                <numIndex index="0">FooBar</numIndex>
                                                            </numIndex>
                                                        </items>
                                                    </config>
                                                </TCEforms>
                                            </check_1>
                                            <check_2>
                                                <TCEforms>
                                                    <label>check_2 invertStateDisplay</label>
                                                    <config>
                                                        <type>check</type>
                                                        <items>
                                                            <numIndex index="0">
                                                                <numIndex index="0">Foo</numIndex>
                                                                <invertStateDisplay>1</invertStateDisplay>
                                                            </numIndex>
                                                            <numIndex index="1">
                                                                <numIndex index="0">Bar</numIndex>
                                                                <invertStateDisplay>1</invertStateDisplay>
                                                            </numIndex>
                                                            <numIndex index="2">
                                                                <numIndex index="0">FooBar</numIndex>
                                                                <invertStateDisplay>1</invertStateDisplay>
                                                            </numIndex>
                                                        </items>
                                                    </config>
                                                </TCEforms>
                                            </check_2>
                                        </el>
                                    </ROOT>
                                </sCheck>

                                <sRadio>
                                    <ROOT>
                                        <type>array</type>
                                        <TCEforms>
                                            <sheetTitle>radio</sheetTitle>
                                        </TCEforms>
                                        <el>
                                            <radio_1>
                                                <TCEforms>
                                                    <label>radio_1 description</label>
                                                    <description>field description</description>
                                                    <config>
                                                        <type>radio</type>
                                                        <items>
                                                            <numIndex index="0">
                                                                <numIndex index="0">Foo</numIndex>
                                                                <numIndex index="1">1</numIndex>
                                                            </numIndex>
                                                            <numIndex index="1">
                                                                <numIndex index="0">Bar</numIndex>
                                                                <numIndex index="1">2</numIndex>
                                                            </numIndex>
                                                        </items>
                                                    </config>
                                                </TCEforms>
                                            </radio_1>
                                        </el>
                                    </ROOT>
                                </sRadio>

                                <sPassthrough>
                                    <ROOT>
                                        <type>array</type>
                                        <TCEforms>
                                            <sheetTitle>passthrough</sheetTitle>
                                        </TCEforms>
                                        <el>
                                            <passthrough_1>
                                                <TCEforms>
                                                    <label>passthrough_1</label>
                                                    <description>field description</description>
                                                    <config>
                                                        <type>passthrough</type>
                                                    </config>
                                                </TCEforms>
                                            </passthrough_1>
                                        </el>
                                    </ROOT>
                                </sPassthrough>



                            </sheets>
                        </T3DataStructure>
                    ',
                ],
            ],
        ],

    ],

    'types' => [
        '0' => [
            'showitem' => '
                --div--;input,
                    input_1, input_40, input_2, input_3, input_4, input_5, input_8, input_39, input_9, input_10,
                    input_11, input_12, input_13, input_15, input_16, input_19, input_20,
                    input_21, input_22, input_23, input_24, input_25, input_26, input_27, input_14, input_28, input_29,
                    input_41, input_38, input_30, input_31, input_32, input_33, input_35, input_36, input_34, input_42,
                    input_37,
                --div--;inputDateTime,
                    inputdatetime_1, inputdatetime_2, inputdatetime_3, inputdatetime_4, inputdatetime_5,
                    inputdatetime_6, inputdatetime_7, inputdatetime_8, inputdatetime_9, inputdatetime_10,
                    inputdatetime_11,
                --div--;text,
                    text_1, text_2, text_3, text_4, text_5, text_6, text_7, text_8, text_9, text_10,
                    text_11, text_12, text_13, text_18, text_14, text_15, text_16, text_17, text_19,
                    text_21, text_20,
                --div--;check,
                    checkbox_1, checkbox_9, checkbox_2, checkbox_17, checkbox_25, checkbox_18, checkbox_24, checkbox_19, checkbox_26,
                    checkbox_20, checkbox_21, checkbox_22, checkbox_23, checkbox_3, checkbox_4, checkbox_6, checkbox_7, checkbox_8,
                    checkbox_10, checkbox_11, checkbox_12, checkbox_13, checkbox_14, checkbox_15, checkbox_16,
                --div--;radio,
                    radio_1, radio_2, radio_3, radio_4, radio_5, radio_6,
                --div--;none,
                    none_1, none_2, none_3, none_4, none_5,
                --div--;passthrough,
                    passthrough_1, passthrough_2,
                --div--;user,
                    user_1,
                --div--;in flex,
                    flex_1,
                --div--;meta,
                    disable, sys_language_uid, l10n_parent, l10n_source,
            ',
        ],
    ],

];

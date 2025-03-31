<?php

return [
    'ctrl' => [
        'title' => 'Form engine elements - input, text, uuid, checkbox, radio, none, passthrough, user',
        'label' => 'uid',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',
        'sortby' => 'sorting',
        'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide.svg',
        'versioningWS' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'translationSource' => 'l10n_source',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],

    'columns' => [
        'input_1' => [
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'input_1 description',
            'description' => 'field description',
            'config' => [
                'type' => 'input',
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        'input_2' => [
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'input_2',
            'description' => 'size=10',
            'config' => [
                'type' => 'input',
                'size' => 10,
            ],
        ],
        'input_3' => [
            'label' => 'input_3',
            'description' => 'max=4',
            'config' => [
                'type' => 'input',
                'max' => 4,
            ],
        ],
        'input_4' => [
            'label' => 'input_4',
            'description' => 'eval=alpha',
            'config' => [
                'type' => 'input',
                'eval' => 'alpha',
            ],
        ],
        'input_5' => [
            'label' => 'input_5',
            'description' => 'eval=alphanum',
            'config' => [
                'type' => 'input',
                'eval' => 'alphanum',
            ],
        ],
        'input_10' => [
            'label' => 'input_10',
            'description' => 'eval=is_in is_in=abc123',
            'config' => [
                'type' => 'input',
                'eval' => 'is_in',
                'is_in' => 'abc123',
            ],
        ],
        'input_11' => [
            'label' => 'input_11',
            'description' => 'eval=lower',
            'config' => [
                'type' => 'input',
                'eval' => 'lower',
            ],
        ],
        'input_12' => [
            'label' => 'input_12',
            'description' => 'eval=md5',
            'config' => [
                'type' => 'input',
                'eval' => 'md5',
            ],
        ],
        'input_13' => [
            'label' => 'input_13',
            'description' => 'eval=nospace',
            'config' => [
                'type' => 'input',
                'eval' => 'nospace',
            ],
        ],
        'input_14' => [
            'label' => 'input_14',
            'description' => 'nullable=true',
            'config' => [
                'type' => 'input',
                'nullable' => true,
            ],
        ],
        'input_15' => [
            'label' => 'input_15',
            'description' => 'eval=num',
            'config' => [
                'type' => 'input',
                'eval' => 'num',
            ],
        ],
        'input_19' => [
            'label' => 'input_19',
            'description' => 'eval=trim',
            'config' => [
                'type' => 'input',
                'eval' => 'trim',
            ],
        ],
        'input_21' => [
            'label' => 'input_21',
            'description' => 'eval=unique',
            'config' => [
                'type' => 'input',
                'eval' => 'unique',
            ],
        ],
        'input_22' => [
            'label' => 'input_22',
            'description' => 'eval=uniqueInPid',
            'config' => [
                'type' => 'input',
                'eval' => 'uniqueInPid',
            ],
        ],
        'input_23' => [
            'label' => 'input_23',
            'description' => 'eval=upper',
            'config' => [
                'type' => 'input',
                'eval' => 'upper',
            ],
        ],
        'input_26' => [
            'label' => 'input_26',
            'description' => 'default="input_26", value for input_27 and input_28',
            'config' => [
                'type' => 'input',
                'default' => 'input_26',
            ],
        ],
        'input_27' => [
            'label' => 'input_27',
            'description' => 'placeholder=__row|input_26',
            'config' => [
                'type' => 'input',
                'placeholder' => '__row|input_26',
            ],
        ],
        'input_28' => [
            'label' => 'input_28',
            'description' => 'placeholder=__row|input_26 mode=useOrOverridePlaceholder nullable=true default=null',
            'config' => [
                'type' => 'input',
                'placeholder' => '__row|input_26',
                'nullable' => true,
                'default' => null,
                'mode' => 'useOrOverridePlaceholder',
            ],
        ],
        'input_33' => [
            'label' => 'input_33',
            'description' => 'valuePicker',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim',
                'valuePicker' => [
                    'items' => [
                        [ 'value' => 'spring', 'label' => 'Spring'],
                        [ 'value' => 'summer', 'label' => 'Summer'],
                        [ 'value' => 'autumn', 'label' => 'Autumn'],
                        [ 'value' => 'winter', 'label' => 'Winter'],
                    ],
                ],
            ],
        ],
        'input_35' => [
            'label' => 'input_35',
            'description' => 'valuePicker append',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim',
                'valuePicker' => [
                    'mode' => 'append',
                    'items' => [
                        [ 'label' => 'Spring', 'value' => 'spring'],
                        [ 'label' => 'Summer', 'value' => 'summer'],
                        [ 'label' => 'Autumn', 'value' => 'autumn'],
                        [ 'label' => 'Winter', 'value' => 'winter'],
                    ],
                ],
            ],
        ],
        'input_36' => [
            'label' => 'input_36',
            'description' => 'valuePicker prepend',
            'config' => [
                'type' => 'input',
                'size' => 20,
                'eval' => 'trim',
                'valuePicker' => [
                    'mode' => 'prepend',
                    'items' => [
                        [ 'label' => 'spring', 'value' => 'Spring'],
                        [ 'label' => 'summer', 'value' => 'Summer'],
                        [ 'label' => 'autumn', 'value' => 'Autumn'],
                        [ 'label' => 'winter', 'value' => 'Winter'],
                    ],
                ],
            ],
        ],
        'input_40' => [
            'label' => 'input_40',
            'description' => 'readOnly',
            'config' => [
                'type' => 'input',
                'readOnly' => true,
            ],
        ],
        'input_41' => [
            'label' => 'input_41',
            'description' => 'min=4',
            'config' => [
                'type' => 'input',
                'min' => 4,
            ],
        ],
        'input_42' => [
            'label' => 'input_42',
            'description' => 'min=4, max=8',
            'config' => [
                'type' => 'input',
                'min' => 4,
                'max' => 8,
            ],
        ],
        'input_43' => [
            'label' => 'input_43',
            'description' => 'min=4, max=4',
            'config' => [
                'type' => 'input',
                'min' => 4,
                'max' => 4,
            ],
        ],

        'inputdatetime_1' => [
            'label' => 'inputdatetime_1',
            'description' => 'format=date',
            'config' => [
                'type' => 'datetime',
                'format' => 'date',
            ],
        ],
        'inputdatetime_2' => [
            'label' => 'inputdatetime_2',
            'description' => 'dbType=date format=date',
            'config' => [
                'type' => 'datetime',
                'dbType' => 'date',
                'format' => 'date',
            ],
        ],
        'inputdatetime_3' => [
            'label' => 'inputdatetime_3',
            'description' => 'format=datetime',
            'config' => [
                'type' => 'datetime',
            ],
        ],
        'inputdatetime_4' => [
            'label' => 'inputdatetime_4',
            'description' => 'dbType=datetime format=datetime',
            'config' => [
                'type' => 'datetime',
                'dbType' => 'datetime',
            ],
        ],
        'inputdatetime_5' => [
            'label' => 'inputdatetime_5',
            'description' => 'format=time',
            'config' => [
                'type' => 'datetime',
                'format' => 'time',
            ],
        ],
        'inputdatetime_6' => [
            'label' => 'inputdatetime_6',
            'description' => 'format=timesec',
            'config' => [
                'type' => 'datetime',
                'format' => 'timesec',
            ],
        ],
        'inputdatetime_7' => [
            'label' => 'inputdatetime_7',
            'description' => 'format=date readOnly',
            'config' => [
                'type' => 'datetime',
                'format' => 'date',
                'readOnly' => true,
            ],
        ],
        'inputdatetime_8' => [
            'label' => 'inputdatetime_8',
            'description' => 'format=datetime readOnly',
            'config' => [
                'type' => 'datetime',
                'readOnly' => true,
            ],
        ],
        'inputdatetime_9' => [
            'label' => 'inputdatetime_9',
            'description' => 'format=time readOnly',
            'config' => [
                'type' => 'datetime',
                'format' => 'time',
                'readOnly' => true,
            ],
        ],
        'inputdatetime_10' => [
            'label' => 'inputdatetime_10',
            'description' => 'format=timesec readOnly',
            'config' => [
                'type' => 'datetime',
                'format' => 'timesec',
                'readOnly' => true,
            ],
        ],
        'inputdatetime_11' => [
            'label' => 'inputdatetime_11',
            'description' => 'default=0, range.lower=1627208536',
            'config' => [
                'type' => 'datetime',
                'default' => 0,
                'range' => [
                    'lower' => 1627208536,
                ],
            ],
        ],
        'inputdatetime_34' => [
            'label' => 'inputdatetime_34',
            'description' => 'default=0, range.lower=1627208536 [2021-07-25T10:22:16Z], range.upper=1729755199 [2024-10-24T07:33:19Z]',
            'config' => [
                'type' => 'datetime',
                'default' => 0,
                'range' => [
                    'lower' => 1627208536,
                    'upper' => 1729755199,
                ],
            ],
        ],
        'inputdatetime_12' => [
            'label' => 'inputdatetime_12',
            'description' => 'dbType=time format=time',
            'config' => [
                'type' => 'datetime',
                'dbType' => 'time',
                'format' => 'time',
            ],
        ],
        'inputdatetime_13' => [
            'label' => 'inputdatetime_13',
            'description' => 'dbType=time format=timesec',
            'config' => [
                'type' => 'datetime',
                'dbType' => 'time',
                'format' => 'timesec',
            ],
        ],

        'inputdatetime_21' => [
            'label' => 'inputdatetime_21',
            'description' => 'format=date nullable=true',
            'config' => [
                'type' => 'datetime',
                'format' => 'date',
                'nullable' => true,
            ],
        ],
        'inputdatetime_22' => [
            'label' => 'inputdatetime_22',
            'description' => 'dbType=date format=date nullable=true',
            'config' => [
                'type' => 'datetime',
                'dbType' => 'date',
                'format' => 'date',
                'nullable' => true,
            ],
        ],
        'inputdatetime_23' => [
            'label' => 'inputdatetime_23',
            'description' => 'format=datetime nullable=true',
            'config' => [
                'type' => 'datetime',
                'nullable' => true,
            ],
        ],
        'inputdatetime_24' => [
            'label' => 'inputdatetime_24',
            'description' => 'format=datetime dbType=datetime nullable=true',
            'config' => [
                'type' => 'datetime',
                'dbType' => 'datetime',
                'nullable' => true,
            ],
        ],
        'inputdatetime_25' => [
            'label' => 'inputdatetime_25',
            'description' => 'format=time nullable=true',
            'config' => [
                'type' => 'datetime',
                'format' => 'time',
                'nullable' => true,
            ],
        ],
        'inputdatetime_26' => [
            'label' => 'inputdatetime_26',
            'description' => 'format=timesec nullable=true',
            'config' => [
                'type' => 'datetime',
                'format' => 'timesec',
                'nullable' => true,
            ],
        ],
        'inputdatetime_27' => [
            'label' => 'inputdatetime_27',
            'description' => 'format=date readOnly nullable=true',
            'config' => [
                'type' => 'datetime',
                'format' => 'date',
                'readOnly' => true,
                'nullable' => true,
            ],
        ],
        'inputdatetime_28' => [
            'label' => 'inputdatetime_28',
            'description' => 'format=datetime readOnly nullable=true',
            'config' => [
                'type' => 'datetime',
                'readOnly' => true,
                'nullable' => true,
            ],
        ],
        'inputdatetime_29' => [
            'label' => 'inputdatetime_29',
            'description' => 'format=time readOnly nullable=true',
            'config' => [
                'type' => 'datetime',
                'format' => 'time',
                'readOnly' => true,
                'nullable' => true,
            ],
        ],
        'inputdatetime_30' => [
            'label' => 'inputdatetime_30',
            'description' => 'format=timesec readOnly nullable=true',
            'config' => [
                'type' => 'datetime',
                'format' => 'timesec',
                'readOnly' => true,
                'nullable' => true,
            ],
        ],
        'inputdatetime_31' => [
            'label' => 'inputdatetime_31',
            'description' => 'default=0, nullable=true',
            'config' => [
                'type' => 'datetime',
                'default' => 0,
                'nullable' => true,
            ],
        ],
        'inputdatetime_32' => [
            'label' => 'inputdatetime_32',
            'description' => 'format=time dbType=time nullable=true',
            'config' => [
                'type' => 'datetime',
                'format' => 'time',
                'dbType' => 'time',
                'nullable' => true,
            ],
        ],
        'inputdatetime_33' => [
            'label' => 'inputdatetime_33',
            'description' => 'format=timesec dbType=time nullable=true',
            'config' => [
                'type' => 'datetime',
                'format' => 'timesec',
                'dbType' => 'time',
                'nullable' => true,
            ],
        ],
        'inputdatetime_35' => [
            'label' => 'inputdatetime_35',
            'description' => 'range.lower=1627208536 nullable=true',
            'config' => [
                'type' => 'datetime',
                'range' => [
                    'lower' => 1627208536,
                ],
                'nullable' => true,
            ],
        ],

        'link_1' => [
            'label' => 'link_1',
            'description' => 'type=link',
            'config' => [
                'type' => 'link',
            ],
        ],
        'link_2' => [
            'label' => 'link_2',
            'description' => 'type=link allowedTypes=file allowedOptions=allowedFileExtensions=jpg,png',
            'config' => [
                'type' => 'link',
                'allowedTypes' => ['file'],
                'appearance' => [
                    'allowedFileExtensions' => ['jpg', 'png'],
                ],
            ],
        ],
        'link_3' => [
            'label' => 'link_3',
            'description' => 'type=link readOnly',
            'config' => [
                'type' => 'link',
                'readOnly' => true,
            ],
        ],
        'link_4' => [
            'label' => 'link_4',
            'description' => 'type=link linkBrowser disabled',
            'config' => [
                'type' => 'link',
                'appearance' => [
                    'enableBrowser' => false,
                ],
            ],
        ],
        'link_5' => [
            'label' => 'link_5',
            'description' => 'type=link allowedOptions=target,title custom browser title',
            'config' => [
                'type' => 'link',
                'appearance' => [
                    'browserTitle' => 'Custom title',
                    'allowedOptions' => ['title', 'target'],
                ],
            ],
        ],

        'password_1' => [
            'label' => 'password_1',
            'description' => 'type=password',
            'config' => [
                'type' => 'password',
            ],
        ],
        'password_2' => [
            'label' => 'password_2',
            'description' => 'type=password hashed=false',
            'config' => [
                'type' => 'password',
                'hashed' => false,
            ],
        ],
        'password_3' => [
            'label' => 'password_3',
            'description' => 'type=password readOnly=true',
            'config' => [
                'type' => 'password',
                'readOnly' => true,
                'default' => 'somePassword1!',
            ],
        ],
        'password_4' => [
            'label' => 'password_4',
            'description' => 'type=password fieldControl=passwordGenerator random=hex',
            'config' => [
                'type' => 'password',
                'fieldControl' => [
                    'passwordGenerator' => [
                        'renderType' => 'passwordGenerator',
                        'options' => [
                            'title' => 'Create random hex string',
                            'passwordRules' => [
                                'length' => 30,
                                'random' => 'hex',
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'password_5' => [
            'label' => 'password_5',
            'description' => 'type=password fieldControl=passwordGenerator random=base64 allowEdit=false',
            'config' => [
                'type' => 'password',
                'fieldControl' => [
                    'passwordGenerator' => [
                        'renderType' => 'passwordGenerator',
                        'options' => [
                            'title' => 'Create random base64 string',
                            'allowEdit' => false,
                            'passwordRules' => [
                                'length' => 35,
                                'random' => 'base64',
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'password_6' => [
            'label' => 'password_6',
            'description' => 'type=password fieldControl=passwordGenerator - all character sets',
            'config' => [
                'type' => 'password',
                'fieldControl' => [
                    'passwordGenerator' => [
                        'renderType' => 'passwordGenerator',
                        'options' => [
                            'title' => 'Create random password',
                            'passwordRules' => [
                                'specialCharacters' => true,
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'password_7' => [
            'label' => 'password_7',
            'description' => 'type=password fieldControl=passwordGenerator length=8 - only digits',
            'config' => [
                'type' => 'password',
                'fieldControl' => [
                    'passwordGenerator' => [
                        'renderType' => 'passwordGenerator',
                        'options' => [
                            'title' => 'Create random number',
                            'passwordRules' => [
                                'length' => 8,
                                'lowerCaseCharacters' => false,
                                'upperCaseCharacters' => false,
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'password_8' => [
            'label' => 'password_8',
            'description' => 'type=password nullable',
            'config' => [
                'type' => 'password',
                'nullable' => true,
            ],
        ],

        'color_1' => [
            'label' => 'color_1',
            'description' => 'type=color',
            'config' => [
                'type' => 'color',
                'size' => 10,
            ],
        ],
        'color_2' => [
            'label' => 'color_2',
            'description' => 'type=color valuePicker',
            'config' => [
                'type' => 'color',
                'size' => 10,
                'valuePicker' => [
                    'items' => [
                        [ 'label' => 'blue', 'value' => '#0000FF'],
                        [ 'label' => 'red', 'value' => '#FF0000'],
                        [ 'label' => 'typo3 orange', 'value' => '#FF8700'],
                    ],
                ],
            ],
        ],
        'color_3' => [
            'label' => 'color_3',
            'description' => 'type=color readOnly',
            'config' => [
                'type' => 'color',
                'size' => 10,
                'readOnly' => true,
            ],
        ],
        'color_4' => [
            'label' => 'color_4 nullable',
            'description' => 'type=color nullable',
            'config' => [
                'type' => 'color',
                'size' => 10,
                'nullable' => true,
            ],
        ],
        'color_5' => [
            'label' => 'color_5 opacity',
            'description' => 'type=color opacity',
            'config' => [
                'type' => 'color',
                'size' => 10,
                'opacity' => true,
            ],
        ],
        'color_palpreset' => [
            'label' => 'color_palpreset with limited palette',
            'description' => 'type=color',
            'config' => [
                'type' => 'color',
                'size' => 10,
            ],
        ],

        'number_1' => [
            'label' => 'number_1',
            'description' => 'format=decimal',
            'config' => [
                'type' => 'number',
                'format' => 'decimal',
            ],
        ],
        'number_2' => [
            'label' => 'number_2',
            'description' => 'format=integer (default)',
            'config' => [
                'type' => 'number',
            ],
        ],
        'number_3' => [
            'label' => 'number_3',
            'description' => 'default=0 range lower=-2 range upper=2',
            'config' => [
                'type' => 'number',
                'range' => [
                    'lower' => -2,
                    'upper' => 2,
                ],
                'default' => 0,
            ],
        ],
        'number_4' => [
            'label' => 'number_4',
            'description' => 'slider step=10 width=200',
            'config' => [
                'type' => 'number',
                'size' => 5,
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
        'number_5' => [
            'label' => 'number_5',
            'description' => 'slider default=14.5 step=0.5 width=150 format=decimal',
            'config' => [
                'type' => 'number',
                'format' => 'decimal',
                'size' => 5,
                'range' => [
                    'lower' => -90.5,
                    'upper' => 90.5,
                ],
                'default' => 14.5,
                'slider' => [
                    'step' => 0.5,
                    'width' => 150,
                ],
            ],
        ],
        'number_7' => [
            'label' => 'number_7',
            'description' => 'readonly=1',
            'config' => [
                'type' => 'number',
                'readOnly' => 1,
            ],
        ],

        'email_1' => [
            'label' => 'email_1',
            'description' => 'email',
            'config' => [
                'type' => 'email',
            ],
        ],
        'email_2' => [
            'label' => 'email_2',
            'description' => 'readOnly',
            'config' => [
                'type' => 'email',
                'readOnly' => true,
            ],
        ],
        'email_3' => [
            'label' => 'email_3',
            'description' => 'nullable',
            'config' => [
                'type' => 'email',
                'nullable' => true,
            ],
        ],
        'email_4' => [
            'label' => 'email_4',
            'description' => 'nullable with placeholder',
            'config' => [
                'type' => 'email',
                'nullable' => true,
                'placeholder' => 'info@example.org',
            ],
        ],
        'email_5' => [
            'label' => 'email_5',
            'description' => 'valuePicker',
            'config' => [
                'type' => 'email',
                'valuePicker' => [
                    'items' => [
                        ['label' => 'Example email', 'value' => 'info@example.org'],
                    ],
                ],
            ],
        ],

        'text_1' => [
            'l10n_mode' => 'prefixLangTitle description',
            'description' => 'field description',
            'label' => 'text_1',
            'config' => [
                'type' => 'text',
            ],
        ],
        'text_2' => [
            'l10n_mode' => 'prefixLangTitle',
            'label' => 'text_2',
            'description' => 'cols=20',
            'config' => [
                'type' => 'text',
                'cols' => 20,
            ],
        ],
        'text_3' => [
            'label' => 'text_3',
            'description' => 'rows=2',
            'config' => [
                'type' => 'text',
                'rows' => 2,
            ],
        ],
        'text_4' => [
            'label' => 'text_4',
            'description' => 'cols=20, rows=2',
            'config' => [
                'type' => 'text',
                'cols' => 20,
                'rows' => 2,
            ],
        ],
        'text_5' => [
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
            'label' => 'text_7',
            'description' => 'eval=trim',
            'config' => [
                'type' => 'text',
                'eval' => 'trim',
            ],
        ],
        'text_9' => [
            'label' => 'text_9',
            'description' => 'readOnly=1',
            'config' => [
                'type' => 'text',
                'readOnly' => 1,
            ],
        ],
        'text_10' => [
            'label' => 'text_10',
            'description' => 'readOnly=1, format=datetime',
            'config' => [
                'type' => 'text',
                'readOnly' => 1,
                'format' => 'datetime',
            ],
        ],
        'text_11' => [
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
            'label' => 'text_12',
            'description' => 'default="text_12", value for text_13 and text_14',
            'config' => [
                'type' => 'input',
                'default' => 'text_12',
            ],
        ],
        'text_13' => [
            'label' => 'text_13',
            'description' => 'placeholder=__row|text_12',
            'config' => [
                'type' => 'text',
                'placeholder' => '__row|text_12',
            ],
        ],
        'text_14' => [
            'label' => 'text_14',
            'description' => 'placeholder=__row|text_12, mode=useOrOverridePlaceholder, nullable=true',
            'config' => [
                'type' => 'text',
                'placeholder' => '__row|text_12',
                'nullable' => true,
                'mode' => 'useOrOverridePlaceholder',
            ],
        ],
        'text_15' => [
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
                        ['label' => 'Option 1', 'value' => 'Dummy Text for Option 1'],
                        ['label' => 'Option 2', 'value' => 'Dummy Text for Option 2'],
                        ['label' => 'Option 3', 'value' => 'Dummy Text for Option 3'],
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
            ],
        ],
        'text_18' => [
            'label' => 'text_18',
            'description' => 'nullable=true',
            'config' => [
                'type' => 'text',
                'nullable' => true,
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
}',
            ],
        ],

        'uuid_1' => [
            'label' => 'uuid_1',
            'description' => 'uuid',
            'config' => [
                'type' => 'uuid',
            ],
        ],
        'uuid_2' => [
            'label' => 'uuid_2',
            'description' => 'uuid without copy icon',
            'config' => [
                'type' => 'uuid',
                'enableCopyToClipboard' => false,
            ],
        ],
        'uuid_3' => [
            'label' => 'uuid_3',
            'description' => 'uuid version 7',
            'config' => [
                'type' => 'uuid',
                'version' => 7,
            ],
        ],

        'checkbox_1' => [
            'label' => 'checkbox_1',
            'description' => 'field description',
            'config' => [
                'type' => 'check',
            ],
        ],
        'checkbox_2' => [
            'label' => 'checkbox_2',
            'description' => 'one checkbox with label',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['label' => 'foo'],
                ],
            ],
        ],
        'checkbox_3' => [
            'label' => 'checkbox_3',
            'description' => 'three checkboxes, two with labels, one without',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['label' => 'foo'],
                    ['label' => ''],
                    [
                        'label' => 'foobar',
                        'iconIdentifierChecked' => 'content-beside-text-img-below-center',
                        'iconIdentifierUnchecked' => 'content-beside-text-img-below-center',
                    ],
                ],
            ],
        ],
        'checkbox_4' => [
            'label' => 'checkbox_4',
            'description' => 'four checkboxes with labels, long text',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['label' => 'foo'],
                    [
                        'label' => 'foo and this here is very long text that maybe does not really fit into the form in one line.'
                        . ' Ok let us add even more text to see how this looks like if wrapped. Is this enough now? No?'
                        . ' Then let us add some even more useless text here!',
                    ],
                    ['label' => 'foobar'],
                    ['label' => 'foobar'],
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
                    ['label' => 'foo'],
                    ['label' => 'bar'],
                ],
                'itemsProcFunc' => 'TYPO3\\CMS\\Styleguide\\UserFunctions\\FormEngine\\TypeCheckbox8ItemsProcFunc->itemsProcFunc',
            ],
        ],
        'checkbox_7' => [
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
            'label' => 'checkbox_9',
            'description' => 'readonly=1',
            'config' => [
                'type' => 'check',
                'readOnly' => 1,
                'items' => [
                    ['label' => 'foo1'],
                    ['label' => 'foo2'],
                ],
            ],
        ],
        'checkbox_10' => [
            'label' => 'checkbox_10',
            'description' => 'cols=1',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['label' => 'foo1'],
                    ['label' => 'foo2'],
                    ['label' => 'foo3'],
                ],
                'cols' => '1',
            ],
        ],
        'checkbox_11' => [
            'label' => 'checkbox_11',
            'description' => 'cols=2',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['label' => 'foo1'],
                    ['label' => 'foo2'],
                    ['label' => 'foo3'],
                ],
                'cols' => '2',
            ],
        ],
        'checkbox_12' => [
            'label' => 'checkbox_12',
            'description' => 'cols=3',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['label' => 'foo1'],
                    ['label' => 'foo2'],
                    ['label' => 'foo3'],
                    ['label' => 'foo4'],
                ],
                'cols' => '3',
            ],
        ],
        'checkbox_13' => [
            'label' => 'checkbox_13',
            'description' => 'cols=4',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['label' => 'foo1'],
                    ['label' => 'foo2'],
                    [
                        'label' => 'foo3 and this here is very long text that maybe does not really fit into the'
                        . ' form in one line. Ok let us add even more text to see how',
                    ],
                    ['label' => 'foo4'],
                    ['label' => 'foo5'],
                    ['label' => 'foo6'],
                    ['label' => 'foo7'],
                    ['label' => 'foo8'],
                ],
                'cols' => '4',
            ],
        ],
        'checkbox_14' => [
            'label' => 'checkbox_14',
            'description' => 'cols=5',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['label' => 'foo1'],
                    ['label' => 'foo2'],
                    ['label' => 'foo3'],
                    ['label' => 'foo4'],
                    ['label' => 'foo5'],
                    ['label' => 'foo6'],
                    ['label' => 'foo7'],
                ],
                'cols' => '5',
            ],
        ],
        'checkbox_15' => [
            'label' => 'checkbox_15',
            'description' => 'cols=6',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['label' => 'foo1'],
                    ['label' => 'foo2'],
                    ['label' => 'foo3'],
                    ['label' => 'foo4'],
                    ['label' => 'foo5'],
                    ['label' => 'foo6'],
                    ['label' => 'foo7'],
                ],
                'cols' => '6',
            ],
        ],
        'checkbox_16' => [
            'label' => 'checkbox_16',
            'description' => 'cols=inline',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['label' => 'Mo'],
                    ['label' => 'Tu'],
                    ['label' => 'We'],
                    ['label' => 'Th'],
                    ['label' => 'Fr'],
                    ['label' => 'Sa'],
                    ['label' => 'Su'],
                ],
                'cols' => 'inline',
            ],
        ],
        'checkbox_17' => [
            'label' => 'checkbox_17',
            'description' => 'renderType=checkboxToggle single',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        'label' => 'foo',
                    ],
                ],
            ],
        ],
        'checkbox_18' => [
            'label' => 'checkbox_18',
            'description' => 'renderType=checkboxToggle single inverted state display',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        'label' => 'foo',
                        'invertStateDisplay' => true,
                    ],
                ],
            ],
        ],
        'checkbox_19' => [
            'label' => 'checkbox_19',
            'description' => 'renderType=checkboxLabeledToggle single',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxLabeledToggle',
                'items' => [
                    [
                        'label' => 'foo',
                        'labelChecked' => 'Enabled',
                        'labelUnchecked' => 'Disabled',
                    ],
                ],
            ],
        ],
        'checkbox_20' => [
            'label' => 'checkbox_20',
            'description' => 'renderType=checkboxLabeledToggle multiple',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxLabeledToggle',
                'items' => [
                    [
                        'label' => 'foo',
                        'labelChecked' => 'On',
                        'labelUnchecked' => 'Off',
                    ],
                    [
                        'label' => 'bar',
                        'labelChecked' => 'On',
                        'labelUnchecked' => 'Off',
                    ],
                    [
                        'label' => 'inv',
                        'labelChecked' => 'On',
                        'labelUnchecked' => 'Off',
                        'invertStateDisplay' => true,
                    ],
                ],
            ],
        ],
        'checkbox_21' => [
            'label' => 'checkbox_21',
            'description' => 'renderType=checkboxLabeledToggle single inverted state display',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxLabeledToggle',
                'items' => [
                    [
                        'label' => 'foo',
                        'labelChecked' => 'Enabled',
                        'labelUnchecked' => 'Disabled',
                        'invertStateDisplay' => true,
                    ],
                ],
            ],
        ],
        'checkbox_24' => [
            'label' => 'checkbox_24',
            'description' => 'renderType=checkboxToggle multiple',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    ['label' => 'foo'],
                    ['label' => 'bar'],
                    ['label' => 'baz'],
                    ['label' => 'husel'],
                ],
                'cols' => '4',
            ],
        ],
        'checkbox_25' => [
            'label' => 'checkbox_25',
            'description' => 'renderType=checkboxToggle single readOnly',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'readOnly' => true,
                'items' => [
                    [
                        'label' => 'foo',
                    ],
                ],
            ],
        ],
        'checkbox_26' => [
            'label' => 'checkbox_26',
            'description' => 'renderType=checkboxLabeledToggle single readOnly',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxLabeledToggle',
                'readOnly' => true,
                'items' => [
                    [
                        'label' => 'foo',
                        'labelChecked' => 'Enabled',
                        'labelUnchecked' => 'Disabled',
                    ],
                ],
            ],
        ],
        'checkbox_27' => [
            'label' => 'checkbox_27',
            'description' => 'renderType=checkboxToggle multiple with cols=inline',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    ['label' => 'foo'],
                    ['label' => 'bar'],
                    ['label' => 'baz'],
                    ['label' => 'husel'],
                ],
                'cols' => 'inline',
            ],
        ],
        'radio_1' => [
            'label' => 'radio_1',
            'description' => 'radio_1 three options, one without label',
            'config' => [
                'type' => 'radio',
                'items' => [
                    ['label' => 'foo', 'value' => 1],
                    ['label' => '', 'value' => 2],
                    ['label' => 'foobar', 'value' => 3],
                ],
            ],
        ],
        'radio_2' => [
            'label' => 'radio_2',
            'description' => 'three options, long text',
            'config' => [
                'type' => 'radio',
                'items' => [
                    [
                        'label' => 'foo and this here is very long text that maybe does not really fit into the form in one line.'
                        . ' Ok let us add even more text to see how this looks like if wrapped. Is this enough now?'
                        . ' No? Then let us add some even more useless text here!',
                        'value' => 1,
                    ],
                    ['label' => 'bar', 'value' => 2],
                    ['label' => 'foobar', 'value' => 3],
                ],
            ],
        ],
        'radio_3' => [
            'label' => 'radio_3',
            'description' => 'many options',
            'config' => [
                'type' => 'radio',
                'items' => [
                    ['label' => 'foo1', 'value' => 1],
                    ['label' => 'foo2', 'value' => 2],
                    ['label' => 'foo3', 'value' => 3],
                    ['label' => 'foo4', 'value' => 4],
                    ['label' => 'foo5', 'value' => 5],
                    ['label' => 'foo6', 'value' => 6],
                    ['label' => 'foo7', 'value' => 7],
                    ['label' => 'foo8', 'value' => 8],
                    ['label' => 'foo9', 'value' => 9],
                    ['label' => 'foo10', 'value' => 10],
                    ['label' => 'foo11', 'value' => 11],
                    ['label' => 'foo12', 'value' => 12],
                    ['label' => 'foo13', 'value' => 13],
                    ['label' => 'foo14', 'value' => 14],
                    ['label' => 'foo15', 'value' => 15],
                    ['label' => 'foo16', 'value' => 16],
                ],
            ],
        ],
        'radio_4' => [
            'label' => 'radio_4',
            'description' => 'string values',
            'config' => [
                'type' => 'radio',
                'items' => [
                    ['label' => 'nofoo (empty)', 'value' => ''],
                    ['label' => 'foo', 'value' => 'foo'],
                    ['label' => 'bar', 'value' => 'bar'],
                ],
            ],
        ],
        'radio_5' => [
            // @todo: Radio elements added by itemsProcFunc are not persisted correctly.
            // @todo: HTML looks good, so this is probably an issue in DataHandler?
            'label' => 'radio_5',
            'description' => 'itemsProcFunc',
            'config' => [
                'type' => 'radio',
                'items' => [
                    ['label' => 'foo', 'value' => 1],
                    ['label' => 'bar', 'value' => 2],
                ],
                'itemsProcFunc' => 'TYPO3\\CMS\\Styleguide\\UserFunctions\\FormEngine\\TypeRadio5ItemsProcFunc->itemsProcFunc',
            ],
        ],
        'radio_6' => [
            'label' => 'radio_6',
            'description' => 'readonly=1',
            'config' => [
                'type' => 'radio',
                'readOnly' => 1,
                'items' => [
                    ['label' => 'foo', 'value' => 1],
                    ['label' => 'bar', 'value' => 2],
                ],
            ],
        ],

        'none_1' => [
            'label' => 'none_1',
            'description' => 'default',
            'config' => [
                'type' => 'none',
            ],
        ],
        'none_2' => [
            'label' => 'none_2',
            'description' => 'size=6',
            'config' => [
                'type' => 'none',
                'size' => 6,
            ],
        ],
        'none_3' => [
            'label' => 'none_3',
            'description' => 'format=datetime',
            'config' => [
                'type' => 'none',
                'format' => 'datetime',
            ],
        ],
        'none_4' => [
            'label' => 'none_4',
            'description' => 'format=date with format configuration',
            'config' => [
                'type' => 'none',
                'format' => 'date',
                'format.' => [
                    'option' => '%d-%m',
                    'strftime' => true,
                ],
            ],
        ],
        'none_5' => [
            'label' => 'none_5',
            'description' => 'format=date with appendAge',
            'config' => [
                'type' => 'none',
                'format' => 'date',
                'format.' => [
                    'appendAge' => true,
                ],
            ],
        ],

        'passthrough_1' => [
            'label' => 'passthrough_1',
            'description' => 'field should NOT be shown',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'passthrough_2' => [
            'label' => 'passthrough_2',
            'description' => 'not shown, default applied',
            'config' => [
                'type' => 'passthrough',
                'default' => 42,
            ],
        ],

        'user_1' => [
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
        'user_2' => [
            'label' => 'user_2',
            'description' => 'no renderType',
            'config' => [
                'type' => 'user',
            ],
        ],

        'unknown_1' => [
            'label' => 'unknown_1',
            'description' => 'default',
            'config' => [
                'type' => 'input',
                'renderType' => 'unknown',
            ],
        ],

        'flex_1' => [
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
                                        <sheetTitle>input</sheetTitle>
                                        <el>
                                            <input_1>
                                                <label>input_1</label>
                                                <description>field description</description>
                                                <config>
                                                    <type>input</type>
                                                    <eval>trim</eval>
                                                </config>
                                            </input_1>
                                            <input_2>
                                                <label>input_2 renderyType textTable</label>
                                                <description>field description</description>
                                                <config>
                                                    <type>text</type>
                                                    <renderType>textTable</renderType>
                                                    <cols>30</cols>
                                                    <rows>5</rows>
                                                </config>
                                            </input_2>
                                            <input_3>
                                                <label>input_3 valuePicker</label>
                                                <config>
                                                    <type>input</type>
                                                    <valuePicker>
                                                        <items>
                                                            <numIndex index="0">
                                                                <label>Foo</label>
                                                                <value>foo</value>
                                                            </numIndex>
                                                            <numIndex index="1">
                                                                <label>Bar</label>
                                                                <value>bar</value>
                                                            </numIndex>
                                                        </items>
                                                    </valuePicker>
                                                </config>
                                            </input_3>
                                        </el>
                                    </ROOT>
                                </sInput>

                                <sInputDateTime>
                                    <ROOT>
                                        <type>array</type>
                                        <sheetTitle>inputDateTime</sheetTitle>
                                        <el>
                                            <inputDateTime_1>
                                                <label>inputDateTime_1 format=date description</label>
                                                <description>field description</description>
                                                <config>
                                                    <type>datetime</type>
                                                    <format>date</format>
                                                </config>
                                            </inputDateTime_1>
                                            <inputDateTime_2>
                                                <label>inputDateTime_2 dbType=date format=date</label>
                                                <config>
                                                    <type>datetime</type>
                                                    <format>date</format>
                                                    <dbType>date</dbType>
                                                </config>
                                            </inputDateTime_2>
                                            <inputDateTime_3>
                                                <label>inputDateTime_3 type=datetime</label>
                                                <config>
                                                    <type>datetime</type>
                                                </config>
                                            </inputDateTime_3>
                                            <inputDateTime_4>
                                                <label>inputDateTime_4 dbType=datetime format=datetime</label>
                                                <config>
                                                    <type>datetime</type>
                                                    <format>datetime</format>
                                                    <dbType>datetime</dbType>
                                                </config>
                                            </inputDateTime_4>
                                            <inputDateTime_5>
                                                <label>inputDateTime_5 format=time</label>
                                                <config>
                                                    <type>datetime</type>
                                                    <format>time</format>
                                                </config>
                                            </inputDateTime_5>
                                            <inputDateTime_6>
                                                <label>inputDateTime_6 format=timesec</label>
                                                <config>
                                                    <type>datetime</type>
                                                    <format>timesec</format>
                                                </config>
                                            </inputDateTime_6>
                                            <inputDateTime_7>
                                                <label>inputDateTime_7 format=time</label>
                                                <config>
                                                    <type>datetime</type>
                                                    <format>time</format>
                                                    <dbType>time</dbType>
                                                </config>
                                            </inputDateTime_7>
                                            <inputDateTime_8>
                                                <label>inputDateTime_8 format=timesec</label>
                                                <config>
                                                    <type>datetime</type>
                                                    <format>timesec</format>
                                                    <dbType>time</dbType>
                                                </config>
                                            </inputDateTime_8>
                                        </el>
                                    </ROOT>
                                </sInputDateTime>

                                <sText>
                                    <ROOT>
                                        <type>array</type>
                                        <sheetTitle>text</sheetTitle>
                                        <el>
                                            <text_1>
                                                <label>text_1 cols=20, rows=4 description</label>
                                                <description>field description</description>
                                                <config>
                                                    <type>text</type>
                                                    <cols>20</cols>
                                                    <rows>4</rows>
                                                </config>
                                            </text_1>
                                        </el>
                                    </ROOT>
                                </sText>

                                <sLink>
                                    <ROOT>
                                        <type>array</type>
                                        <sheetTitle>link</sheetTitle>
                                        <el>
                                            <link_1>
                                                <label>link_1</label>
                                                <description>field description</description>
                                                <config>
                                                    <type>link</type>
                                                    <allowedTypes>
                                                        <numIndex index="0">page</numIndex>
                                                        <numIndex index="1">file</numIndex>
                                                        <numIndex index="2">url</numIndex>
                                                        <numIndex index="3">record</numIndex>
                                                        <numIndex index="4">telephone</numIndex>
                                                    </allowedTypes>
                                                    <appearance>
                                                        <browserTitle>Link</browserTitle>
                                                    </appearance>
                                                </config>
                                            </link_1>
                                        </el>
                                    </ROOT>
                                </sLink>

                                <sCheck>
                                    <ROOT>
                                        <type>array</type>
                                        <sheetTitle>check</sheetTitle>
                                        <el>
                                            <check_1>
                                                <label>check_1 description</label>
                                                <description>field description</description>
                                                <config>
                                                    <type>check</type>
                                                    <items>
                                                        <numIndex index="0">
                                                            <label>Foo</label>
                                                        </numIndex>
                                                        <numIndex index="1">
                                                            <label>Bar</label>
                                                        </numIndex>
                                                        <numIndex index="2">
                                                            <label>FooBar</label>
                                                        </numIndex>
                                                    </items>
                                                </config>
                                            </check_1>
                                            <check_2>
                                                <label>check_2 invertStateDisplay</label>
                                                <config>
                                                    <type>check</type>
                                                    <items>
                                                        <numIndex index="0">
                                                            <label>Foo</label>
                                                            <invertStateDisplay>1</invertStateDisplay>
                                                        </numIndex>
                                                        <numIndex index="1">
                                                            <label>Bar</label>
                                                            <invertStateDisplay>1</invertStateDisplay>
                                                        </numIndex>
                                                        <numIndex index="2">
                                                            <label>FooBar</label>
                                                            <invertStateDisplay>1</invertStateDisplay>
                                                        </numIndex>
                                                    </items>
                                                </config>
                                            </check_2>
                                        </el>
                                    </ROOT>
                                </sCheck>

                                <sRadio>
                                    <ROOT>
                                        <type>array</type>
                                        <sheetTitle>radio</sheetTitle>
                                        <el>
                                            <radio_1>
                                                <label>radio_1 description</label>
                                                <description>field description</description>
                                                <config>
                                                    <type>radio</type>
                                                    <items>
                                                        <numIndex index="0">
                                                            <label>Foo</label>
                                                            <value>1</value>
                                                        </numIndex>
                                                        <numIndex index="1">
                                                            <label>Bar</label>
                                                            <value>2</value>
                                                        </numIndex>
                                                    </items>
                                                </config>
                                            </radio_1>
                                            <radio_2>
                                                <label>radio_2 description</label>
                                                <description>field description</description>
                                                <config>
                                                    <type>radio</type>
                                                    <items>
                                                        <numIndex index="0">
                                                            <label>NoFoo (empty)</label>
                                                            <value></value>
                                                        </numIndex>
                                                        <numIndex index="1">
                                                            <label>Foo</label>
                                                            <value>fooValue</value>
                                                        </numIndex>
                                                        <numIndex index="2">
                                                            <label>Bar</label>
                                                            <value>barValue</value>
                                                        </numIndex>
                                                    </items>
                                                </config>
                                            </radio_2>
                                        </el>
                                    </ROOT>
                                </sRadio>

                                <sPassthrough>
                                    <ROOT>
                                        <type>array</type>
                                        <sheetTitle>passthrough</sheetTitle>
                                        <el>
                                            <passthrough_1>
                                                <label>passthrough_1</label>
                                                <description>field description</description>
                                                <config>
                                                    <type>passthrough</type>
                                                </config>
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

        'language_1' => [
            'label' => 'language_1',
            'description' => 'simple language selection',
            'config' => [
                'type' => 'language',
            ],
        ],
    ],

    'types' => [
        '0' => [
            'showitem' => '
                --div--;input,
                    input_1, input_40, input_2, input_3, input_41, input_42, input_43, input_4, input_5, input_10,
                    input_11, input_12, input_13, input_15, input_16, input_19,
                    input_21, input_22, input_23, input_26, input_27, input_14, input_28,
                    input_33, input_35, input_36,
                --div--;inputDateTime,
                    inputdatetime_1, inputdatetime_2, inputdatetime_3, inputdatetime_4, inputdatetime_5,
                    inputdatetime_6, inputdatetime_7, inputdatetime_8, inputdatetime_9, inputdatetime_10,
                    inputdatetime_11, inputdatetime_34, inputdatetime_12, inputdatetime_13,
                    inputdatetime_21, inputdatetime_22, inputdatetime_23, inputdatetime_24, inputdatetime_25,
                    inputdatetime_26, inputdatetime_27, inputdatetime_28, inputdatetime_29, inputdatetime_30,
                    inputdatetime_31, inputdatetime_32, inputdatetime_33, inputdatetime_35,
                --div--;link,
                    link_1,link_2,link_3,link_4,link_5,
                --div--;password,
                    password_1,password_2,password_3,password_8,password_4,password_5,password_6,password_7,
                --div--;color,
                    color_1,color_2,color_3,color_4,color_5,color_palpreset,
                --div--;number,
                    number_1, number_2, number_3, number_4, number_5, number_7,
                --div--;email,
                    email_1, email_2, email_3, email_4, email_5,
                --div--;text,
                    text_1, text_2, text_3, text_4, text_5, text_6, text_7, text_9, text_10,
                    text_11, text_12, text_13, text_18, text_14, text_15, text_16, text_17, text_19,
                    text_20,
                --div--;uuid,
                    uuid_1, uuid_2, uuid_3,
                --div--;check,
                    checkbox_1, checkbox_9, checkbox_2, checkbox_17, checkbox_25, checkbox_18, checkbox_24, checkbox_27, checkbox_19, checkbox_26,
                    checkbox_20, checkbox_21, checkbox_22, checkbox_23, checkbox_3, checkbox_4, checkbox_6, checkbox_7, checkbox_8,
                    checkbox_10, checkbox_11, checkbox_12, checkbox_13, checkbox_14, checkbox_15, checkbox_16,
                --div--;radio,
                    radio_1, radio_2, radio_3, radio_4, radio_5, radio_6,
                --div--;language,
                    language_1,
                --div--;none,
                    none_1, none_2, none_3, none_4, none_5,
                --div--;passthrough,
                    passthrough_1, passthrough_2,
                --div--;user,
                    user_1, user_2,
                --div--;unknown,
                    unknown_1,
                --div--;in flex,
                    flex_1,
                --div--;meta,
                    disable, sys_language_uid, l10n_parent, l10n_source,
            ',
        ],
    ],

];

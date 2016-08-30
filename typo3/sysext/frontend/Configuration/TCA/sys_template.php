<?php
return [
    'ctrl' => [
        'label' => 'title',
        'descriptionColumn' => 'description',
        'tstamp' => 'tstamp',
        'sortby' => 'sorting',
        'prependAtCopy' => 'LLL:EXT:lang/locallang_general.xlf:LGL.prependAtCopy',
        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        // Only admin, if any
        'adminOnly' => 1,
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime'
        ],
        'typeicon_column' => 'root',
        'typeicon_classes' => [
            'default' => 'mimetypes-x-content-template-extension',
            '1' => 'mimetypes-x-content-template'
        ],
        'searchFields' => 'title,constants,config'
    ],
    'interface' => [
        'showRecordFieldList' => 'title,clear,root,basedOn,nextLevel,sitetitle,description,hidden,starttime,endtime'
    ],
    'columns' => [
        'title' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.title',
            'config' => [
                'type' => 'input',
                'size' => '25',
                'max' => '255',
                'eval' => 'required'
            ]
        ],
        'hidden' => [
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.disable',
            'exclude' => 1,
            'config' => [
                'type' => 'check',
                'default' => '0'
            ]
        ],
        'starttime' => [
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.starttime',
            'exclude' => 1,
            'config' => [
                'type' => 'input',
                'size' => '13',
                'eval' => 'datetime',
                'default' => '0'
            ]
        ],
        'endtime' => [
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.endtime',
            'exclude' => 1,
            'config' => [
                'type' => 'input',
                'size' => '13',
                'eval' => 'datetime',
                'default' => '0',
                'range' => [
                    'upper' => mktime(0, 0, 0, 12, 31, 2020)
                ]
            ]
        ],
        'root' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.root',
            'config' => [
                'type' => 'check'
            ]
        ],
        'clear' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.clear',
            'config' => [
                'type' => 'check',
                'items' => [
                    ['Constants', ''],
                    ['Setup', '']
                ],
                'cols' => 2
            ]
        ],
        'sitetitle' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.sitetitle',
            'config' => [
                'type' => 'input',
                'size' => '25',
                'max' => '255'
            ]
        ],
        'constants' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.constants',
            'config' => [
                'type' => 'text',
                'cols' => '48',
                'rows' => '10',
                'wrap' => 'OFF',
                'softref' => 'TStemplate,email[subst],url[subst]'
            ],
            'defaultExtras' => 'fixed-font : enable-tab'
        ],
        'nextLevel' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.nextLevel',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'sys_template',
                'show_thumbs' => '1',
                'size' => '1',
                'maxitems' => '1',
                'minitems' => '0',
                'default' => '',
                'wizards' => [
                    'suggest' => [
                        'type' => 'suggest'
                    ]
                ]
            ]
        ],
        'include_static_file' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.include_static_file',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 10,
                'maxitems' => 100,
                'items' => [],
                'enableMultiSelectFilterTextfield' => true,
                'softref' => 'ext_fileref'
            ]
        ],
        'basedOn' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.basedOn',
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'sys_template',
                'show_thumbs' => '1',
                'size' => '3',
                'maxitems' => '50',
                'autoSizeMax' => 10,
                'minitems' => '0',
                'default' => '',
                'wizards' => [
                    '_VERTICAL' => 1,
                    'suggest' => [
                        'type' => 'suggest'
                    ],
                    'edit' => [
                        'type' => 'popup',
                        'title' => 'Edit template',
                        'module' => [
                            'name' => 'wizard_edit',
                        ],
                        'popup_onlyOpenIfSelected' => 1,
                        'icon' => 'actions-open',
                        'JSopenParams' => 'width=800,height=600,status=0,menubar=0,scrollbars=1'
                    ],
                    'add' => [
                        'type' => 'script',
                        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.basedOn_add',
                        'icon' => 'actions-add',
                        'params' => [
                            'table' => 'sys_template',
                            'pid' => '###CURRENT_PID###',
                            'setValue' => 'prepend'
                        ],
                        'module' => [
                            'name' => 'wizard_add'
                        ]
                    ]
                ]
            ]
        ],
        'includeStaticAfterBasedOn' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.includeStaticAfterBasedOn',
            'exclude' => 1,
            'config' => [
                'type' => 'check',
                'default' => '0'
            ]
        ],
        'config' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.config',
            'config' => [
                'type' => 'text',
                'rows' => 10,
                'cols' => 48,
                'wrap' => 'OFF',
                'softref' => 'TStemplate,email[subst],url[subst]'
            ],
            'defaultExtras' => 'fixed-font : enable-tab'
        ],
        'description' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.description',
            'config' => [
                'type' => 'text',
                'rows' => 5,
                'cols' => 48
            ]
        ],
        'static_file_mode' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.static_file_mode',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.static_file_mode.0', '0'],
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.static_file_mode.1', '1'],
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.static_file_mode.2', '2'],
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.static_file_mode.3', '3']
                ],
                'default' => '0'
            ]
        ],
        'tx_impexp_origuid' => ['config' => ['type' => 'passthrough']],
        't3ver_label' => [
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.versionLabel',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'max' => '255'
            ]
        ]
    ],
    'types' => [
        '1' => ['showitem' => '
			hidden, title, sitetitle, constants, config, description,
			--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.tabs.options, clear, root, nextLevel,
			--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.tabs.include, includeStaticAfterBasedOn, include_static_file, basedOn, static_file_mode,
			--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_template.tabs.access, starttime, endtime']
    ]
];

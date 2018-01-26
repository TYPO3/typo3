<?php

return [
    'ctrl' => [
        'title' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect',
        'label' => 'source_host',
        'label_alt' => 'source_path',
        'label_alt_force' => true,
        'crdate' => 'createdon',
        'cruser_id' => 'createdby',
        'tstamp' => 'updatedon',
        'versioningWS' => false,
        'default_sortby' => 'source_host, source_path',
        'rootLevel' => 1,
        'security' => [
            'ignoreWebMountRestriction' => true,
            'ignoreRootLevelRestriction' => true,
        ],
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'disabled',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'typeicon_classes' => [
            'default' => 'mimetypes-x-sys_redirect',
        ],
        'searchFields' => 'source_host,source_path,target,target_statuscode',
    ],
    'interface' => [
        'showRecordFieldList' => 'disabled, source_host, source_path, is_regexp, force_https, keep_query_parameters, target, target_statuscode, hitcount, lasthiton, disable_hitcount',
    ],
    'types' => [
        '1' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general, --palette--;;source, --palette--;;targetdetails,
                --div--;LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:tabs.redirectCount, disable_hitcount, hitcount, lasthiton,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access, --palette--;;visibility'
        ],
    ],
    'palettes' => [
        'visibility' => [
            'showitem' => 'disabled, --linebreak--, starttime, endtime'
        ],
        'source' => [
            'showitem' => 'source_host, --linebreak--, source_path, is_regexp'
        ],
        'targetdetails' => [
            'showitem' => 'target, target_statuscode, --linebreak--, force_https, keep_query_parameters'
        ],
    ],
    'columns' => [
        'disabled' => [
            'exclude' => true,
            'label' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.disabled',
            'config' => [
                'type' => 'check',
                'items' => [
                    '1' => [
                        '0' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.disabled.0'
                    ]
                ]
            ]
        ],
        'starttime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime',
                'default' => 0
            ]
        ],
        'endtime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime',
                'default' => 0,
                'range' => [
                    'upper' => mktime(0, 0, 0, 1, 1, 2038)
                ]
            ]
        ],
        'source_host' => [
            'label' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.source_host',
            'config' => [
                'type' => 'input',
                'eval' => 'trim,required,' . \TYPO3\CMS\Redirects\Evaluation\SourceHost::class,
                // items will be extended by local sys_domain records using dataprovider TYPO3\CMS\Redirects\FormDataProvider\ValuePickerItemDataProvider
                'valuePicker' => [
                    'items' => [
                        [   'LLL:EXT:redirects/Resources/Private/Language/locallang_module_redirect.xlf:source_host_global_text',
                            '*',
                        ],
                    ],
                ],
                'default' => '*',
            ],
        ],
        'source_path' => [
            'label' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.source_path',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,required',
                'placeholder' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_module_redirect.xlf:source_path.placeholder',
            ],
        ],
        'force_https' => [
            'exclude' => true,
            'label' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.force_https',
            'config' => [
                'type' => 'check',
                'default' => 0,
                'items' => [
                    '1' => [
                        '0' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.force_https.0'
                    ]
                ]
            ],
        ],
        'keep_query_parameters' => [
            'exclude' => true,
            'label' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.keep_query_parameters',
            'config' => [
                'type' => 'check',
                'default' => 0,
                'items' => [
                    '1' => [
                        '0' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.keep_query_parameters.0'
                    ]
                ]
            ],
        ],
        'target' => [
            'label' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.target',
            'config' => [
                'type' => 'input',
                'eval' =>'required',
                'renderType' => 'inputLink',
                'softref' => 'typolink',
                'fieldControl' => [
                    'linkPopup' => [
                        'options' => [
                            'blindLinkOptions' => 'mail',
                            'blindLinkFields' => 'class, target'
                        ],
                    ],
                ],
            ],
        ],
        'target_statuscode' => [
            'exclude' => true,
            'label' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.target_statuscode',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.target_statuscode.301', 301],
                    ['LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.target_statuscode.302', 302],
                    ['LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.target_statuscode.303', 303],
                    ['LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.target_statuscode.307', 307],
                ],
                'default' => 307,
                'size' => 1,
            ],
        ],
        'hitcount' => [
            'exclude' => true,
            'label' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.hitcount',
            'config' => [
                'type' => 'input',
                'size' => 5,
                'default' => 0,
                'readOnly' => true
            ],
        ],
        'lasthiton' => [
            'exclude' => true,
            'label' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.lasthiton',
            'config' => [
                'type' => 'input',
                'eval' => 'datetime',
                'renderType' => 'inputDateTime',
                'readOnly' => true
            ],
        ],
        'disable_hitcount' => [
            'exclude' => true,
            'label' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.disable_hitcount',
            'config' => [
                'type' => 'check',
                'items' => [
                    '1' => [
                        '0' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.disable_hitcount.0'
                    ]
                ]
            ],
        ],
        'is_regexp' => [
            'exclude' => true,
            'label' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.is_regexp',
            'config' => [
                'type' => 'check',
                'items' => [
                    '1' => [
                        '0' => 'LLL:EXT:redirects/Resources/Private/Language/locallang_db.xlf:sys_redirect.is_regexp.0'
                    ]
                ]
            ],
        ],
    ],
];

<?php
return [
    'ctrl' => [
        'label' => 'domainName',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'sortby' => 'sorting',
        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_domain',
        'enablecolumns' => [
            'disabled' => 'hidden'
        ],
        'typeicon_classes' => [
            'default' => 'mimetypes-x-content-domain'
        ],
        'searchFields' => 'domainName,redirectTo'
    ],
    'interface' => [
        'showRecordFieldList' => 'hidden,domainName,redirectTo'
    ],
    'columns' => [
        'domainName' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_domain.domainName',
            'config' => [
                'type' => 'input',
                'size' => 35,
                'max' => 255,
                'eval' => 'required,unique,lower,trim,domainname',
                'softref' => 'substitute'
            ]
        ],
        'redirectTo' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_domain.redirectTo',
            'config' => [
                'type' => 'input',
                'size' => 35,
                'max' => 255,
                'default' => '',
                'eval' => 'trim',
                'softref' => 'substitute'
            ]
        ],
        'redirectHttpStatusCode' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_domain.redirectHttpStatusCode',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_domain.redirectHttpStatusCode.301', '301'],
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_domain.redirectHttpStatusCode.302', '302'],
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_domain.redirectHttpStatusCode.303', '303'],
                    ['LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_domain.redirectHttpStatusCode.307', '307']
                ],
                'size' => 1,
                'maxitems' => 1
            ]
        ],
        'hidden' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.disable',
            'exclude' => true,
            'config' => [
                'type' => 'check',
                'default' => 0
            ]
        ],
        'prepend_params' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_domain.prepend_params',
            'exclude' => true,
            'config' => [
                'type' => 'check',
                'default' => 0
            ]
        ],
        'forced' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_domain.forced',
            'exclude' => true,
            'config' => [
                'type' => 'check',
                'default' => 0
            ]
        ]
    ],
    'types' => [
        '1' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    domainName,--palette--;;1, prepend_params, forced,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    hidden,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            ',
        ],
    ],
    'palettes' => [
        '1' => [
            'showitem' => 'redirectTo, redirectHttpStatusCode',
        ],
    ]
];

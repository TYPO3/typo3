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
                'size' => '35',
                'max' => '80',
                'eval' => 'required,unique,lower,trim,domainname',
                'softref' => 'substitute'
            ]
        ],
        'redirectTo' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_domain.redirectTo',
            'config' => [
                'type' => 'input',
                'size' => '35',
                'max' => '255',
                'default' => '',
                'eval' => 'trim',
                'softref' => 'substitute'
            ]
        ],
        'redirectHttpStatusCode' => [
            'exclude' => 1,
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
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.disable',
            'exclude' => 1,
            'config' => [
                'type' => 'check',
                'default' => '0'
            ]
        ],
        'prepend_params' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_domain.prepend_params',
            'exclude' => 1,
            'config' => [
                'type' => 'check',
                'default' => '0'
            ]
        ],
        'forced' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:sys_domain.forced',
            'exclude' => 1,
            'config' => [
                'type' => 'check',
                'default' => '0'
            ]
        ]
    ],
    'types' => [
        '1' => [
            'showitem' => 'hidden, domainName, --palette--;;1, prepend_params, forced',
        ],
    ],
    'palettes' => [
        '1' => [
            'showitem' => 'redirectTo, redirectHttpStatusCode',
        ],
    ]
];

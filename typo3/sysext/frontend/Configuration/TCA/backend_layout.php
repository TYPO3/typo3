<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:backend_layout',
        'label' => 'title',
        'descriptionColumn' => 'description',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'versioningWS' => true,
        'origUid' => 't3_origuid',
        'sortby' => 'sorting',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden'
        ],
        'typeicon_classes' => [
            'default' => 'mimetypes-x-backend_layout'
        ],
        'selicon_field' => 'icon',
        'selicon_field_path' => 'uploads/media'
    ],
    'interface' => [
        'showRecordFieldList' => 'title,config,description,hidden,icon'
    ],
    'columns' => [
        'title' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:backend_layout.title',
            'config' => [
                'type' => 'input',
                'size' => '25',
                'max' => '255',
                'eval' => 'required'
            ]
        ],
        'description' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:backend_layout.description',
            'config' => [
                'type' => 'text',
                'rows' => '5',
                'cols' => '25'
            ]
        ],
        'config' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:backend_layout.config',
            'config' => [
                'type' => 'text',
                'rows' => '5',
                'cols' => '25',
                'wizards' => [
                    0 => [
                        'title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:backend_layout.wizard',
                        'type' => 'popup',
                        'icon' => 'EXT:frontend/Resources/Public/Images/wizard_backend_layout.png',
                        'module' => [
                            'name' => 'wizard_backend_layout'
                        ],
                        'JSopenParams' => 'height=800,width=800,status=0,menubar=0,scrollbars=0'
                    ]
                ]
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
        'icon' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:backend_layout.icon',
            'exclude' => 1,
            'config' => [
                'type' => 'group',
                'internal_type' => 'file',
                'allowed' => 'jpg,gif,png',
                'uploadfolder' => 'uploads/media',
                'show_thumbs' => 1,
                'size' => 1,
                'maxitems' => 1
            ]
        ]
    ],
    'types' => [
        '1' => [
            'showitem' => 'hidden, title, icon, description, config',
        ],
    ]
];

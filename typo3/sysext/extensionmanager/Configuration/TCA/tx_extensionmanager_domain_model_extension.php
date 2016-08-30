<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:extensionmanager/Resources/Private/Language/locallang_db.xlf:tx_extensionmanager_domain_model_extension',
        'label' => 'uid',
        'default_sortby' => '',
        'hideTable' => true,
        'rootLevel' => true,
        'adminOnly' => true,
        'typeicon_classes' => [
            'default' => 'empty-icon'
        ]
    ],
    'interface' => [
        'showRecordFieldList' => 'extension_key,version,integer_version,title,description,state,category,last_updated,update_comment,author_name,author_email,md5hash,serialized_dependencies'
    ],
    'columns' => [
        'extension_key' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xlf:tx_extensionmanager_domain_model_extension.extensionkey',
            'config' => [
                'type' => 'input',
                'size' => '30'
            ]
        ],
        'version' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xlf:tx_extensionmanager_domain_model_extension.version',
            'config' => [
                'type' => 'input',
                'size' => '30'
            ]
        ],
        'alldownloadcounter' => [
            'config' => [
                'type' => 'passthrough'
            ]
        ],
        'integer_version' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xlf:tx_extensionmanager_domain_model_extension.integerversion',
            'config' => [
                'type' => 'input',
                'size' => '30'
            ]
        ],
        'title' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xlf:tx_extensionmanager_domain_model_extension.title',
            'config' => [
                'type' => 'input',
                'size' => '30'
            ]
        ],
        'description' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xlf:tx_extensionmanager_domain_model_extension.description',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '5'
            ]
        ],
        'state' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xlf:tx_extensionmanager_domain_model_extension.state',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'range' => ['lower' => 0, 'upper' => 1000],
                'eval' => 'int'
            ]
        ],
        'category' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xlf:tx_extensionmanager_domain_model_extension.category',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'range' => ['lower' => 0, 'upper' => 1000],
                'eval' => 'int'
            ]
        ],
        'last_updated' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xlf:tx_extensionmanager_domain_model_extension.lastupdated',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'datetime'
            ]
        ],
        'update_comment' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xlf:tx_extensionmanager_domain_model_extension.updatecomment',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '5'
            ]
        ],
        'author_name' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xlf:tx_extensionmanager_domain_model_extension.authorname',
            'config' => [
                'type' => 'input',
                'size' => '30'
            ]
        ],
        'author_email' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xlf:tx_extensionmanager_domain_model_extension.authoremail',
            'config' => [
                'type' => 'input',
                'size' => '30'
            ]
        ],
        'current_version' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xlf:tx_extensionmanager_domain_model_extension.currentversion',
            'config' => [
                'type' => 'check',
                'size' => '1'
            ]
        ],
        'review_state' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xlf:tx_extensionmanager_domain_model_extension.reviewstate',
            'config' => [
                'type' => 'check',
                'size' => '1'
            ]
        ],
        'md5hash' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xlf:tx_extensionmanager_domain_model_extension.md5hash',
            'config' => [
                'type' => 'input',
                'size' => '1',
            ],
        ],
        'serialized_dependencies' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xlf:tx_extensionmanager_domain_model_extension.serializedDependencies',
            'config' => [
                'type' => 'input',
                'size' => '30',
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'extensionkey, version, integer_version, title, description, state, category, last_updated, update_comment, author_name, author_email, review_state, md5hash, serialized_dependencies']
    ],
    'palettes' => [
        '1' => ['showitem' => '']
    ]
];

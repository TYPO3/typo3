<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:extensionmanager/Resources/Private/Language/locallang_db.xlf:tx_extensionmanager_domain_model_repository',
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
        'showRecordFieldList' => 'title,description,wsdl_url_mirror_list_url,last_update,extension_count'
    ],
    'columns' => [
        'title' => [
            'label' => 'LLL:EXT:extensionmanager/Resources/Private/Language/locallang_db.xlf:tx_extensionmanager_domain_model_repository.title',
            'config' => [
                'type' => 'input',
                'size' => 30
            ],
        ],
        'description' => [
            'label' => 'LLL:EXT:extensionmanager/Resources/Private/Language/locallang_db.xlf:tx_extensionmanager_domain_model_repository.description',
            'config' => [
                'type' => 'input',
                'size' => 30
            ],
        ],
        'wsdl_url' => [
            'label' => 'LLL:EXT:extensionmanager/Resources/Private/Language/locallang_db.xlf:tx_extensionmanager_domain_model_repository.wsdlUrl',
            'config' => [
                'type' => 'input',
                'size' => 30
            ],
        ],
        'mirror_list_url' => [
            'label' => 'LLL:EXT:extensionmanager/Resources/Private/Language/locallang_db.xlf:tx_extensionmanager_domain_model_repository.mirrorListUrl',
            'config' => [
                'type' => 'text',
                'cols' => 30,
            ],
        ],
        'last_update' => [
            'label' => 'LLL:EXT:extensionmanager/Resources/Private/Language/locallang_db.xlf:tx_extensionmanager_domain_model_repository.lastUpdate',
            'config' => [
                'type' => 'input',
                'size' => 30,
            ],
        ],
        'extension_count' => [
            'label' => 'LLL:EXT:extensionmanager/Resources/Private/Language/locallang_db.xlf:tx_extensionmanager_domain_model_repository.extensionCount',
            'config' => [
                'type' => 'input',
                'size' => 30,
            ],
        ],
    ],
    'types' => [
        '0' => ['showitem' => 'title, description, wsdl_url, mirror_list_url, last_update, extension_count'],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
];

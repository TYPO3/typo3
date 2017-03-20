<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_file_storage',
        'label' => 'name',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'name',
        'delete' => 'deleted',
        'descriptionColumn' => 'description',
        'rootLevel' => true,
        'versioningWS_alwaysAllowLiveEdit' => true, // Only have LIVE records of file storages
        'enablecolumns' => [],
        'typeicon_classes' => [
            'default' => 'mimetypes-x-sys_file_storage'
        ],
        'searchFields' => 'name,description'
    ],
    'interface' => [
        'showRecordFieldList' => 'name,description,driver,processingfolder,configuration,auto_extract_metadata'
    ],
    'columns' => [
        'name' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_file_storage.name',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'required'
            ]
        ],
        'description' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_file_storage.description',
            'config' => [
                'type' => 'text',
                'cols' => 30,
                'rows' => 5
            ]
        ],
        'is_browsable' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_file_storage.is_browsable',
            'config' => [
                'type' => 'check',
                'default' => 1
            ]
        ],
        'is_default' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_file_storage.is_default',
            'config' => [
                'type' => 'check',
                'default' => 0,
                'eval' => 'maximumRecordsChecked',
                'validation' => [
                    'maximumRecordsChecked' => 1
                ]
            ]
        ],
        'is_public' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_file_storage.is_public',
            'config' => [
                'default' => true,
                'type' => 'user',
                'userFunc' => \TYPO3\CMS\Core\Resource\Service\UserStorageCapabilityService::class . '->renderIsPublic',
            ]
        ],
        'is_writable' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_file_storage.is_writable',
            'config' => [
                'type' => 'check',
                'default' => 1
            ]
        ],
        'is_online' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_file_storage.is_online',
            'config' => [
                'type' => 'check',
                'default' => 1
            ]
        ],
        'auto_extract_metadata' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_file_storage.auto_extract_metadata',
            'config' => [
                'type' => 'check',
                'default' => 1
            ]
        ],
        'processingfolder' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_file_storage.processingfolder',
            'config' => [
                'type' => 'input',
                'placeholder' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_file_storage.processingfolder.placeholder',
                'size' => 20
            ]
        ],
        'driver' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_file_storage.driver',
            'onChange' => 'reload',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [],
                'default' => 'Local',
                'onChange' => 'reload'
            ]
        ],
        'configuration' => [
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_tca.xlf:sys_file_storage.configuration',
            'config' => [
                'type' => 'flex',
                'ds_pointerField' => 'driver',
                'ds' => []
            ],
        ]
    ],
    'types' => [
        '0' => ['showitem' => '
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    name, driver, configuration, is_default, auto_extract_metadata, processingfolder, 
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:accesscapabilities,
                    --palette--;Capabilities;capabilities,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    is_online,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
                description,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
        ']
    ],
    'palettes' => [
        'capabilities' => [
            'showitem' => 'is_browsable, is_public, is_writable',
        ],
    ],
];

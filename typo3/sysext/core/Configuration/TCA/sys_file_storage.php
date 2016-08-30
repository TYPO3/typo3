<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_storage',
        'label' => 'name',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY name',
        'delete' => 'deleted',
        'rootLevel' => true,
        'versioningWS_alwaysAllowLiveEdit' => true, // Only have LIVE records of file storages
        'enablecolumns' => [],
        'requestUpdate' => 'driver',
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
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_storage.name',
            'config' => [
                'type' => 'input',
                'size' => '30',
                'eval' => 'required'
            ]
        ],
        'description' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_storage.description',
            'config' => [
                'type' => 'text',
                'cols' => '30',
                'rows' => '5'
            ]
        ],
        'is_browsable' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_storage.is_browsable',
            'config' => [
                'type' => 'check',
                'default' => 1
            ]
        ],
        'is_default' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_storage.is_default',
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
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_storage.is_public',
            'config' => [
                'default' => true,
                'type' => 'user',
                'userFunc' => \TYPO3\CMS\Core\Resource\Service\UserStorageCapabilityService::class . '->renderIsPublic',
            ]
        ],
        'is_writable' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_storage.is_writable',
            'config' => [
                'type' => 'check',
                'default' => 1
            ]
        ],
        'is_online' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_storage.is_online',
            'config' => [
                'type' => 'check',
                'default' => 1
            ]
        ],
        'auto_extract_metadata' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_storage.auto_extract_metadata',
            'config' => [
                'type' => 'check',
                'default' => 1
            ]
        ],
        'processingfolder' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_storage.processingfolder',
            'config' => [
                'type' => 'input',
                'placeholder' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_storage.processingfolder.placeholder',
                'size' => '20'
            ]
        ],
        'driver' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_storage.driver',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [],
                'default' => 'Local',
                'onChange' => 'reload'
            ]
        ],
        'configuration' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_storage.configuration',
            'config' => [
                'type' => 'flex',
                'ds_pointerField' => 'driver',
                'ds' => []
            ],
        ]
    ],
    'types' => [
        '0' => ['showitem' => 'name, description, --div--;Configuration, driver, configuration, is_default, auto_extract_metadata, processingfolder, --div--;Access, --palette--;Capabilities;capabilities, is_online']
    ],
    'palettes' => [
        'capabilities' => [
            'showitem' => 'is_browsable, is_public, is_writable',
        ],
    ],
];

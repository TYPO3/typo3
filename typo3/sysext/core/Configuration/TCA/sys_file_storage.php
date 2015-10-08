<?php
return array(
    'ctrl' => array(
        'title' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_storage',
        'label' => 'name',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY name',
        'delete' => 'deleted',
        'rootLevel' => true,
        'versioningWS_alwaysAllowLiveEdit' => true, // Only have LIVE records of file storages
        'enablecolumns' => array(),
        'requestUpdate' => 'driver',
        'typeicon_classes' => array(
            'default' => 'mimetypes-x-sys_file_storage'
        ),
        'searchFields' => 'name,description'
    ),
    'interface' => array(
        'showRecordFieldList' => 'name,description,driver,processingfolder,configuration,auto_extract_metadata'
    ),
    'columns' => array(
        'name' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_storage.name',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'required'
            )
        ),
        'description' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_storage.description',
            'config' => array(
                'type' => 'text',
                'cols' => '30',
                'rows' => '5'
            )
        ),
        'is_browsable' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_storage.is_browsable',
            'config' => array(
                'type' => 'check',
                'default' => 1
            )
        ),
        'is_default' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_storage.is_default',
            'config' => array(
                'type' => 'check',
                'default' => 0,
                'eval' => 'maximumRecordsChecked',
                'validation' => array(
                    'maximumRecordsChecked' => 1
                )
            )
        ),
        'is_public' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_storage.is_public',
            'config' => array(
                'default' => true,
                'type' => 'user',
                'userFunc' => \TYPO3\CMS\Core\Resource\Service\UserStorageCapabilityService::class . '->renderIsPublic',
            )
        ),
        'is_writable' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_storage.is_writable',
            'config' => array(
                'type' => 'check',
                'default' => 1
            )
        ),
        'is_online' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_storage.is_online',
            'config' => array(
                'type' => 'check',
                'default' => 1
            )
        ),
        'auto_extract_metadata' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_storage.auto_extract_metadata',
            'config' => array(
                'type' => 'check',
                'default' => 1
            )
        ),
        'processingfolder' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_storage.processingfolder',
            'config' => array(
                'type' => 'input',
                'placeholder' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_storage.processingfolder.placeholder',
                'size' => '20'
            )
        ),
        'driver' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_storage.driver',
            'config' => array(
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => array(),
                'default' => 'Local',
                'onChange' => 'reload'
            )
        ),
        'configuration' => array(
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_storage.configuration',
            'config' => array(
                'type' => 'flex',
                'ds_pointerField' => 'driver',
                'ds' => array()
            ),
        )
    ),
    'types' => array(
        '0' => array('showitem' => 'name, description, --div--;Configuration, driver, configuration, is_default, auto_extract_metadata, processingfolder, --div--;Access, --palette--;Capabilities;capabilities, is_online')
    ),
    'palettes' => array(
        'capabilities' => array(
            'showitem' => 'is_browsable, is_public, is_writable',
        ),
    ),
);

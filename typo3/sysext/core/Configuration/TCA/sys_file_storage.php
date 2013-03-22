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
		'rootLevel' => TRUE,
		'versioningWS_alwaysAllowLiveEdit' => TRUE, // Only have LIVE records of file storages
		'enablecolumns' => array(
			'disabled' => 'hidden'
		),
		'dividers2tabs' => TRUE,
		'requestUpdate' => 'driver',
		'iconfile' => '_icon_ftp.gif',
	),
	'interface' => array(
		'showRecordFieldList' => 'hidden,name,description,driver,processingfolder,configuration'
	),
	'columns' => array(
		'hidden' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_storage.hidden',
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),
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
		'is_public' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_storage.is_public',
			'config' => array(
				'type' => 'check',
				'default' => 1
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
		'processingfolder' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_storage.processingfolder',
			'config' => array(
				'type' => 'input',
				'placeholder' => \TYPO3\CMS\Core\Resource\ResourceStorage::DEFAULT_ProcessingFolder,
				'size' => '20'
			)
		),
		'driver' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_storage.driver',
			'config' => array(
				'type' => 'select',
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
		'0' => array('showitem' => 'name, description, hidden, --div--;Configuration, driver, configuration, processingfolder, --div--;Access, --palette--;Capabilities;capabilities, is_online')
	),
	'palettes' => array(
		'capabilities' => array('showitem' => 'is_browsable, is_public, is_writable', 'canNotCollapse' => TRUE)
	)
);
?>
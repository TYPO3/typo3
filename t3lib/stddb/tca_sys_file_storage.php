<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

/**
 * File storages
 */
$TCA['sys_file_storage'] = array (
	'ctrl' => $TCA['sys_file_storage']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'hidden,name,description,driver,processingfolder,configuration'
	),
	'feInterface' => $TCA['sys_file_storage']['feInterface'],
	'columns' => array (
		'hidden' => array (
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		'name' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_storage.name',
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
		'description' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_storage.description',
			'config' => array (
				'type' => 'text',
				'cols' => '30',
				'rows' => '5',
			)
		),
		'is_browsable' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_storage.is_browsable',
			'config' => array (
				'type' => 'check',
				'default' => 1
			)
		),
		'is_public' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_storage.is_public',
			'config' => array (
				'type' => 'check',
				'default' => 1
			)
		),
		'is_writable' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_storage.is_writable',
			'config' => array (
				'type' => 'check',
				'default' => 1
			)
		),
		'processingfolder' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_storage.processingfolder',
			'config' => array (
				'type' => 'input',
				'placeholder' => t3lib_file_Storage::DEFAULT_ProcessingFolder,
				'size' => '20',
			)
		),
		'driver' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_storage.driver',
			'config' => array (
				'type' => 'select',
				'items' => array(),
				'default' => '',
			)
		),
		'configuration' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_storage.configuration',
			'config' => array (
				'type' => 'flex',
				'ds_pointerField' => 'driver',
				'ds' => array(),
			)
		),
	),
	'types' => array(
		'0' => array('showitem' => 'hidden;;1;;1-1-1, name, --palette--;Capabilities;capabilities, description, processingfolder, driver, configuration'),
	),
	'palettes' => array(
		'capabilities' => array('showitem' => 'is_browsable, is_public, is_writable', 'canNotCollapse' => TRUE)
	)
);

/** @var t3lib_file_Driver_DriverRegistry $registry */
$registry = t3lib_div::makeInstance('t3lib_file_Driver_DriverRegistry');
$registry->addDriversToTCA();

?>
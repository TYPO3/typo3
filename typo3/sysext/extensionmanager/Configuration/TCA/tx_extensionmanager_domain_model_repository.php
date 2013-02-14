<?php
return array(
	'ctrl' => array(
		'title' => 'LLL:EXT:extensionmanager/Resources/Private/Language/locallang_db.xml:tx_extensionmanager_domain_model_repository',
		'label' => 'uid',
		'default_sortby' => '',
		'hideTable' => TRUE,
	),
	'interface' => array(
		'showRecordFieldList' => 'title,description,wsdl_url_mirror_list_url,last_update,extension_count'
	),
	'columns' => array(
		'title' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xml:tx_extensionmanager_domain_model_repository.title',
			'config' => array(
				'type' => 'input',
				'size' => '30'
			),
		),
		'description' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xml:tx_extensionmanager_domain_model_repository.description',
			'config' => array(
				'type' => 'input',
				'size' => '30'
			),
		),
		'wsdl_url' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xml:tx_extensionmanager_domain_model_repository.wsdlUrl',
			'config' => array(
				'type' => 'input',
				'size' => '30'
			),
		),
		'mirror_list_url' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xml:tx_extensionmanager_domain_model_repository.mirrorListUrl',
			'config' => array(
				'type' => 'text',
				'cols' => '30',
			),
		),
		'last_update' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xml:tx_extensionmanager_domain_model_repository.lastUpdate',
			'config' => array(
				'type' => 'input',
				'size' => '30',
			),
		),
		'extension_count' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xml:tx_extensionmanager_domain_model_repository.extensionCount',
			'config' => array(
				'type' => 'input',
				'size' => '30',
			),
		),
	),
	'types' => array(
		'0' => array('showitem' => 'title;;;;1-1-1, description;;;;1-1-1, wsdl_url, mirror_list_url, last_update, extension_count'),
	),
	'palettes' => array(
		'1' => array('showitem' => ''),
	),
);
?>
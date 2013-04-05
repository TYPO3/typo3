<?php
return array(
	'ctrl' => array(
		'title' => 'LLL:EXT:extensionmanager/Resources/Private/Language/locallang_db.xml:tx_extensionmanager_domain_model_extension',
		'label' => 'uid',
		'default_sortby' => '',
		'hideTable' => TRUE
	),
	'interface' => array(
		'showRecordFieldList' => 'extension_key,version,integer_version,title,description,state,category,last_updated,update_comment,author_name,author_email,md5hash,serialized_dependencies'
	),
	'columns' => array(
		'extension_key' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xml:tx_extensionmanager_domain_model_extension.extensionkey',
			'config' => array(
				'type' => 'input',
				'size' => '30'
			)
		),
		'version' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xml:tx_extensionmanager_domain_model_extension.version',
			'config' => array(
				'type' => 'input',
				'size' => '30'
			)
		),
		'alldownloadcounter' => array(
			'config' => array(
				'type' => 'passthrough'
			)
		),
		'integer_version' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xml:tx_extensionmanager_domain_model_extension.integerversion',
			'config' => array(
				'type' => 'input',
				'size' => '30'
			)
		),
		'title' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xml:tx_extensionmanager_domain_model_extension.title',
			'config' => array(
				'type' => 'input',
				'size' => '30'
			)
		),
		'description' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xml:tx_extensionmanager_domain_model_extension.description',
			'config' => array(
				'type' => 'text',
				'cols' => '30',
				'rows' => '5'
			)
		),
		'state' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xml:tx_extensionmanager_domain_model_extension.state',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'range' => array('lower' => 0, 'upper' => 1000),
				'eval' => 'int'
			)
		),
		'category' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xml:tx_extensionmanager_domain_model_extension.category',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'range' => array('lower' => 0, 'upper' => 1000),
				'eval' => 'int'
			)
		),
		'last_updated' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xml:tx_extensionmanager_domain_model_extension.lastupdated',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'datetime'
			)
		),
		'update_comment' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xml:tx_extensionmanager_domain_model_extension.updatecomment',
			'config' => array(
				'type' => 'text',
				'cols' => '30',
				'rows' => '5'
			)
		),
		'author_name' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xml:tx_extensionmanager_domain_model_extension.authorname',
			'config' => array(
				'type' => 'input',
				'size' => '30'
			)
		),
		'author_email' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xml:tx_extensionmanager_domain_model_extension.authoremail',
			'config' => array(
				'type' => 'input',
				'size' => '30'
			)
		),
		'current_version' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xml:tx_extensionmanager_domain_model_extension.currentversion',
			'config' => array(
				'type' => 'check',
				'size' => '1'
			)
		),
		'review_state' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xml:tx_extensionmanager_domain_model_extension.reviewstate',
			'config' => array(
				'type' => 'check',
				'size' => '1'
			)
		),
		'md5hash' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xml:tx_extensionmanager_domain_model_extension.md5hash',
			'config' => array(
				'type' => 'input',
				'size' => '1',
			),
		),
		'serialized_dependencies' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xml:tx_extensionmanager_domain_model_extension.serializedDependencies',
			'config' => array(
				'type' => 'input',
				'size' => '30',
			),
		),
	),
	'types' => array(
		'0' => array('showitem' => 'extensionkey;;;;1-1-1, version, integer_version, title;;;;2-2-2, description;;;;3-3-3, state, category, last_updated, update_comment, author_name, author_email, review_state, md5hash, serialized_dependencies')
	),
	'palettes' => array(
		'1' => array('showitem' => '')
	)
);
?>
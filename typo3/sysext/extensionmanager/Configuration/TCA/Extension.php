<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

$TCA['tx_extensionmanager_domain_model_extension'] = array(
	'ctrl' => $TCA['tx_extensionmanager_domain_model_extension']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'extensionkey,version,title,description,state,category,lastupdated,updatecomment,authorname,authoremail'
	),
	'feInterface' => $TCA['tx_extensionmanager_domain_model_extension']['feInterface'],
	'columns' => array(
		'extension_key' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xml:tx_extensionmanager_domain_model_extension.extensionkey',
			'config' => array(
				'type' => 'input',
				'size' => '30',
			)
		),
		'version' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xml:tx_extensionmanager_domain_model_extension.version',
			'config' => array(
				'type' => 'input',
				'size' => '30',
			)
		),
		'title' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xml:tx_extensionmanager_domain_model_extension.title',
			'config' => array(
				'type' => 'input',
				'size' => '30',
			)
		),
		'description' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xml:tx_extensionmanager_domain_model_extension.description',
			'config' => array(
				'type' => 'text',
				'cols' => '30',
				'rows' => '5',
			)
		),
		'state' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xml:tx_extensionmanager_domain_model_extension.state',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'range' => array('lower' => 0, 'upper' => 1000),
				'eval' => 'int',
			)
		),
		'category' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xml:tx_extensionmanager_domain_model_extension.category',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'range' => array('lower' => 0, 'upper' => 1000),
				'eval' => 'int',
			)
		),
		'last_updated' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xml:tx_extensionmanager_domain_model_extension.lastupdated',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'datetime',
			)
		),
		'update_comment' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xml:tx_extensionmanager_domain_model_extension.updatecomment',
			'config' => array(
				'type' => 'text',
				'cols' => '30',
				'rows' => '5',
			)
		),
		'author_name' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xml:tx_extensionmanager_domain_model_extension.authorname',
			'config' => array(
				'type' => 'input',
				'size' => '30',
			)
		),
		'author_email' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xml:tx_extensionmanager_domain_model_extension.authoremail',
			'config' => array(
				'type' => 'input',
				'size' => '30',
			)
		),
		'lastversion' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xml:tx_extensionmanager_domain_model_extension.lastversion',
			'config' => array(
				'type' => 'check',
				'size' => '1',
			)
		),
		'position' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:extensionmanager/Resources/Private/locallang_db.xml:tx_extensionmanager_domain_model_extension.lastversion',
			'config' => array(
				'type' => 'input',
				'size' => '10',
				'eval' => 'int'
			)
		),
	),
	'types' => array(
		'0' => array('showitem' => 'extensionkey;;;;1-1-1, version, title;;;;2-2-2, description;;;;3-3-3, state, category, lastupdated, updatecomment, authorname, authoremail')
	),
	'palettes' => array(
		'1' => array('showitem' => '')
	)
);
?>

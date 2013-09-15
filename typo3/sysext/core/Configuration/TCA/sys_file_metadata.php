<?php
return array(
	'ctrl' => array(
		'title' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file',
		'label' => 'file',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'type' => 'type',
		'hideTable' => TRUE,
		'rootLevel' => TRUE,
		'versioningWS' => TRUE,
		'origUid' => 't3_origuid',
		'default_sortby' => 'ORDER BY crdate DESC',
		'dividers2tabs' => TRUE,
		'typeicon_column' => '__row|file|type',
		'typeicon_classes' => array(
			'1' => 'mimetypes-text-text',
			'2' => 'mimetypes-media-image',
			'3' => 'mimetypes-media-audio',
			'4' => 'mimetypes-media-video',
			'5' => 'mimetypes-application',
			'default' => 'mimetypes-other-other'
		),
		'security' => array(
			'ignoreWebMountRestriction' => TRUE,
			'ignoreRootLevelRestriction' => TRUE,
		),
	),
	'interface' => array(
		'showRecordFieldList' => 'file, title, description, alternative'
	),
	'columns' => array(
		't3ver_label' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.versionLabel',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'max' => '30'
			)
		),
		'fileinfo' => array(
			'config' => array(
				'type' => 'user',
				'userFunc' => 'typo3/sysext/core/Classes/Resource/Hook/FileInfoHook.php:TYPO3\CMS\Core\Resource\Hook\FileInfoHook->renderFileMetadataInfo'
			)
		),
		'file' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file',
			'config' => array(
				'readOnly' => 1,
				'type' => 'select',
				'foreign_table' => 'sys_file',
				'minitems' => 1,
				'maxitems' => 1,
				'size' => 1,
			)
		),
		'title' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file.title',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'placeholder' => '__row|file|name'
			)
		),
		'description' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file.description',
			'config' => array(
				'type' => 'text',
				'cols' => '40',
				'rows' => '3'
			)
		),
		'alternative' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file.alternative',
			'config' => array(
				'type' => 'text',
				'cols' => '40',
				'rows' => '3'
			)
		),
	),
	'types' => array(
		'1' => array('showitem' => 'fileinfo, title, description, alternative')
	),
	'palettes' => array()
);

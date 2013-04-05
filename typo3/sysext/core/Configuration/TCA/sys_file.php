<?php
return array(
	'ctrl' => array(
		'title' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file',
		'label' => 'name',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'type' => 'type',
		'hideTable' => TRUE,
		'rootLevel' => TRUE,
		'versioningWS' => TRUE,
		'origUid' => 't3_origuid',
		'default_sortby' => 'ORDER BY crdate DESC',
		'delete' => 'deleted',
		'dividers2tabs' => TRUE,
		'typeicon_column' => 'type',
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
		'showRecordFieldList' => 'storage, name, description, alternative, type, mime_type, size, sha1'
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
				'userFunc' => 'typo3/sysext/core/Classes/Resource/Hook/FileInfoHook.php:TYPO3\CMS\Core\Resource\Hook\FileInfoHook->renderFileInfo'
			)
		),
		'storage' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file.storage',
			'config' => array(
				'readOnly' => 1,
				'type' => 'select',
				'items' => array(
					array('', 0)
				),
				'foreign_table' => 'sys_file_storage',
				'foreign_table_where' => 'ORDER BY sys_file_storage.name',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1
			)
		),
		'identifier' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file.identifier',
			'config' => array(
				'readOnly' => 1,
				'type' => 'input',
				'size' => '30'
			)
		),
		'name' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file.name',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'required',
				'readOnly' => TRUE
			)
		),
		'title' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file.title',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'placeholder' => '__row|name'
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
		'type' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file.type',
			'config' => array(
				'readOnly' => 1,
				'type' => 'select',
				'size' => '1',
				'items' => array(
					array('LLL:EXT:lang/locallang_tca.xlf:sys_file.type.unknown', 0),
					array('LLL:EXT:lang/locallang_tca.xlf:sys_file.type.text', 1),
					array('LLL:EXT:lang/locallang_tca.xlf:sys_file.type.image', 2),
					array('LLL:EXT:lang/locallang_tca.xlf:sys_file.type.audio', 3),
					array('LLL:EXT:lang/locallang_tca.xlf:sys_file.type.video', 4),
					array('LLL:EXT:lang/locallang_tca.xlf:sys_file.type.software', 5)
				)
			)
		),
		'mime_type' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file.mime_type',
			'config' => array(
				'readOnly' => 1,
				'type' => 'input',
				'size' => '30'
			)
		),
		'sha1' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file.sha1',
			'config' => array(
				'readOnly' => 1,
				'type' => 'input',
				'size' => '30',
			)
		),
		'size' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file.size',
			'config' => array(
				'readOnly' => 1,
				'type' => 'input',
				'size' => '8',
				'max' => '30',
				'eval' => 'int',
				'default' => 0
			)
		)
	),
	'types' => array(
		'1' => array('showitem' => 'fileinfo, name, title, description, alternative, storage')
	),
	'palettes' => array()
);
?>
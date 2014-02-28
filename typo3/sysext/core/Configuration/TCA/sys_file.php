<?php
return array(
	'ctrl' => array(
		'title' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file',
		'label' => 'name',
		'tstamp' => 'tstamp',
		'type' => 'type',
		'hideTable' => TRUE,
		'rootLevel' => TRUE,
		'default_sortby' => 'ORDER BY name ASC',
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
		'searchFields' => 'name, type, mime_type, sha1'
	),
	'interface' => array(
		'showRecordFieldList' => 'storage, name, type, mime_type, size, sha1, missing'
	),
	'columns' => array(
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
				'readOnly' => 1,
				'type' => 'input',
				'size' => '30',
				'eval' => 'required',
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
		),
		'missing' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file.missing',
			'config' => array(
				'readOnly' => 1,
				'type' => 'check',
				'default' => 0
			)
		),
		'metadata' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file.metadata',
			'config' => array(
				'readOnly' => 1,
				'type' => 'inline',
				'foreign_table' => 'sys_file_metadata',
				'foreign_field' => 'file',
				'size' => 1,
				'minitems' => 1,
				'maxitems' => 1,
			)
		)
	),
	'types' => array(
		'1' => array('showitem' => 'fileinfo, storage, missing')
	),
	'palettes' => array()
);

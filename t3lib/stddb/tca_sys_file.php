<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$TCA['sys_file'] = array (
	'ctrl' => $TCA['sys_file']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'name,identifier,storage,type,sha1,size'
	),
	'feInterface' => $TCA['sys_file']['feInterface'],
	'columns' => array (
		't3ver_label' => array (
			'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.versionLabel',
			'config' => array (
				'type' => 'input',
				'size' => '30',
				'max'  => '30',
			)
		),
		'storage' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file.storage',
			'config' => array (
				'readOnly' => 1,
				'type' => 'select',
				'items' => array (
					array('',0),
				),
				'foreign_table' => 'sys_file_storage',
				'foreign_table_where' => 'ORDER BY sys_file_storage.name',
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1,
			)
		),
		'identifier' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file.identifier',
			'config' => array (
				'readOnly' => 1,
				'type' => 'input',
				'size' => '30',
			)
		),
		'name' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file.name',
			'config' => array (
				'type' => 'input',
				'size' => '30',
			)
		),
		'type' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file.type',
			'config' => array (
				'readOnly' => 1,
				'type' => 'select',
				'size' => '1',
				'items' => array(
					array('LLL:EXT:lang/locallang_tca.xlf:sys_file.type.unknown',  0),
					array('LLL:EXT:lang/locallang_tca.xlf:sys_file.type.text',     1),
					array('LLL:EXT:lang/locallang_tca.xlf:sys_file.type.image',    2),
					array('LLL:EXT:lang/locallang_tca.xlf:sys_file.type.audio',    3),
					array('LLL:EXT:lang/locallang_tca.xlf:sys_file.type.video',    4),
					array('LLL:EXT:lang/locallang_tca.xlf:sys_file.type.software', 5),
				),
			)
		),
		'mime_type' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file.mime_type',
			'config' => array (
				'readOnly' => 1,
				'type' => 'input',
				'size' => '30',
			)
		),
		'sha1' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file.sha1',
			'config' => array (
				'readOnly' => 1,
				'type' => 'input',
				'size' => '30',
				'readOnly' => 1,
			)
		),
		'size' => array (
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file.size',
			'config' => array (
				'readOnly' => 1,
				'type'     => 'input',
				'size'     => '8',
				'max'      => '30',
				'eval'     => 'int',
				'default' => 0
			)
		),
		'usage_count' => array (
			'exclude'     => 1,
			'label'       => 'LLL:EXT:lang/locallang_general.xml:usage_count',
			'config' => array (
				'type' => 'group',
				'internal_type' => 'db',
				'allowed' => '*',
				'size' => 5,
				'minitems' => 0,
				'maxitems' => 100,
				"MM_hasUidField" => TRUE,
				"MM" => "sys_file_reference",
			)
		),
	),
	'types' => array (
		'1' => array('showitem' => 'name, storage, identifier, type, mime_type, sha1, size')
	),
	'palettes' => array()
);

?>
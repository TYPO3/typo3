<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA['sys_files'] = array (
	'ctrl' => $TCA['sys_files']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'sys_language_uid,l10n_parent,l10n_diffsource,hidden,starttime,endtime,fe_group,file_name,file_path,file_size,file_mtime,file_inode,file_ctime,file_hash,file_mime_type,file_mime_subtype,file_type,file_type_version,file_usage'
	),
	'feInterface' => $TCA['sys_files']['feInterface'],
	'columns' => array (
		'sys_language_uid' => array (		
			'exclude' => 1,
			'label'  => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array (
				'type'                => 'select',
				'foreign_table'       => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0)
				)
			)
		),
		'l10n_parent' => array (		
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude'     => 1,
			'label'       => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config'      => array (
				'type'  => 'select',
				'items' => array (
					array('', 0),
				),
				'foreign_table'       => 'tx_fal_sys_files',
				'foreign_table_where' => 'AND tx_fal_sys_files.pid=###CURRENT_PID### AND tx_fal_sys_files.sys_language_uid IN (-1,0)',
			)
		),
		'l10n_diffsource' => array (		
			'config' => array (
				'type' => 'passthrough'
			)
		),
		'hidden' => array (		
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		'starttime' => array (		
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.starttime',
			'config'  => array (
				'type'     => 'input',
				'size'     => '8',
				'max'      => '20',
				'eval'     => 'date',
				'default'  => '0',
				'checkbox' => '0'
			)
		),
		'fe_group' => array (		
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.fe_group',
			'config'  => array (
				'type'  => 'select',
				'items' => array (
					array('', 0),
					array('LLL:EXT:lang/locallang_general.xml:LGL.hide_at_login', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.any_login', -2),
					array('LLL:EXT:lang/locallang_general.xml:LGL.usergroups', '--div--')
				),
				'foreign_table' => 'fe_groups'
			)
		),
		'file_name' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:fal/locallang_db.xml:tx_fal_sys_files.file_name',		
			'config' => array (
				'type' => 'input',	
				'size' => '30',
			)
		),
		'file_path' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:fal/locallang_db.xml:tx_fal_sys_files.file_path',		
			'config' => array (
				'type' => 'input',	
				'size' => '30',
			)
		),
		'file_size' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:fal/locallang_db.xml:tx_fal_sys_files.file_size',		
			'config' => array (
				'type'     => 'input',
				'size'     => '4',
				'max'      => '4',
				'eval'     => 'int',
				'checkbox' => '0',
				'range'    => array (
					'upper' => '1000',
					'lower' => '10'
				),
				'default' => 0
			)
		),
		'file_mtime' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:fal/locallang_db.xml:tx_fal_sys_files.file_mtime',		
			'config' => array (
				'type'     => 'input',
				'size'     => '12',
				'max'      => '20',
				'eval'     => 'datetime',
				'checkbox' => '0',
				'default'  => '0'
			)
		),
		'file_inode' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:fal/locallang_db.xml:tx_fal_sys_files.file_inode',		
			'config' => array (
				'type'     => 'input',
				'size'     => '4',
				'max'      => '4',
				'eval'     => 'int',
				'checkbox' => '0',
				'range'    => array (
					'upper' => '1000',
					'lower' => '10'
				),
				'default' => 0
			)
		),
		'file_ctime' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:fal/locallang_db.xml:tx_fal_sys_files.file_ctime',		
			'config' => array (
				'type'     => 'input',
				'size'     => '12',
				'max'      => '20',
				'eval'     => 'datetime',
				'checkbox' => '0',
				'default'  => '0'
			)
		),
		'file_hash' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:fal/locallang_db.xml:tx_fal_sys_files.file_hash',		
			'config' => array (
				'type' => 'input',	
				'size' => '48',
			)
		),
		'file_mime_type' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:fal/locallang_db.xml:tx_fal_sys_files.file_mime_type',		
			'config' => array (
				'type' => 'input',	
				'size' => '30',	
				'max' => '45',
			)
		),
		'file_mime_subtype' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:fal/locallang_db.xml:tx_fal_sys_files.file_mime_subtype',		
			'config' => array (
				'type' => 'input',	
				'size' => '30',	
				'max' => '45',
			)
		),
		'file_type' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:fal/locallang_db.xml:tx_fal_sys_files.file_type',		
			'config' => array (
				'type' => 'input',	
				'size' => '9',
			)
		),
		'file_type_version' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:fal/locallang_db.xml:tx_fal_sys_files.file_type_version',		
			'config' => array (
				'type' => 'input',	
				'size' => '10',
			)
		),
		'file_usage' => array (		
			'exclude' => 0,		
			'label' => 'LLL:EXT:fal/locallang_db.xml:tx_fal_sys_files.file_usage',		
			'config' => array (
				'type' => 'group',	
				'internal_type' => 'db',	
				'allowed' => '*',
				'size' => 5,
				'minitems' => 0,
				'MM' => 'sys_files_usage_mm',
			)
		),
	),
	'types' => array (
		'0' => array('showitem' => 'sys_language_uid;;;;1-1-1, l10n_parent, l10n_diffsource, hidden;;1, file_name, file_path, file_size, file_mtime, file_inode, file_ctime, file_hash, file_mime_type, file_mime_subtype, file_type, file_type_version, file_usage')
	),
	'palettes' => array (
		'1' => array('showitem' => 'starttime, fe_group')
	)
);


$TCA['sys_files_mounts'] = array (
	'ctrl' => $TCA['sys_files_mounts']['ctrl'],
	'interface' => array (
		'showRecordFieldList' => 'hidden,title,storage_backend,backend_configuration'
	),
	'feInterface' => $TCA['sys_files_mounts']['feInterface'],
	'columns' => array (
		'title' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:fal/locallang_db.xml:tx_fal_sys_files_mounts.title',
			'config' => array(
				'type' => 'input',
				'size' => 30
			)
		),
		'alias' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:fal/locallang_db.xml:tx_fal_sys_files_mounts.alias',
			'config' => array(
				'type' => 'input',
				'size' => 30,
				'eval' => 'unique,alphanum'
			)
		),
		'storage_backend' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:fal/locallang_db.xml:tx_fal_sys_files_mounts.storage_backend',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('Filesystem', 'tx_fal_storage_FileSystemStorage')
				)
			)
		),
		'backend_configuration' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:fal/locallang_db.xml:tx_fal_sys_files_mounts.backend_configuration',
			'config' => array(
				'type' => 'flex',
				'ds_pointerField' => 'storage_backend',
				'ds' => array(
					'tx_fal_storage_FileSystemStorage' => 'FILE:EXT:fal/ds_filesystemstorage.xml',
				)
			)
		)
	),
	'types' => array (
		'0' => array('showitem' => 'title;;;;1-1-1, alias, storage_backend, backend_configuration')
	),
	'palettes' => array (
		'1' => array('showitem' => '')
	)
);
?>
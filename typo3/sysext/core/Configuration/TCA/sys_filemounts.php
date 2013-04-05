<?php
return array(
	'ctrl' => array(
		'label' => 'title',
		'tstamp' => 'tstamp',
		'sortby' => 'sorting',
		'prependAtCopy' => 'LLL:EXT:lang/locallang_general.xlf:LGL.prependAtCopy',
		'title' => 'LLL:EXT:lang/locallang_tca.xlf:sys_filemounts',
		'adminOnly' => 1,
		'rootLevel' => 1,
		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden'
		),
		'iconfile' => '_icon_ftp.gif',
		'useColumnsForDefaultValues' => 'path,base',
		'versioningWS_alwaysAllowLiveEdit' => TRUE,
		'searchFields' => 'title,path'
	),
	'interface' => array(
		'showRecordFieldList' => 'title,hidden,path,base'
	),
	'columns' => array(
		'title' => array(
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_filemounts.title',
			'config' => array(
				'type' => 'input',
				'size' => '20',
				'max' => '30',
				'eval' => 'required,trim'
			)
		),
		'hidden' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.disable',
			'config' => array(
				'type' => 'check'
			)
		),
		'base' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.baseStorage',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'sys_file_storage',
				'size' => 1,
				'maxitems' => 1
			)
		),
		'path' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.folder',
			'config' => array(
				'type' => 'select',
				'items' => array(),
				'itemsProcFunc' => 'typo3/sysext/core/Classes/Resource/Service/UserFileMountService.php:TYPO3\CMS\Core\Resource\Service\UserFileMountService->renderTceformsSelectDropdown',
			)
		)
	),
	'types' => array(
		'0' => array('showitem' => '--palette--;;mount, base, path')
	),
	'palettes' => array(
		'mount' => array('showitem' => 'title,hidden', 'canNotCollapse' => 1)
	),
);
?>
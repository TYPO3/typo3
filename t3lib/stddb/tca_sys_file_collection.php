<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
$TCA['sys_file_collection'] = array(
	'ctrl' => $TCA['sys_file_collection']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'sys_language_uid,l10n_parent,l10n_diffsource,hidden,starttime,endtime,files,title'
	),
	'feInterface' => $TCA['sys_file_collection']['feInterface'],
	'columns' => array(
		't3ver_label' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.versionLabel',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'max' => '30'
			)
		),
		'sys_language_uid' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.language',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xml:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.xml:LGL.default_value', 0)
				)
			)
		),
		'l10n_parent' => array(
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.l18n_parent',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0)
				),
				'foreign_table' => 'sys_file_collection',
				'foreign_table_where' => 'AND sys_file_collection.pid=###CURRENT_PID### AND sys_file_collection.sys_language_uid IN (-1,0)'
			)
		),
		'l10n_diffsource' => array(
			'config' => array(
				'type' => 'passthrough'
			)
		),
		'hidden' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),
		'starttime' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.starttime',
			'config' => array(
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'default' => '0',
				'checkbox' => '0'
			)
		),
		'endtime' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.endtime',
			'config' => array(
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'checkbox' => '0',
				'default' => '0',
				'range' => array(
					'upper' => mktime(3, 14, 7, 1, 19, 2038),
					'lower' => mktime(0, 0, 0, date('m') - 1, date('d'), date('Y'))
				)
			)
		),
		'type' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_collection.type',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('Static selection of files', 'static'),
					array('Folder from Storage', 'folder')
				)
			)
		),
		'files' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_collection.files',
			'config' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getFileFieldTCAConfig('files')
		),
		'title' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_collection.title',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'required'
			)
		),
		'storage' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_collection.storage',
			'config' => array(
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
		'folder' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_collection.folder',
			'config' => array(
				'type' => 'select',
				'items' => array(),
				'itemsProcFunc' => 'typo3/sysext/core/Classes/Resource/Service/UserFileMountService.php:TYPO3\CMS\Core\Resource\Service\UserFileMountService->renderTceformsSelectDropdown',
			)
		)
	),
	'types' => array(
		'0' => array('showitem' => 'sys_language_uid;;;;1-1-1, l10n_parent, l10n_diffsource, title;;1, type, files'),
		'static' => array('showitem' => 'sys_language_uid;;;;1-1-1, l10n_parent, l10n_diffsource, title;;1, type, files'),
		'folder' => array('showitem' => 'sys_language_uid;;;;1-1-1, l10n_parent, l10n_diffsource, title;;1, type, storage, folder')
	),
	'palettes' => array(
		'1' => array('showitem' => 'hidden, starttime, endtime')
	)
);
?>
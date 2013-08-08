<?php

return array(
	'ctrl' => array(
		'title' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		//'hideTable' => TRUE,
		'rootLevel' => TRUE,
		'label' => 'title',
		'versioningWS' => TRUE,
		'origUid' => 't3_origuid',
		'default_sortby' => 'ORDER BY crdate DESC',
		'delete' => 'deleted',
		'dividers2tabs' => TRUE,
		'copyAfterDuplFields' => 'sys_language_uid,file_uid',
		'useColumnsForDefaultValues' => 'sys_language_uid,file_uid',
		'transOrigPointerField' => 'l10n_parent',
		'transOrigDiffSourceField' => 'l10n_diffsource',
		//'languageField' => 'sys_language_uid',
		'localeField' => 'locale',
		'localeRequired' => TRUE,
		'security' => array(
			'ignoreWebMountRestriction' => TRUE,
			'ignoreRootLevelRestriction' => TRUE,
		),
	),
	'interface' => array(
		'showRecordFieldList' => 'storage, description, alternative, type, mime_type, size, sha1'
	),
	'columns' => array(
		'fileinfo' => array(
			'l10n_display' => 'hideDiff', // TODO add the correct value
			'config' => array(
				'type' => 'user',
				'userFunc' => 'typo3/sysext/core/Classes/Resource/Hook/FileInfoHook.php:TYPO3\CMS\Core\Resource\Hook\FileInfoHook->renderFileInfoForMetadata'
			)
		),
		'file_uid' => array(
			'exclude' => 0,
			'displayCond' => 'REC:NEW:TRUE',
			'label' => 'File',
			'l10n_mode' => 'copy',
			'config' => array(
				'type' => 'input'
				/*'type' => 'group',
				'internal_type' => 'db',
				'allowed' => 'sys_file',
				'size' => '1',
				'maxitems' => '1',
				'minitems' => '1',
				//'userFunc' => 'typo3/sysext/core/Classes/Resource/Hook/FileInfoHook.php:TYPO3\CMS\Core\Resource\Hook\FileInfoHook->renderFileInfoForMetadata',
				'readOnly' => 1,*/
			),
		),
		'locale' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
			'config' => Array (
				'type' => 'select',
				'itemsProcFunc' => 'typo3/sysext/core/Classes/Resource/Hook/LocalizationHooks.php:TYPO3\CMS\Core\Resource\Hook\LocalizationHooks->fillLocaleList',
				'items' => Array(
					Array('LLL:EXT:lang/locallang_general.php:LGL.allLanguages', 'zz-mul')
				)
			)
		),
		'l10n_parent' => Array (
			'displayCond' => 'FIELD:locale:!=:',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.l10n_parent',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					array('', 0)
				),
				'foreign_table' => 'sys_file_metadata',
				'foreign_table_where' => 'AND sys_file_metadata.locale != "###REC_FIELD_locale###"',
			)
		),
		'title' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file.title',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'placeholder' => '__row|file_uid|name'
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
		'l10n_diffsource' => array(
			'config' => array(
				'type' => 'passthrough'
			)
		),
		'locale' => array(
			'config' => array(
				'type' => 'passthrough'
			)
		),
	),
	'types' => array(
		'1' => array('showitem' => 'fileinfo, locale, l10n_parent, title, description, alternative, file_uid')
	),
);

?>
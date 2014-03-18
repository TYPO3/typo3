<?php
return array(
	'ctrl' => array(
		'title' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_metadata',
		'label' => 'file',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'type' => 'type',
		'hideTable' => TRUE,
		'rootLevel' => TRUE,
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l10n_parent',
		'transOrigDiffSourceField' => 'l10n_diffsource',
		'versioningWS' => TRUE,
		'origUid' => 't3_origuid',
		'default_sortby' => 'ORDER BY crdate DESC',
		'dividers2tabs' => TRUE,
		'typeicon_classes' => array(
			'default' => 'mimetypes-other-other'
		),
		'security' => array(
			'ignoreWebMountRestriction' => TRUE,
			'ignoreRootLevelRestriction' => TRUE,
		),
		'searchFields' => 'file,title,description,alternative'
	),
	'interface' => array(
		'showRecordFieldList' => 'file, title, description, alternative'
	),
	'columns' => array(
		'sys_language_uid' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.language',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xlf:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.xlf:LGL.default_value', 0)
				)
			)
		),
		'l10n_parent' => array(
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.l18n_parent',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0)
				),
				'foreign_table' => 'sys_file_metadata',
				'foreign_table_where' => 'AND sys_file_metadata.uid=###REC_FIELD_l10n_parent### AND sys_file_metadata.sys_language_uid IN (-1,0)'
			)
		),
		'l10n_diffsource' => array(
			'exclude' => 0,
			'config' => array(
				'type' => 'passthrough'
			)
		),
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
			'displayCond' => 'FIELD:sys_language_uid:=:0',
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
			'l10n_mode' => 'prefixLangTitle',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'placeholder' => '__row|file|name'
			)
		),
		'description' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file.description',
			'l10n_mode' => 'prefixLangTitle',
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
		'width' => array(
			'exclude' => 0,
			'l10n_mode' => 'exclude'
		),
		'height' => array(
			'exclude' => 0,
			'l10n_mode' => 'exclude'
		)
	),
	'types' => array(
		'1' => array('showitem' => 'fileinfo, title, description, alternative')
	),
	'palettes' => array()
);

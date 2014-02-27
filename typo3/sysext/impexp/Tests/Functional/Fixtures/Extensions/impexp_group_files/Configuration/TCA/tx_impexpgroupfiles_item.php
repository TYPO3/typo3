<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

return array(
	'ctrl' => array(
		'title'	=> 'LLL:EXT:impexp_group_files/Resources/Private/Language/locallang_db.xlf:tx_impexpgroupfiles_item',
		'label' => 'title',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'dividers2tabs' => TRUE,
		'sortby' => 'sorting',

		'versioningWS' => 2,
		'versioning_followPages' => TRUE,

		'origUid' => 't3_origuid',

		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
		),
		'searchFields' => 'price,currency,symbol,',
		'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('impexp_group_files') . 'Resources/Public/Icons/icon_tx_impexpgroupfiles_item.gif'
	),
	'interface' => array(
		'showRecordFieldList' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden, title, images, image_references',
	),
	'types' => array(
		'1' => array('showitem' => 'hidden, title, images, image_references,--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,starttime, endtime'),
	),
	'palettes' => array(
		'1' => array('showitem' => ''),
	),
	'columns' => array(

		't3ver_label' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.versionLabel',
			'config' => array(
				'type' => 'input',
				'size' => 30,
				'max' => 255,
			)
		),

		'hidden' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.hidden',
			'config' => array(
				'type' => 'check',
			),
		),
		'starttime' => array(
			'exclude' => 1,
			'l10n_mode' => 'mergeIfNotBlank',
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.starttime',
			'config' => array(
				'type' => 'input',
				'size' => 13,
				'max' => 20,
				'eval' => 'datetime',
				'checkbox' => 0,
				'default' => 0,
				'range' => array(
					'lower' => mktime(0, 0, 0, date('m'), date('d'), date('Y'))
				),
			),
		),
		'endtime' => array(
			'exclude' => 1,
			'l10n_mode' => 'mergeIfNotBlank',
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.endtime',
			'config' => array(
				'type' => 'input',
				'size' => 13,
				'max' => 20,
				'eval' => 'datetime',
				'checkbox' => 0,
				'default' => 0,
				'range' => array(
					'lower' => mktime(0, 0, 0, date('m'), date('d'), date('Y'))
				),
			),
		),
		'title' => array(
			'label' => 'LLL:EXT:impexp_group_files/Resources/Private/Language/locallang_db.xml:tx_impexpgroupfiles_item_title',
			'config' => array(
				'type' => 'input',
				'size' => 30,
				'max' => 255,
				'eval' => 'required'
			)
		),
		'images' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:impexp_group_files/Resources/Private/Language/locallang_db.xml:tx_impexpgroupfiles_item_images',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'file',
				'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
				'disallowed' => 'php',
				'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
				'uploadfolder' => 'uploads/tx_impexpgroupfiles',
				'size' => 5,
				'maxitems' => 5,
				'show_thumbs' => 1,
			),
		),
		'image_references' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:impexp_group_files/Resources/Private/Language/locallang_db.xml:tx_impexpgroupfiles_item_image_references',
			'config' => array(
				'type' => 'group',
				'internal_type' => 'file_reference',
				'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
				'disallowed' => 'php',
				'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
				'size' => 5,
				'maxitems' => 5,
				'show_thumbs' => 1,
			),
		),
	),
);

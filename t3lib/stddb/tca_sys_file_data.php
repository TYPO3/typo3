<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
$GLOBALS['TCA']['sys_file_data'] = array(
	'ctrl' => $GLOBALS['TCA']['sys_file_data']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'title, description, alternative'
	),
	'feInterface' => $GLOBALS['TCA']['sys_file_data']['feInterface'],
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
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.php:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.php:LGL.default_value', 0)
				)
			)
		),
		'l10n_parent' => array(
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.l18n_parent',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0)
				),
				'foreign_table' => 'sys_file_reference',
				'foreign_table_where' => 'AND sys_file_reference.uid=###REC_FIELD_l10n_parent### AND sys_file_reference.sys_language_uid IN (-1,0)'
			)
		),
		'l10n_diffsource' => array(
			'config' => array(
				'type' => 'passthrough'
			)
		),
		'title' => array(
			'l10n_mode' => 'mergeIfNotBlank',
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.title',
			'config' => array(
				'type' => 'input',
				'size' => '22',
			)
		),
		'link' => array(
			'l10n_mode' => 'mergeIfNotBlank',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.link',
			'config' => array(
				'type' => 'input',
				'size' => '20',
				'wizards' => array(
					'_PADDING' => 2,
					'link' => array(
						'type' => 'popup',
						'title' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.link',
						'icon' => 'link_popup.gif',
						'script' => 'browse_links.php?mode=wizard',
						'JSopenParams' => 'height=300,width=500,status=0,menubar=0,scrollbars=1'
					)
				)
			)
		),
		'description' => array(
			// This is used for captions in the frontend
			'l10n_mode' => 'mergeIfNotBlank',
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.description',
			'config' => array(
				'type' => 'text',
				'cols' => '24',
				'rows' => '5'
			)
		),
		'alternative' => array(
			'l10n_mode' => 'mergeIfNotBlank',
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_tca.xlf:sys_file_reference.alternative',
			'config' => array(
				'type' => 'input',
				'size' => '22',
			)
		),
	),
	'types' => array(
		'1' => array('showitem' => 'title, link, description, alternative')
	),
	'palettes' => array()
);
?>
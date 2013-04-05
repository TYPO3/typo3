<?php
return array(
	'ctrl' => array(
		'title' => 'LLL:EXT:rtehtmlarea/locallang_db.xml:tx_rtehtmlarea_acronym',
		'label' => 'term',
		'default_sortby' => 'ORDER BY term',
		'sortby' => 'sorting',
		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime'
		),
		'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('rtehtmlarea') . 'extensions/Acronym/skin/images/acronym.gif'
	),
	'interface' => array(
		'showRecordFieldList' => 'hidden,sys_language_uid,term,acronym'
	),
	'columns' => array(
		'hidden' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.hidden',
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		),
		'starttime' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.starttime',
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
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.endtime',
			'config' => array(
				'type' => 'input',
				'size' => '8',
				'max' => '20',
				'eval' => 'date',
				'checkbox' => '0',
				'default' => '0',
				'range' => array(
					'upper' => mktime(0, 0, 0, 12, 31, 2020),
					'lower' => mktime(0, 0, 0, date('m') - 1, date('d'), date('Y'))
				)
			)
		),
		'sys_language_uid' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.language',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xlf:LGL.allLanguages', '-1'),
					array('LLL:EXT:lang/locallang_general.xlf:LGL.default_value', '0')
				)
			)
		),
		'type' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:rtehtmlarea/locallang_db.xml:tx_rtehtmlarea_acronym.type',
			'config' => array(
				'type' => 'radio',
				'items' => array(
					array('LLL:EXT:rtehtmlarea/locallang_db.xml:tx_rtehtmlarea_acronym.type.I.1', '2'),
					array('LLL:EXT:rtehtmlarea/locallang_db.xml:tx_rtehtmlarea_acronym.type.I.0', '1')
				),
				'default' => '2'
			)
		),
		'term' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:rtehtmlarea/locallang_db.xml:tx_rtehtmlarea_acronym.term',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'trim,required'
			)
		),
		'acronym' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:rtehtmlarea/locallang_db.xml:tx_rtehtmlarea_acronym.acronym',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'trim,required'
			)
		),
		'static_lang_isocode' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:rtehtmlarea/locallang_db.xml:tx_rtehtmlarea_acronym.static_lang_isocode',
			'displayCond' => 'EXT:static_info_tables:LOADED:true',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', 0)
				),
				'itemsProcFunc' => 'tx_staticinfotables_div->selectItemsTCA',
				'itemsProcFunc_config' => array(
					'table' => 'static_languages',
					'indexField' => 'uid',
					'prependHotlist' => 1
				),
				'size' => 1,
				'minitems' => 0,
				'maxitems' => 1
			)
		)
	),
	'types' => array(
		'0' => array('showitem' => 'hidden;;1;;1-1-1, sys_language_uid, type, term, acronym, static_lang_isocode')
	),
	'palettes' => array(
		'1' => array('showitem' => 'starttime, endtime')
	)
);
?>
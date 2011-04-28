<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

//add field to tt_content
$tempColumns = array(
	'tx_contentrss_excluderss' => Array (
		'exclude' => 1,
		'label' => 'LLL:EXT:contentrss/locallang_db.xml:tt_content.tx_contentrss_excluderss',
		'config' => Array (
			'type' => 'check',
		)
	),
);
t3lib_div::loadTCA('tt_content');
t3lib_extMgm::addTCAcolumns('tt_content', $tempColumns, TRUE);
t3lib_extMgm::addToAllTCAtypes('tt_content','tx_contentrss_excluderss'); 

//configure and add plugin
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY . '_pi1'] = 'layout,select_key';
t3lib_extMgm::addPlugin(array('LLL:EXT:contentrss/locallang_db.xml:tt_content.list_type_pi1', $_EXTKEY . '_pi1'),'list_type');

//add static templates
t3lib_extMgm::addStaticFile($_EXTKEY,'pi1/static/', 'RSS from Content');
?>
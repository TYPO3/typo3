<?php

if (!defined ("TYPO3_MODE")) 	die ("Access denied.");

// ******************************************************************
// static_template
// ******************************************************************
$TCA['static_template'] = array(
	'ctrl' => array(
		'label' => 'title',
		'tstamp' => 'tstamp',
		'title' => 'LLL:EXT:statictemplates/locallang_tca.xml:static_template',
		'readOnly' => 1,	// This should always be true, as it prevents the static templates from being altered
		'adminOnly' => 1,	// Only admin, if any
		'rootLevel' => 1,
		'is_static' => 1,
		'default_sortby' => 'ORDER BY title',
		'crdate' => 'crdate',
		'iconfile' => 'template_standard.gif',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY) . 'tca.php'
	)
);

$tempField = array(
		'include_static' => array(
			'label'  => 'LLL:EXT:statictemplates/locallang_tca.xml:include_static',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'static_template',
				'foreign_table_where' => 'ORDER BY static_template.title DESC',
				'size' => 10,
				'maxitems' => 20,
				'default' => '',
			)
		),
);

t3lib_div::loadTCA('sys_template');
t3lib_extMgm::addTCAcolumns('sys_template', $tempField, 1);
t3lib_extMgm::addToAllTCAtypes('sys_template', 'include_static;;2;;5-5-5', '', 'before:includeStaticAfterBasedOn');

?>

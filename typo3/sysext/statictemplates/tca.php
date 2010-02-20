<?php

// ******************************************************************
// static_template
// ******************************************************************
$TCA['static_template'] = array(
	'ctrl' => $TCA['static_template']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'title,include_static,description'
	),
	'columns' => array(
		'title' => array(
			'label' => 'LLL:EXT:statictemplates/locallang_tca.xml:static_template.title',
			'config' => array(
				'type' => 'input',
				'size' => '25',
				'max' => '256',
				'eval' => 'required'
			)
		),
		'constants' => array(
			'label' => 'LLL:EXT:statictemplates/locallang_tca.xml:static_template.constants',
			'config' => array(
				'type' => 'text',
				'cols' => '48',
				'rows' => '10',
				'wrap' => 'OFF'
			),
			'defaultExtras' => 'fixed-font: enable-tab',
		),
		'include_static' => array(
			'label' => 'LLL:EXT:statictemplates/locallang_tca.xml:static_template.include_static',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'static_template',
				'foreign_table_where' => 'ORDER BY static_template.title',
				'size' => 10,
				'maxitems' => 20,
				'default' => ''
			)
		),
		'config' => array(
			'label' => 'LLL:EXT:statictemplates/locallang_tca.xml:static_template.config',
			'config' => array(
				'type' => 'text',
				'rows' => 10,
				'cols' => 48,
				'wrap' => 'OFF'
			),
			'defaultExtras' => 'fixed-font: enable-tab',
		),
		'editorcfg' => array(
			'label' => 'LLL:EXT:statictemplates/locallang_tca.xml:static_template.editorcfg',
			'config' => array(
				'type' => 'text',
				'rows' => 4,
				'cols' => 48,
				'wrap' => 'OFF'
			),
			'defaultExtras' => 'fixed-font: enable-tab',
		),
		'description' => array(
			'label' => 'LLL:EXT:statictemplates/locallang_tca.xml:static_template.description',
			'config' => array(
				'type' => 'text',
				'rows' => 10,
				'cols' => 48
			)
		)
	),
	'types' => array(
		'1' => array('showitem' => 'title;;;;2-2-2, constants;;;;3-3-3, config, include_static;;;;5-5-5, description;;;;5-5-5, editorcfg')
	)
);


?>
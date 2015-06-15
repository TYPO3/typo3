<?php
return array(
	'ctrl' => array(
		'label' => 'text',
		'title' => 'Subtype test record',
		'requestUpdate' => 'subtypeswitch'
	),
	'columns' => array(
		'subtypeswitch' => array(
			'label' => 'Subtype switch',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('All fields visible', 'fieldsvisible'),
					array('Excluded fields', 'fieldsexcluded'),
				),
			),
		),
		'beforeinline' => array(
			'label' => 'Before inline',
			'config' => array(
				'type' => 'input',
			)
		),
		'children' => array(
			'label' => 'Children',
			'config' => array(
				'type' => 'inline',
				'foreign_table' => 'tx_inlinetest_inline',
				'foreign_field' => 'parent_uid',
				'appearance' => array(
					'collapseAll' => FALSE,
				),
			)
		),
		'afterinline' => array(
			'label' => 'After inline',
			'config' => array(
				'type' => 'input',
			)
		),
	),
	'types' => array(
		0 => array(
			'showitem' => '--palette--;;testsettings',
			'subtype_value_field' => 'subtypeswitch',
			'subtypes_excludelist' => array(
				'fieldsexcluded' => 'beforeinline,afterinline'
			),
		),
	),
	'palettes' => array(
		'testsettings' => array(
			'showitem' => 'subtypeswitch, --linebreak--, beforeinline, --linebreak--, children, --linebreak--, afterinline',
			'canNotCollapse' => 1,
		),
	),
);
<?php
return [
	'ctrl' => [
		'label' => 'text',
		'title' => 'Subtype test record',
		'requestUpdate' => 'subtypeswitch'
	],
	'columns' => [
		'subtypeswitch' => [
			'label' => 'Subtype switch',
			'config' => [
				'type' => 'select',
				'items' => [
					['All fields visible', 'fieldsvisible'],
					['Excluded fields', 'fieldsexcluded'],
				],
			],
		],
		'beforeinline' => [
			'label' => 'Before inline',
			'config' => [
				'type' => 'input',
			]
		],
		'children' => [
			'label' => 'Children',
			'config' => [
				'type' => 'inline',
				'foreign_table' => 'tx_inlinetest_inline',
				'foreign_field' => 'parent_uid',
				'appearance' => [
					'collapseAll' => FALSE,
				],
			]
		],
		'afterinline' => [
			'label' => 'After inline',
			'config' => [
				'type' => 'input',
			]
		],
	],
	'types' => [
		0 => [
			'showitem' => '--palette--;;testsettings',
			'subtype_value_field' => 'subtypeswitch',
			'subtypes_excludelist' => [
				'fieldsexcluded' => 'beforeinline,afterinline'
			],
		],
	],
	'palettes' => [
		'testsettings' => [
			'showitem' => 'subtypeswitch, --linebreak--, beforeinline, --linebreak--, children, --linebreak--, afterinline',
			'canNotCollapse' => 1,
		],
	],
];
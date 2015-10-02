<?php
return array(
	'ctrl' => array (
		'title' => 'Form engine tests - Inline expand tests',
		'label' => 'uid',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'delete' => 'deleted',
		'sortby' => 'sorting',
		'default_sortby' => 'ORDER BY crdate',
		'iconfile' => 'EXT:styleguide/Resources/Public/Icons/tx_styleguide_inlineexpand.svg',

		'versioningWS' => TRUE,
		'origUid' => 't3_origuid',
		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l10n_parent',
		'transOrigDiffSourceField' => 'l10n_diffsource',

		'enablecolumns' => array(
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
		),
	),

	'columns' => array(
		'hidden' => array (
			'exclude' => 1,
			'config' => array(
				'type' => 'check',
				'items' => array(
					'1' => array(
						'0' => 'Disable'
					),
				),
			),
		),
		'starttime' => array(
			'exclude' => 1,
			'label' => 'Publish Date',
			'config' => array(
				'type' => 'input',
				'size' => '13',
				'max' => '20',
				'eval' => 'datetime',
				'default' => '0'
			),
			'l10n_mode' => 'exclude',
			'l10n_display' => 'defaultAsReadonly'
		),
		'endtime' => array(
			'exclude' => 1,
			'label' => 'Expiration Date',
			'config' => array(
				'type' => 'input',
				'size' => '13',
				'max' => '20',
				'eval' => 'datetime',
				'default' => '0',
				'range' => array(
					'upper' => mktime(0, 0, 0, 12, 31, 2020)
				)
			),
			'l10n_mode' => 'exclude',
			'l10n_display' => 'defaultAsReadonly'
		),


		'inline_1' => array(
			'exclude' => 1,
			'label' => 'INLINE 1',
			'config' => array(
				'type' => 'inline',
				'foreign_table' => 'tx_styleguide_inlineexpand_inline_1_child1',
				'foreign_field' => 'parentid',
				'foreign_table_field' => 'parenttable',
			),
		),
	),


	'interface' => array(
		'showRecordFieldList' => '
			hidden, starttime, endtime,
			inline_1,
		',
	),

	'types' => array(
		'0' => array(
			'showitem' => '
				inline_1,
			',
		),
	),

);

<?php
return array(
	'ctrl' => array(
		'label' => 'groupName',
		'tstamp' => 'tstamp',
		'title' => 'LLL:EXT:scheduler/locallang_tca.xlf:tx_scheduler_task_group',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'delete' => 'deleted',
		'sortby' => 'sorting',
		'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('scheduler') . 'ext_icon.gif',
		'adminOnly' => 1, // Only admin users can edit
		'rootLevel' => 1,
		'enablecolumns' => array(
			'disabled' => 'hidden'
		),
		'searchFields' => 'groupName'
	),
	'interface' => array(
		'showRecordFieldList' => 'hidden,groupName'
	),
	'columns' => array(
		'groupName' => array(
			'label' => 'LLL:EXT:scheduler/locallang_tca.xlf:tx_scheduler_task_group.groupName',
			'config' => array(
				'type' => 'input',
				'size' => '35',
				'max' => '80',
				'eval' => 'required,unique,trim',
				'softref' => 'substitute'
			)
		),
		'description' => array(
			'label' => 'LLL:EXT:scheduler/locallang_tca.xlf:tx_scheduler_task_group.description',
			'config' => array(
				'type' => 'text'
			),
		),
		'hidden' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.disable',
			'exclude' => 1,
			'config' => array(
				'type' => 'check',
				'default' => '0'
			)
		)
	),
	'types' => array(
		'1' => array('showitem' => 'hidden;;;;1-1-1,groupName;;1;;3-3-3,description;;1;;3-3-3')
	)
);
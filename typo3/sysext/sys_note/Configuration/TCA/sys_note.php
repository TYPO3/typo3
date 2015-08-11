<?php
return array(
	'ctrl' => array(
		'label' => 'subject',
		'default_sortby' => 'ORDER BY crdate',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser',
		'prependAtCopy' => 'LLL:EXT:lang/locallang_general.xlf:LGL.prependAtCopy',
		'delete' => 'deleted',
		'title' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note',
		'iconfile' => 'EXT:sys_note/ext_icon.png',
		'sortby' => 'sorting',
	),
	'interface' => array(
		'showRecordFieldList' => 'category,subject,message,personal'
	),
	'columns' => array(
		'category' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.category',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', '0'),
					array('LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note.category.I.1', '1', 'sysext/sys_note/Resources/Public/Icons/instruction.png'),
					array('LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note.category.I.3', '3', 'sysext/sys_note/Resources/Public/Icons/note.png'),
					array('LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note.category.I.4', '4', 'sysext/sys_note/Resources/Public/Icons/todo.png'),
					array('LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note.category.I.2', '2', 'sysext/sys_note/Resources/Public/Icons/template.png')
				),
				'default' => '0'
			)
		),
		'subject' => array(
			'label' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note.subject',
			'config' => array(
				'type' => 'input',
				'size' => '40',
				'max' => '256'
			)
		),
		'message' => array(
			'label' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note.message',
			'config' => array(
				'type' => 'text',
				'cols' => '40',
				'rows' => '15'
			)
		),
		'personal' => array(
			'label' => 'LLL:EXT:sys_note/Resources/Private/Language/locallang_tca.xlf:sys_note.personal',
			'config' => array(
				'type' => 'check'
			)
		)
	),
	'types' => array(
		'0' => array('showitem' => 'category, personal, subject, message')
	)
);

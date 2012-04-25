<?php

if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

$TCA['sys_note'] = array(
	'ctrl' => $TCA['sys_note']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'category,subject,message,author,email,personal'
	),
	'columns' => array(
		'category' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.category',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('', '0'),
					array('LLL:EXT:sys_note/locallang_tca.xlf:sys_note.category.I.1', '1'),
					array('LLL:EXT:sys_note/locallang_tca.xlf:sys_note.category.I.2', '3'),
					array('LLL:EXT:sys_note/locallang_tca.xlf:sys_note.category.I.3', '4'),
					array('LLL:EXT:sys_note/locallang_tca.xlf:sys_note.category.I.4', '2')
				),
				'default' => '0'
			)
		),
		'subject' => array(
			'label' => 'LLL:EXT:sys_note/locallang_tca.xlf:sys_note.subject',
			'config' => array(
				'type' => 'input',
				'size' => '40',
				'max' => '256'
			)
		),
		'message' => array(
			'label' => 'LLL:EXT:sys_note/locallang_tca.xlf:sys_note.message',
			'config' => array(
				'type' => 'text',
				'cols' => '40',
				'rows' => '15'
			)
		),
		'author' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.author',
			'config' => array(
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '80'
			)
		),
		'email' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.email',
			'config' => array(
				'type' => 'input',
				'size' => '20',
				'eval' => 'trim',
				'max' => '80'
			)
		),
		'personal' => array(
			'label' => 'LLL:EXT:sys_note/locallang_tca.xlf:sys_note.personal',
			'config' => array(
				'type' => 'check'
			)
		)
	),
	'types' => array(
		'0' => array('showitem' => 'category;;;;2-2-2, author, email, personal, subject;;;;3-3-3, message')
	)
);

?>
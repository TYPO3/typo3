<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE=='BE')	{
	$TCA['sys_note'] = Array (
		'ctrl' => Array (
			'label' => 'subject',
			'default_sortby' => 'ORDER BY crdate',
			'tstamp' => 'tstamp',
			'crdate' => 'crdate',
			'cruser_id' => 'cruser',
			'prependAtCopy' => 'LLL:EXT:lang/locallang_general.php:LGL.prependAtCopy',
			'delete' => 'deleted',
			'title' => 'LLL:EXT:sys_note/locallang_tca.php:sys_note',
			'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY).'ext_icon.gif',
		),
		'interface' => Array (
			'showRecordFieldList' => 'category,subject,message,author,email,personal'
		),
		'columns' => Array (
			'category' => Array (
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.category',
				'config' => Array (
					'type' => 'select',
					'items' => Array (
						Array('', '0'),
						Array('LLL:EXT:sys_note/locallang_tca.php:sys_note.category.I.1', '1'),
						Array('LLL:EXT:sys_note/locallang_tca.php:sys_note.category.I.2', '3'),
						Array('LLL:EXT:sys_note/locallang_tca.php:sys_note.category.I.3', '4'),
						Array('LLL:EXT:sys_note/locallang_tca.php:sys_note.category.I.4', '2')
					),
					'default' => '0'
				)
			),
			'subject' => Array (
				'label' => 'LLL:EXT:sys_note/locallang_tca.php:sys_note.subject',
				'config' => Array (
					'type' => 'input',
					'size' => '40',
					'max' => '256'
				)
			),
			'message' => Array (
				'label' => 'LLL:EXT:sys_note/locallang_tca.php:sys_note.message',
				'config' => Array (
					'type' => 'text',
					'cols' => '40',
					'rows' => '15'
				)
			),
			'author' => Array (
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.author',
				'config' => Array (
					'type' => 'input',
					'size' => '20',
					'eval' => 'trim',
					'max' => '80'
				)
			),
			'email' => Array (
				'label' => 'LLL:EXT:lang/locallang_general.php:LGL.email',
				'config' => Array (
					'type' => 'input',
					'size' => '20',
					'eval' => 'trim',
					'max' => '80'
				)
			),
			'personal' => Array (
				'label' => 'LLL:EXT:sys_note/locallang_tca.php:sys_note.personal',
				'config' => Array (
					'type' => 'check'
				)
			)
		),
		'types' => Array (
			'0' => Array('showitem' => 'category;;;;2-2-2, author, email, personal, subject;;;;3-3-3, message')
		)
	);

	t3lib_extMgm::allowTableOnStandardPages('sys_note');
}

t3lib_extMgm::addLLrefForTCAdescr('sys_note','EXT:sys_note/locallang_csh_sysnote.xml');
?>
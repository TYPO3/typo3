<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$TCA['tx_blogexample_domain_model_comment'] = array(
	'ctrl' => $TCA['tx_blogexample_domain_model_comment']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'hidden, date, author, email, content'
	),
	'columns' => array(
		'hidden' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config' => array(
				'type' => 'check'
			)
		),
		'date' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_comment.date',
			'config' => array(
				'type' => 'input',
				'size' => 12,
				'checkbox' => 1,
				'eval' => 'datetime, required',
				'default' => time()
			)
		),
		'author' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_comment.author',
			'config' => array(
				'type' => 'input',
				'size' => 20,
				'eval' => 'trim, required',
				'max' => 256
			)
		),
		'email' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_comment.email',
			'config' => array(
				'type' => 'input',
				'size' => 20,
				'eval' => 'trim, required',
				'max' => 256
			)
		),
		'content' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_comment.content',
			'config' => array(
				'type' => 'text',
				'rows' => 30,
				'cols' => 80
			)
		),
		'post' => array(
			'config' => array(
				'type' => 'passthrough',
			)
		),
	),
	'types' => array(
		'1' => array('showitem' => 'hidden, date, author, email, content')
	),
	'palettes' => array(
		'1' => array('showitem' => '')
	)
);

?>
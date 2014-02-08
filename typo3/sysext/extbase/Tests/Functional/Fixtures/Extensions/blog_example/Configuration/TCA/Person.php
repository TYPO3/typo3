<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$TCA['tx_blogexample_domain_model_person'] = array(
	'ctrl' => $TCA['tx_blogexample_domain_model_person']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'firstname, lastname, email, avatar'
	),
	'columns' => array(
		'hidden' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config' => array(
				'type' => 'check'
			)
		),
		'firstname' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_person.firstname',
			'config' => array(
				'type' => 'input',
				'size' => 20,
				'eval' => 'trim,required',
				'max' => 256
			)
		),
		'lastname' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_person.lastname',
			'config' => array(
				'type' => 'input',
				'size' => 20,
				'eval' => 'trim,required',
				'max' => 256
			)
		),
		'email' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:blog_example/Resources/Private/Language/locallang_db.xml:tx_blogexample_domain_model_person.email',
			'config' => array(
				'type' => 'input',
				'size' => 20,
				'eval' => 'trim, required',
				'max' => 256
			)
		)
	),
	'types' => array(
		'1' => array('showitem' => 'firstname, lastname, email, avatar')
	),
	'palettes' => array(
		'1' => array('showitem' => '')
	)
);

?>
<?php
$EM_CONF[$_EXTKEY] = array(
	'title' => 'Database Abstraction Layer',
	'description' => 'A database abstraction layer implementation for TYPO3 4.6 based on ADOdb and offering a lot of other features.',
	'category' => 'be',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => '',
	'clearCacheOnLoad' => 0,
	'author' => 'Xavier Perseguers',
	'author_email' => 'xavier@typo3.org',
	'author_company' => '',
	'version' => '6.2.0',
	'_md5_values_when_last_written' => '',
	'constraints' => array(
		'depends' => array(
			'adodb' => '6.2.0-6.2.99',
			'typo3' => '6.2.0-6.2.99',
		),
		'conflicts' => array(),
		'suggests' => array(),
	),
);

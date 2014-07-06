<?php
$EM_CONF[$_EXTKEY] = array(
	'title' => 'Recycler',
	'description' => 'The recycler offers the possibility to restore deleted records or remove them from the database permanently. These actions can be applied to a single record, multiple records, and recursively to child records (ex. restoring a page can restore all content elements on that page). Filtering by page and by table provides a quick overview of deleted records before taking action on them.',
	'category' => 'module',
	'author' => 'Julian Kleinhans',
	'author_email' => 'typo3@kj187.de',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => '',
	'clearCacheOnLoad' => 0,
	'author_company' => '',
	'version' => '6.2.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '6.2.0-6.2.99',
		),
		'conflicts' => array(),
		'suggests' => array(),
	),
	'_md5_values_when_last_written' => '',
);

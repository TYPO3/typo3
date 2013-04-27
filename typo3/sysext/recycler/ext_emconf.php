<?php
/***************************************************************
 * Extension Manager/Repository config file for ext "recycler".
 *
 * Auto generated 25-10-2011 13:11
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/
$EM_CONF[$_EXTKEY] = array(
	'title' => 'Recycler',
	'description' => 'The recycler offers the possibility to restore deleted records or remove them from the database permanently. These actions can be applied to a single record, multiple records, and recursively to child records (ex. restoring a page can restore all content elements on that page). Filtering by page and by table provides a quick overview of deleted records before taking action on them.',
	'category' => 'module',
	'author' => 'Julian Kleinhans',
	'author_email' => 'typo3@kj187.de',
	'shy' => '',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'module' => 'mod1',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '6.1.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '6.1.0-6.1.99',
		),
		'conflicts' => array(),
		'suggests' => array()
	),
	'_md5_values_when_last_written' => '',
	'suggests' => array()
);
?>
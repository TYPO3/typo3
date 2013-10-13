<?php

/*********************************************************************
 * Extension configuration file for ext "filemetadata".
 *********************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Advanced file metadata',
	'description' => 'Add advanced metadata to File.',
	'category' => 'misc',
	'shy' => '',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => '0',
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'version' => '6.2.0',
	'constraints' =>
	array(
		'depends' => array(
			'typo3' => '6.2.0-6.2.99',
		),
		'conflicts' => array(),
		'suggests' => array(),
	),
);

?>
<?php

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Fixture extension for functional tests for Inline Relational Record Editing IRRE',
	'description' => 'based on irre_tutorial extension created by Oliver Hader, see http://forge.typo3.org/projects/extension-irre_tutorial',
	'category' => 'example',
	'shy' => 0,
	'version' => '0.4.0',
	'dependencies' => 'workspaces,version',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => 'mod1',
	'state' => 'beta',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Oliver Hader',
	'author_email' => 'oliver@typo3.org',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'typo3' => '4.5.0-0.0.0',
			'workspaces' => '0.0.0-',
			'version' => '0.0.0-',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'suggests' => array(
	),
);

?>
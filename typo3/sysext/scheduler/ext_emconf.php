<?php
$EM_CONF[$_EXTKEY] = array(
	'title' => 'Scheduler',
	'description' => 'The TYPO3 Scheduler let\'s you register tasks to happen at a specific time',
	'category' => 'misc',
	'version' => '7.5.0',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => '',
	'clearcacheonload' => 0,
	'author' => 'Francois Suter',
	'author_email' => 'francois@typo3.org',
	'author_company' => '',
	'constraints' => array(
		'depends' => array(
			'typo3' => '7.5.0-7.5.99',
		),
		'conflicts' => array(
			'gabriel' => ''
		),
		'suggests' => array(),
	),
);

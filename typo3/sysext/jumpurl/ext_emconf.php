<?php
$EM_CONF[$_EXTKEY] = array(
	'title' => 'JumpURL',
	'description' => 'Allows to modify links to create Jump URLs created in the frontend of the TYPO3 Core.',
	'category' => 'fe',
	'author' => 'TYPO3 CMS Team',
	'author_email' => 'info@typo3.org',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => '',
	'clearCacheOnLoad' => 1,
	'author_company' => '',
	'version' => '7.5.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '7.5.0-7.9.99',
		),
		'conflicts' => array(),
		'suggests' => array(),
	),
);

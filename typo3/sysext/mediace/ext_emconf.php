<?php
$EM_CONF[$_EXTKEY] = array(
	'title' => 'Media Content Element',
	'description' => 'The media functionality from TYPO3 6.2 and earlier can be found here. This extension provides ContentObjects and Content Elements.',
	'category' => 'fe',
	'author' => 'TYPO3 CMS Team',
	'author_email' => 'info@typo3.org',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => 'uploads/media',
	'clearCacheOnLoad' => 1,
	'version' => '7.1.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '7.1.0-7.9.99',
		),
		'conflicts' => array(),
		'suggests' => array(),
	)
);

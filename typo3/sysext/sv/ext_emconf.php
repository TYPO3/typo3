<?php
$EM_CONF[$_EXTKEY] = array(
	'title' => 'TYPO3 System Services',
	'description' => 'The core/default services. This includes the default authentication services for now.',
	'category' => 'services',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => '',
	'clearCacheOnLoad' => 1,
	'author' => 'Rene Fritz',
	'author_email' => 'r.fritz@colorcube.de',
	'author_company' => 'Colorcube',
	'version' => '6.2.0',
	'_md5_values_when_last_written' => '',
	'constraints' => array(
		'depends' => array(
			'typo3' => '6.2.0-6.2.99',
		),
		'conflicts' => array(),
		'suggests' => array(),
	),
);

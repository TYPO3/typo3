<?php
$EM_CONF[$_EXTKEY] = array(
	'title' => 'Tools>Log',
	'description' => 'Displays backend log, both per page and systemwide. Available as the module Tools>Log (system wide overview) and Web>Info/Log (page relative overview).',
	'category' => 'module',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => '',
	'clearCacheOnLoad' => 0,
	'author' => 'Christian Kuhn',
	'author_email' => '',
	'author_company' => '',
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

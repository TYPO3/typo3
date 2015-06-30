<?php
$EM_CONF[$_EXTKEY] = array(
	'title' => 'System > Configuration + DB Check',
	'description' => 'Enables the \'Config\' and \'DB Check\' modules for technical analysis of the system. This includes raw database search, checking relations, counting pages and records etc.',
	'category' => 'module',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => '',
	'clearCacheOnLoad' => 0,
	'author' => 'Kasper Skaarhoj',
	'author_email' => 'kasperYYYY@typo3.com',
	'author_company' => 'Curby Soft Multimedia',
	'version' => '7.4.0',
	'_md5_values_when_last_written' => '',
	'constraints' => array(
		'depends' => array(
			'typo3' => '7.4.0-7.4.99',
		),
		'conflicts' => array(),
		'suggests' => array(),
	),
);

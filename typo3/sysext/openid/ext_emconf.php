<?php
$EM_CONF[$_EXTKEY] = array(
	'title' => 'OpenID authentication',
	'description' => 'Adds OpenID authentication to TYPO3',
	'category' => 'services',
	'author' => 'Dmitry Dulepov',
	'author_email' => 'dmitry@typo3.org',
	'author_company' => 'TYPO3 core team',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => '',
	'clearCacheOnLoad' => 0,
	'version' => '6.2.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '6.2.0-6.2.99',
			'sv' => '6.2.0-6.2.99',
			'setup' => '6.2.0-6.2.99',
		),
		'conflicts' => array(
			'naw_openid' => '',
			'naw_openid_be' => ''
		),
		'suggests' => array(),
	),
	'_md5_values_when_last_written' => '',
);

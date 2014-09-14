<?php
$EM_CONF[$_EXTKEY] = array(
	'title' => 'TYPO3 CMS Backend Styleguide',
	'description' => 'Presents all supported styles for TYPO3 backend modules. Mocks typography, tables, forms, buttons, flash messages and helpers. More at https://github.com/7elix/TYPO3.CMS.Styleguide',
	'category' => 'plugin',
	'author' => 'Felix Kopp',
	'author_email' => 'felix-source@phorax.com',
	'author_company' => 'PHORAX',
	'state' => 'stable',
	'uploadfolder' => '0',
	'createDirs' => '',
	'clearCacheOnLoad' => 0,
	'version' => '0.8.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '6.2.0-6.2.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
);
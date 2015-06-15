<?php
$EM_CONF[$_EXTKEY] = array(
	'title' => 'subtypes_exclude_list test',
	'description' => 'Extension for testing the issue with the TCA setting "subtypes_exclude_list described in #47359',
	'category' => 'example',
	'version' => '1.0.0',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => '',
	'clearcacheonload' => 0,
	'author' => 'Alexander Stehlik',
	'author_email' => 'alexander.stehlik.deleteme@gmail.com',
	'author_company' => '',
	'constraints' => array(
		'depends' => array(
			'typo3' => '6.2.1-0.0.0',
		),
		'conflicts' => array(),
		'suggests' => array(),
	),
);
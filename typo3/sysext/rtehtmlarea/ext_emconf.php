<?php
$EM_CONF[$_EXTKEY] = array(
	'title' => 'htmlArea RTE',
	'description' => 'Rich Text Editor.',
	'category' => 'be',
	'state' => 'stable',
	'uploadfolder' => 1,
	'createDirs' => '',
	'clearCacheOnLoad' => 0,
	'author' => 'Stanislas Rolland',
	'author_email' => 'typo3(arobas)sjbr.ca',
	'author_company' => 'SJBR',
	'version' => '6.2.0',
	'_md5_values_when_last_written' => '',
	'constraints' => array(
		'depends' => array(
			'typo3' => '6.2.0-6.2.99',
		),
		'conflicts' => array(
			'rte_conf' => '',
			'tkr_rteanchors' => '',
			'ad_rtepasteplain' => '',
			'rtehtmlarea_definitionlist' => '',
		),
		'suggests' => array(
			'rtehtmlarea_api_manual' => '',
			'setup' => '',
		),
	),
);

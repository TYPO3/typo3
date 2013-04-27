<?php
/***************************************************************
 * Extension Manager/Repository config file for ext "rtehtmlarea".
 *
 * Auto generated 12-03-2012 13:43
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/
$EM_CONF[$_EXTKEY] = array(
	'title' => 'htmlArea RTE',
	'description' => 'Rich Text Editor.',
	'category' => 'be',
	'shy' => 0,
	'dependencies' => '',
	'conflicts' => 'rte_conf,tkr_rteanchors,ad_rtepasteplain,rtehtmlarea_definitionlist',
	'priority' => '',
	'loadOrder' => '',
	'module' => 'mod3,mod4,mod5,mod6',
	'state' => 'stable',
	'internal' => 0,
	'uploadfolder' => 1,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author' => 'Stanislas Rolland',
	'author_email' => 'typo3(arobas)sjbr.ca',
	'author_company' => 'SJBR',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'version' => '6.1.0',
	'_md5_values_when_last_written' => '',
	'constraints' => array(
		'depends' => array(
			'typo3' => '6.1.0-6.1.99',
		),
		'conflicts' => array(
			'rte_conf' => '',
			'tkr_rteanchors' => '',
			'ad_rtepasteplain' => '',
			'rtehtmlarea_definitionlist' => ''
		),
		'suggests' => array(
			'rtehtmlarea_api_manual' => '',
			'setup' => ''
		)
	),
	'suggests' => array('rtehtmlarea_api_manual', 'setup')
);
?>
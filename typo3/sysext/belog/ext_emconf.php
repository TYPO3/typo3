<?php

########################################################################
# Extension Manager/Repository config file for ext: "belog"
#
# Auto generated 23-04-2008 10:38
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Tools>Log',
	'description' => 'Displays backend log, both per page and systemwide. Available as the module Tools>Log (system wide overview) and Web>Info/Log (page relative overview).',
	'category' => 'module',
	'shy' => 1,
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => 'mod',
	'state' => 'stable',
	'internal' => 0,
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author' => 'Kasper Skaarhoj',
	'author_email' => 'kasperYYYY@typo3.com',
	'author_company' => 'Curby Soft Multimedia',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'version' => '0.2.0',
	'_md5_values_when_last_written' => 'a:11:{s:26:"class.tx_belog_webinfo.php";s:4:"7fd3";s:12:"ext_icon.gif";s:4:"a61e";s:14:"ext_tables.php";s:4:"694b";s:13:"locallang.xml";s:4:"4caf";s:13:"mod/clear.gif";s:4:"cc11";s:12:"mod/conf.php";s:4:"cd38";s:13:"mod/index.php";s:4:"0a62";s:17:"mod/locallang.xml";s:4:"4e37";s:21:"mod/locallang_mod.xml";s:4:"9623";s:11:"mod/log.gif";s:4:"a61e";s:12:"doc/TODO.txt";s:4:"1631";}',
	'constraints' => array(
		'depends' => array(
			'php' => '5.1.0-0.0.0',
			'typo3' => '4.2.0-4.2.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'suggests' => array(
	),
);

?>
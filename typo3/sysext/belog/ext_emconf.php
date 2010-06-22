<?php

########################################################################
# Extension Manager/Repository config file for ext "belog".
#
# Auto generated 22-06-2010 13:05
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
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
	'doNotLoadInFE' => 1,
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
	'version' => '1.0.0',
	'_md5_values_when_last_written' => 'a:11:{s:26:"class.tx_belog_webinfo.php";s:4:"cdf4";s:12:"ext_icon.gif";s:4:"691d";s:14:"ext_tables.php";s:4:"694b";s:13:"locallang.xml";s:4:"4caf";s:12:"doc/TODO.txt";s:4:"1631";s:13:"mod/clear.gif";s:4:"cc11";s:12:"mod/conf.php";s:4:"cd38";s:13:"mod/index.php";s:4:"6e08";s:17:"mod/locallang.xml";s:4:"f603";s:21:"mod/locallang_mod.xml";s:4:"9623";s:11:"mod/log.gif";s:4:"a61e";}',
	'constraints' => array(
		'depends' => array(
			'php' => '5.1.0-0.0.0',
			'typo3' => '4.4.0-0.0.0',
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
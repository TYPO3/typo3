<?php

########################################################################
# Extension Manager/Repository config file for ext "info".
#
# Auto generated 26-01-2011 20:08
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Web>Info',
	'description' => 'Shows various infos',
	'category' => 'module',
	'shy' => 1,
	'dependencies' => 'cms',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => 'view',
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
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'version' => '1.0.0',
	'_md5_values_when_last_written' => 'a:7:{s:12:"ext_icon.gif";s:4:"8c52";s:14:"ext_tables.php";s:4:"d1b4";s:14:"mod1/clear.gif";s:4:"cc11";s:13:"mod1/conf.php";s:4:"a6eb";s:14:"mod1/index.php";s:4:"83a6";s:13:"mod1/info.gif";s:4:"8a42";s:12:"mod1/log.gif";s:4:"70bb";}',
	'constraints' => array(
		'depends' => array(
			'cms' => '',
			'php' => '5.1.0-0.0.0',
			'typo3' => '4.5.0-0.0.0',
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
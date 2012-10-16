<?php

########################################################################
# Extension Manager/Repository config file for ext "perm".
#
# Auto generated 16-10-2012 14:07
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Web>Access',
	'description' => 'Page editing permissions',
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
	'version' => '4.7.5',
	'_md5_values_when_last_written' => 'a:8:{s:12:"ext_icon.gif";s:4:"6cd0";s:14:"ext_tables.php";s:4:"25ae";s:35:"mod1/class.sc_mod_web_perm_ajax.php";s:4:"c166";s:14:"mod1/clear.gif";s:4:"cc11";s:13:"mod1/conf.php";s:4:"ff0c";s:14:"mod1/index.php";s:4:"8022";s:13:"mod1/perm.gif";s:4:"c751";s:12:"mod1/perm.js";s:4:"20ba";}',
	'constraints' => array(
		'depends' => array(
			'php' => '5.3.0-0.0.0',
			'typo3' => '4.7.0-0.0.0',
			'cms' => '',
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
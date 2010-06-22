<?php

########################################################################
# Extension Manager/Repository config file for ext "beuser".
#
# Auto generated 22-06-2010 13:05
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Tools>User Admin',
	'description' => 'Backend user administration and overview. Allows you to compare the settings of users and verify their permissions and see who is online.',
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
	'_md5_values_when_last_written' => 'a:12:{s:19:"class.tx_beuser.php";s:4:"934e";s:34:"class.tx_beuser_switchbackuser.php";s:4:"1218";s:12:"ext_icon.gif";s:4:"8f11";s:17:"ext_localconf.php";s:4:"c778";s:14:"ext_tables.php";s:4:"21c0";s:12:"doc/TODO.txt";s:4:"02ed";s:14:"mod/beuser.gif";s:4:"2804";s:13:"mod/clear.gif";s:4:"cc11";s:12:"mod/conf.php";s:4:"f320";s:13:"mod/index.php";s:4:"539b";s:17:"mod/locallang.xml";s:4:"6578";s:21:"mod/locallang_mod.xml";s:4:"4155";}',
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
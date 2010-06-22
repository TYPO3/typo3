<?php

########################################################################
# Extension Manager/Repository config file for ext "taskcenter".
#
# Auto generated 22-06-2010 13:53
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'User>Task Center',
	'description' => 'The Task Center is the framework for a host of other extensions, see below.',
	'category' => 'module',
	'shy' => 1,
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
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
	'version' => '2.0.0',
	'_md5_values_when_last_written' => 'a:20:{s:16:"ext_autoload.php";s:4:"d432";s:12:"ext_icon.gif";s:4:"7c85";s:14:"ext_tables.php";s:4:"492b";s:13:"locallang.xml";s:4:"26ca";s:38:"classes/class.tx_taskcenter_status.php";s:4:"9b0b";s:14:"doc/manual.sxw";s:4:"6598";s:43:"interfaces/interface.tx_taskcenter_task.php";s:4:"eee4";s:23:"res/item-background.jpg";s:4:"c87c";s:21:"res/list-item-act.gif";s:4:"6fa4";s:17:"res/list-item.gif";s:4:"e82d";s:18:"res/mod_styles.css";s:4:"a07d";s:21:"res/mod_template.html";s:4:"eb07";s:14:"task/clear.gif";s:4:"cc11";s:13:"task/conf.php";s:4:"deb0";s:13:"task/icon.gif";s:4:"7941";s:14:"task/index.php";s:4:"38dd";s:19:"task/index.php.orig";s:4:"87b2";s:18:"task/locallang.xml";s:4:"d68a";s:22:"task/locallang_mod.xml";s:4:"c0f2";s:13:"task/task.gif";s:4:"fc53";}',
	'constraints' => array(
		'depends' => array(
			'typo3' => '4.4.0-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
			'sys_action' => '1.2.0-0.0.0',
		),
	),
	'suggests' => array(
	),
);

?>
<?php

########################################################################
# Extension Manager/Repository config file for ext "taskcenter".
#
# Auto generated 21-10-2009 11:25
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
	'module' => 'task',
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
	'version' => '0.2.0',
	'_md5_values_when_last_written' => 'a:9:{s:12:"ext_icon.gif";s:4:"fc53";s:14:"ext_tables.php";s:4:"837d";s:28:"task/class.mod_user_task.php";s:4:"5869";s:14:"task/clear.gif";s:4:"cc11";s:13:"task/conf.php";s:4:"1fae";s:14:"task/index.php";s:4:"87b2";s:18:"task/locallang.xml";s:4:"0f9b";s:22:"task/locallang_mod.xml";s:4:"c0f2";s:13:"task/task.gif";s:4:"fc53";}',
	'constraints' => array(
		'depends' => array(
			'php' => '5.1.0-0.0.0',
			'typo3' => '4.3.0-4.3.99',
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
<?php

########################################################################
# Extension Manager/Repository config file for ext "sys_action".
#
# Auto generated 22-06-2010 17:19
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'User>Task Center, Actions',
	'description' => 'Actions are \'programmed\' admin tasks which can be performed by selected regular users from the Task Center. An action could be creation of backend users, fixed SQL SELECT queries, listing of records, direct edit access to selected records etc.',
	'category' => 'module',
	'shy' => 0,
	'dependencies' => 'taskcenter',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
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
	'_md5_values_when_last_written' => 'a:17:{s:27:"class.tx_sysaction.php.orig";s:4:"b40e";s:26:"class.tx_sysaction.php.rej";s:4:"ef38";s:16:"ext_autoload.php";s:4:"9b72";s:12:"ext_icon.gif";s:4:"8ea4";s:14:"ext_tables.php";s:4:"ef56";s:14:"ext_tables.sql";s:4:"c36f";s:13:"locallang.xml";s:4:"4dac";s:27:"locallang_csh_sysaction.xml";s:4:"a1d4";s:17:"locallang_tca.xml";s:4:"abe1";s:7:"tca.php";s:4:"1604";s:16:"x-sys_action.png";s:4:"4764";s:14:"doc/manual.sxw";s:4:"5228";s:32:"task/class.tx_sysaction_task.php";s:4:"23a3";s:46:"toolbarmenu/class.tx_sysaction_toolbarmenu.php";s:4:"2cf6";s:35:"toolbarmenu/registerToolbarItem.php";s:4:"58a6";s:29:"toolbarmenu/tx_sysactions.css";s:4:"e1fa";s:28:"toolbarmenu/tx_sysactions.js";s:4:"72f0";}',
	'constraints' => array(
		'depends' => array(
			'taskcenter' => '2.0.0-0.0.0',
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
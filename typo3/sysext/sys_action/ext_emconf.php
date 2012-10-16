<?php

########################################################################
# Extension Manager/Repository config file for ext "sys_action".
#
# Auto generated 16-10-2012 14:08
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
	'version' => '4.7.5',
	'_md5_values_when_last_written' => 'a:16:{s:16:"ext_autoload.php";s:4:"b218";s:12:"ext_icon.gif";s:4:"804a";s:14:"ext_tables.php";s:4:"cd52";s:14:"ext_tables.sql";s:4:"c36f";s:13:"locallang.xlf";s:4:"455a";s:27:"locallang_csh_sysaction.xlf";s:4:"581a";s:17:"locallang_tca.xlf";s:4:"f045";s:7:"tca.php";s:4:"1604";s:16:"x-sys_action.png";s:4:"8076";s:14:"doc/manual.sxw";s:4:"5228";s:32:"task/class.tx_sysaction_list.php";s:4:"4ab3";s:32:"task/class.tx_sysaction_task.php";s:4:"2d3d";s:46:"toolbarmenu/class.tx_sysaction_toolbarmenu.php";s:4:"c83e";s:35:"toolbarmenu/registerToolbarItem.php";s:4:"58a6";s:29:"toolbarmenu/tx_sysactions.css";s:4:"e1fa";s:28:"toolbarmenu/tx_sysactions.js";s:4:"b568";}',
	'constraints' => array(
		'depends' => array(
			'php' => '5.3.0-0.0.0',
			'typo3' => '4.7.0-0.0.0',
			'taskcenter' => '2.1.0-0.0.0',
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
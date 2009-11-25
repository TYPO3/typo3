<?php

########################################################################
# Extension Manager/Repository config file for ext "sys_action".
#
# Auto generated 25-11-2009 22:10
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
	'version' => '1.2.0',
	'_md5_values_when_last_written' => 'a:15:{s:8:"TODO.txt";s:4:"17ff";s:22:"class.tx_sysaction.php";s:4:"6103";s:12:"ext_icon.gif";s:4:"f410";s:14:"ext_tables.php";s:4:"6561";s:14:"ext_tables.sql";s:4:"c36f";s:13:"locallang.xml";s:4:"6f52";s:27:"locallang_csh_sysaction.xml";s:4:"a1d4";s:17:"locallang_tca.xml";s:4:"dd78";s:14:"sys_action.gif";s:4:"eb3a";s:17:"sys_action__h.gif";s:4:"7a29";s:7:"tca.php";s:4:"f3d0";s:46:"toolbarmenu/class.tx_sysaction_toolbarmenu.php";s:4:"978d";s:35:"toolbarmenu/registerToolbarItem.php";s:4:"58a6";s:29:"toolbarmenu/tx_sysactions.css";s:4:"bd1b";s:28:"toolbarmenu/tx_sysactions.js";s:4:"deb0";}',
	'constraints' => array(
		'depends' => array(
			'taskcenter' => '',
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
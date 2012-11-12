<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "aboutmodules".
 *
 * Auto generated 12-11-2012 21:55
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Help>About Modules',
	'description' => 'Shows an overview of the installed and available modules including description and links.',
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
	'version' => '1.1.0',
	'_md5_values_when_last_written' => 'a:8:{s:35:"class.tx_aboutmodules_functions.php";s:4:"80cb";s:16:"ext_autoload.php";s:4:"06c5";s:12:"ext_icon.gif";s:4:"6101";s:14:"ext_tables.php";s:4:"920d";s:20:"mod/aboutmodules.gif";s:4:"711d";s:12:"mod/conf.php";s:4:"9a03";s:13:"mod/index.php";s:4:"acd7";s:21:"mod/locallang_mod.xlf";s:4:"8645";}',
	'constraints' => array(
		'depends' => array(
			'php' => '5.3.0-0.0.0',
			'typo3' => '4.6.0-0.0.0',
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
<?php

########################################################################
# Extension Manager/Repository config file for ext "wizard_crpages".
#
# Auto generated 22-06-2010 13:06
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Web>Func, Wizards, Create multiple pages',
	'description' => 'A little utility to create many empty pages in one batch. Great for making a quick page structure.',
	'category' => 'module',
	'shy' => 1,
	'dependencies' => 'func_wizards',
	'conflicts' => '',
	'priority' => '',
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
	'version' => '1.0.0',
	'_md5_values_when_last_written' => 'a:7:{s:36:"class.tx_wizardcrpages_webfunc_2.php";s:4:"de3d";s:12:"ext_icon.gif";s:4:"dd33";s:14:"ext_tables.php";s:4:"7e2d";s:13:"locallang.xml";s:4:"c897";s:17:"locallang_csh.xml";s:4:"c7d9";s:23:"cshimages/wizards_1.png";s:4:"4d49";s:23:"cshimages/wizards_2.png";s:4:"4d0f";}',
	'constraints' => array(
		'depends' => array(
			'func_wizards' => '',
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
<?php

########################################################################
# Extension Manager/Repository config file for ext "aboutmodules".
#
# Auto generated 16-10-2012 14:04
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Help>About Modules',
	'description' => 'Shows an overview of the installed and available modules including description and links.',
	'category' => 'module',
	'shy' => 1,
	'dependencies' => 'extbase,fluid',
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
	'version' => '4.7.5',
	'_md5_values_when_last_written' => 'a:8:{s:35:"class.tx_aboutmodules_functions.php";s:4:"1fc2";s:16:"ext_autoload.php";s:4:"06c5";s:12:"ext_icon.gif";s:4:"6101";s:14:"ext_tables.php";s:4:"aede";s:40:"Classes/Controller/ModulesController.php";s:4:"f6e2";s:44:"Resources/Private/Language/locallang_mod.xlf";s:4:"8645";s:38:"Resources/Private/Layouts/Default.html";s:4:"42e7";s:46:"Resources/Private/Templates/Modules/Index.html";s:4:"0596";}',
	'constraints' => array(
		'depends' => array(
			'php' => '5.3.0-0.0.0',
			'typo3' => '4.7.0-0.0.0',
			'extbase' => '',
			'fluid' => '',
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
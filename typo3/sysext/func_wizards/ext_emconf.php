<?php

########################################################################
# Extension Manager/Repository config file for ext "func_wizards".
#
# Auto generated 16-10-2012 14:06
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Web>Func, Wizards',
	'description' => 'Adds the \'Wizards\' item to the function menu in Web>Func. This is just a framework for wizard extensions.',
	'category' => 'module',
	'shy' => 1,
	'dependencies' => 'func',
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
	'_md5_values_when_last_written' => 'a:5:{s:32:"class.tx_funcwizards_webfunc.php";s:4:"d42e";s:12:"ext_icon.gif";s:4:"ff38";s:14:"ext_tables.php";s:4:"17f9";s:13:"locallang.xlf";s:4:"de11";s:17:"locallang_csh.xlf";s:4:"9f28";}',
	'constraints' => array(
		'depends' => array(
			'php' => '5.3.0-0.0.0',
			'typo3' => '4.7.0-0.0.0',
			'func' => '',
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
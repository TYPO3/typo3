<?php

########################################################################
# Extension Manager/Repository config file for ext "info_pagetsconfig".
#
# Auto generated 22-01-2011 20:10
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Web>Info, Page TSconfig',
	'description' => 'Displays the compiled Page TSconfig values relative to a page.',
	'category' => 'module',
	'shy' => 1,
	'dependencies' => 'info',
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
	'version' => '1.0.0',
	'_md5_values_when_last_written' => 'a:11:{s:37:"class.tx_infopagetsconfig_webinfo.php";s:4:"038e";s:12:"ext_icon.gif";s:4:"04b0";s:14:"ext_tables.php";s:4:"935e";s:13:"locallang.xml";s:4:"9750";s:25:"locallang_csh_webinfo.xml";s:4:"fd3b";s:19:"cshimages/img_1.png";s:4:"a129";s:19:"cshimages/img_2.png";s:4:"a10c";s:19:"cshimages/img_3.png";s:4:"329d";s:19:"cshimages/img_4.png";s:4:"e596";s:19:"cshimages/img_5.png";s:4:"34b9";s:12:"doc/TODO.txt";s:4:"418c";}',
	'constraints' => array(
		'depends' => array(
			'php' => '5.1.0-0.0.0',
			'typo3' => '4.4.0-0.0.0',
			'info' => '',
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
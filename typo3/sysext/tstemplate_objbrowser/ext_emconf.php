<?php

########################################################################
# Extension Manager/Repository config file for ext "tstemplate_objbrowser".
#
# Auto generated 16-10-2012 14:08
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Web>Template, Object Browser',
	'description' => 'The Object Browser writes out the TypoScript configuration in an object tree style.',
	'category' => 'module',
	'shy' => 1,
	'dependencies' => 'tstemplate',
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
	'_md5_values_when_last_written' => 'a:6:{s:9:"ChangeLog";s:4:"63a4";s:33:"class.tx_tstemplateobjbrowser.php";s:4:"8098";s:12:"ext_icon.gif";s:4:"c2e1";s:14:"ext_tables.php";s:4:"a29f";s:13:"locallang.xlf";s:4:"0af1";s:12:"doc/TODO.txt";s:4:"6bb0";}',
	'constraints' => array(
		'depends' => array(
			'php' => '5.3.0-0.0.0',
			'typo3' => '4.7.0-0.0.0',
			'tstemplate' => '',
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
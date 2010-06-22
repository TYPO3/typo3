<?php

########################################################################
# Extension Manager/Repository config file for ext "sys_note".
#
# Auto generated 22-06-2010 12:44
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Internal notes',
	'description' => 'Records with messages which can be placed on any page and contain instructions or other information related to a page or section.',
	'category' => 'be',
	'shy' => 1,
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'doNotLoadInFE' => 1,
	'state' => 'stable',
	'internal' => 1,
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
	'version' => '1.1.2',
	'_md5_values_when_last_written' => 'a:5:{s:12:"ext_icon.gif";s:4:"bf22";s:14:"ext_tables.php";s:4:"847c";s:14:"ext_tables.sql";s:4:"18f7";s:25:"locallang_csh_sysnote.xml";s:4:"efaa";s:17:"locallang_tca.xml";s:4:"456e";}',
	'constraints' => array(
		'depends' => array(
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
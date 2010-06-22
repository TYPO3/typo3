<?php

########################################################################
# Extension Manager/Repository config file for ext "tstemplate_info".
#
# Auto generated 22-06-2010 13:06
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Web>Template, Info/Modify',
	'description' => 'Quick and easy editing of template record Setup and Constants fields. Allows editing of attached txt-resource files.',
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
	'version' => '1.0.0',
	'_md5_values_when_last_written' => 'a:5:{s:9:"ChangeLog";s:4:"4105";s:27:"class.tx_tstemplateinfo.php";s:4:"b724";s:12:"ext_icon.gif";s:4:"ecbc";s:14:"ext_tables.php";s:4:"d827";s:13:"locallang.xml";s:4:"06a8";}',
	'constraints' => array(
		'depends' => array(
			'tstemplate' => '',
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
<?php

########################################################################
# Extension Manager/Repository config file for ext: "tstemplate_analyzer"
#
# Auto generated 23-04-2008 10:50
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Web>Template, Template analyzer',
	'description' => 'Analyzes the hierarchy of included static and custom template records.',
	'category' => 'module',
	'shy' => 1,
	'dependencies' => 'tstemplate',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
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
	'version' => '0.1.0',
	'_md5_values_when_last_written' => 'a:4:{s:9:"ChangeLog";s:4:"b76f";s:31:"class.tx_tstemplateanalyzer.php";s:4:"9095";s:12:"ext_icon.gif";s:4:"52a3";s:14:"ext_tables.php";s:4:"f45f";}',
	'constraints' => array(
		'depends' => array(
			'tstemplate' => '',
			'php' => '5.1.0-0.0.0',
			'typo3' => '4.2.0-4.2.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
);

?>
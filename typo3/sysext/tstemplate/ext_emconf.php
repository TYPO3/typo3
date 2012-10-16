<?php

########################################################################
# Extension Manager/Repository config file for ext "tstemplate".
#
# Auto generated 16-10-2012 14:08
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Web>Template',
	'description' => 'Framework for management of TypoScript template records for the CMS frontend.',
	'category' => 'module',
	'shy' => 1,
	'dependencies' => 'cms',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => 'ts',
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
	'_md5_values_when_last_written' => 'a:10:{s:9:"ChangeLog";s:4:"9bb8";s:12:"ext_icon.gif";s:4:"e0ad";s:14:"ext_tables.php";s:4:"041b";s:12:"ts/clear.gif";s:4:"cc11";s:11:"ts/conf.php";s:4:"9e80";s:12:"ts/index.php";s:4:"3715";s:16:"ts/locallang.xlf";s:4:"fa74";s:20:"ts/locallang_mod.xlf";s:4:"b08e";s:9:"ts/ts.gif";s:4:"18cb";s:10:"ts/ts1.gif";s:4:"e0ad";}',
	'constraints' => array(
		'depends' => array(
			'php' => '5.3.0-0.0.0',
			'typo3' => '4.7.0-0.0.0',
			'cms' => '',
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
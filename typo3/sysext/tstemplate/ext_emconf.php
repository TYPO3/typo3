<?php

########################################################################
# Extension Manager/Repository config file for ext "tstemplate".
#
# Auto generated 26-01-2011 20:08
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
	'version' => '1.0.1',
	'_md5_values_when_last_written' => 'a:10:{s:9:"ChangeLog";s:4:"9bb8";s:12:"ext_icon.gif";s:4:"e0ad";s:14:"ext_tables.php";s:4:"041b";s:12:"ts/clear.gif";s:4:"cc11";s:11:"ts/conf.php";s:4:"c6d6";s:12:"ts/index.php";s:4:"157f";s:16:"ts/locallang.xml";s:4:"f223";s:20:"ts/locallang_mod.xml";s:4:"98d2";s:9:"ts/ts.gif";s:4:"18cb";s:10:"ts/ts1.gif";s:4:"e0ad";}',
	'constraints' => array(
		'depends' => array(
			'cms' => '',
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
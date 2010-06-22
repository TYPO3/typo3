<?php

########################################################################
# Extension Manager/Repository config file for ext "tsconfig_help".
#
# Auto generated 22-06-2010 12:44
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'TSConfig / TypoScript Object Reference',
	'description' => 'Object reference for TSref, Page TSconfig and User TSconfig which is enabled by the TS icon close to the TSconfig field.',
	'category' => 'be',
	'author' => 'Stephane Schitter',
	'author_email' => 'stephane.schitter@free.fr',
	'shy' => 0,
	'dependencies' => 'cms',
	'conflicts' => '',
	'priority' => '',
	'module' => 'mod1',
	'doNotLoadInFE' => 1,
	'state' => 'stable',
	'internal' => 1,
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'author_company' => '',
	'version' => '1.1.0',
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
	'_md5_values_when_last_written' => 'a:10:{s:12:"ext_icon.gif";s:4:"2ad2";s:14:"ext_tables.php";s:4:"1018";s:14:"ext_tables.sql";s:4:"492c";s:25:"ext_tables_static+adt.sql";s:4:"e557";s:12:"doc/TODO.txt";s:4:"cfc6";s:13:"mod1/conf.php";s:4:"89c9";s:14:"mod1/index.php";s:4:"6650";s:18:"mod1/locallang.xml";s:4:"1863";s:22:"mod1/locallang_mod.xml";s:4:"23aa";s:19:"mod1/moduleicon.gif";s:4:"b78a";}',
	'suggests' => array(
	),
);

?>
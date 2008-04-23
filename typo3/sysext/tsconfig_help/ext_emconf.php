<?php

########################################################################
# Extension Manager/Repository config file for ext: "tsconfig_help"
#
# Auto generated 23-04-2008 11:22
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'TSConfig / TypoScript Object Reference',
	'description' => 'Object reference for TSref, Page TSconfig and User TSconfig which is enabled by the TS icon close to the TSconfig field.',
	'category' => 'be',
	'author' => 'Stephane Schitter',
	'author_email' => 'stephane.schitter@free.fr',
	'shy' => 1,
	'dependencies' => 'cms',
	'conflicts' => '',
	'priority' => '',
	'module' => 'mod1',
	'state' => 'beta',
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
			'typo3' => '4.2.0-4.2.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:10:{s:12:"ext_icon.gif";s:4:"bdd9";s:14:"ext_tables.php";s:4:"1592";s:14:"ext_tables.sql";s:4:"9fbd";s:25:"ext_tables_static+adt.sql";s:4:"dda2";s:13:"mod1/conf.php";s:4:"b39e";s:14:"mod1/index.php";s:4:"50cf";s:18:"mod1/locallang.xml";s:4:"1863";s:22:"mod1/locallang_mod.xml";s:4:"23aa";s:19:"mod1/moduleicon.gif";s:4:"b78a";s:12:"doc/TODO.txt";s:4:"cfc6";}',
	'suggests' => array(
	),
);

?>
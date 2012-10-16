<?php

########################################################################
# Extension Manager/Repository config file for ext "felogin".
#
# Auto generated 16-10-2012 14:18
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Frontend Login for Website Users',
	'description' => 'A template-based plugin to log in Website Users in the Frontend',
	'category' => 'plugin',
	'author' => 'Steffen Kamper',
	'author_email' => 'info@sk-typo3.de',
	'shy' => '',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 1,
	'createDirs' => '',
	'modify_tables' => 'fe_groups,fe_users',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author_company' => '',
	'version' => '1.4.0',
	'constraints' => array(
		'depends' => array(
			'php' => '5.3.0-0.0.0',
			'typo3' => '4.6.0-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:13:{s:9:"ChangeLog";s:4:"a6aa";s:12:"ext_icon.gif";s:4:"7160";s:17:"ext_localconf.php";s:4:"0ae4";s:14:"ext_tables.php";s:4:"cd2c";s:14:"ext_tables.sql";s:4:"7d28";s:24:"ext_typoscript_setup.txt";s:4:"a5b2";s:12:"flexform.xml";s:4:"6a0a";s:16:"locallang_db.xlf";s:4:"2791";s:13:"template.html";s:4:"0075";s:14:"doc/manual.sxw";s:4:"c90f";s:28:"pi1/class.tx_felogin_pi1.php";s:4:"efd0";s:17:"pi1/locallang.xlf";s:4:"bf93";s:24:"tests/tx_feloginTest.php";s:4:"cd06";}',
	'suggests' => array(
	),
);

?>
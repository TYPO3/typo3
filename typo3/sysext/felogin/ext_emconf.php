<?php

########################################################################
# Extension Manager/Repository config file for ext: "felogin"
#
# Auto generated 11-03-2009 19:10
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
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
	'state' => 'beta',
	'internal' => '',
	'uploadfolder' => 1,
	'createDirs' => '',
	'modify_tables' => 'fe_groups,fe_users',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author_company' => '',
	'version' => '1.1.0',
	'constraints' => array(
		'depends' => array(
			'php' => '5.1.0-0.0.0',
			'typo3' => '4.3.0-4.3.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:12:{s:9:"ChangeLog";s:4:"a6aa";s:12:"ext_icon.gif";s:4:"7160";s:17:"ext_localconf.php";s:4:"4e6e";s:14:"ext_tables.php";s:4:"ac24";s:14:"ext_tables.sql";s:4:"640e";s:24:"ext_typoscript_setup.txt";s:4:"ba3c";s:12:"flexform.xml";s:4:"8f57";s:16:"locallang_db.xml";s:4:"a75c";s:13:"template.html";s:4:"1452";s:14:"doc/manual.sxw";s:4:"44e7";s:28:"pi1/class.tx_felogin_pi1.php";s:4:"42da";s:17:"pi1/locallang.xml";s:4:"440b";}',
	'suggests' => array(
	),
);

?>
<?php

########################################################################
# Extension Manager/Repository config file for ext "felogin".
#
# Auto generated 22-06-2010 13:08
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
	'version' => '1.3.0',
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
	'_md5_values_when_last_written' => 'a:12:{s:9:"ChangeLog";s:4:"a6aa";s:12:"ext_icon.gif";s:4:"7160";s:17:"ext_localconf.php";s:4:"b601";s:14:"ext_tables.php";s:4:"4845";s:14:"ext_tables.sql";s:4:"7d28";s:24:"ext_typoscript_setup.txt";s:4:"6e62";s:12:"flexform.xml";s:4:"8f57";s:16:"locallang_db.xml";s:4:"a75c";s:13:"template.html";s:4:"0075";s:14:"doc/manual.sxw";s:4:"d32a";s:28:"pi1/class.tx_felogin_pi1.php";s:4:"5305";s:17:"pi1/locallang.xml";s:4:"9c2b";}',
	'suggests' => array(
	),
);

?>
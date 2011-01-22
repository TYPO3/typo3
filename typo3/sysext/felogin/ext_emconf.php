<?php

########################################################################
# Extension Manager/Repository config file for ext "felogin".
#
# Auto generated 22-01-2011 20:10
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
	'version' => '1.3.1',
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
	'_md5_values_when_last_written' => 'a:13:{s:9:"ChangeLog";s:4:"a6aa";s:12:"ext_icon.gif";s:4:"7160";s:17:"ext_localconf.php";s:4:"b601";s:14:"ext_tables.php";s:4:"3d65";s:14:"ext_tables.sql";s:4:"7d28";s:24:"ext_typoscript_setup.txt";s:4:"caea";s:12:"flexform.xml";s:4:"6b9c";s:16:"locallang_db.xml";s:4:"e35b";s:13:"template.html";s:4:"0075";s:14:"doc/manual.sxw";s:4:"c90f";s:28:"pi1/class.tx_felogin_pi1.php";s:4:"1a29";s:17:"pi1/locallang.xml";s:4:"6c92";s:24:"tests/tx_feloginTest.php";s:4:"a88f";}',
	'suggests' => array(
	),
);

?>
<?php

########################################################################
# Extension Manager/Repository config file for ext: "felogin"
#
# Auto generated 15-01-2008 18:29
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
	'version' => '1.0.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '4.1.9-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:11:{s:9:"ChangeLog";s:4:"9dfe";s:12:"ext_icon.gif";s:4:"7160";s:17:"ext_localconf.php";s:4:"1dbd";s:14:"ext_tables.php";s:4:"0a9e";s:14:"ext_tables.sql";s:4:"640e";s:24:"ext_typoscript_setup.txt";s:4:"9f50";s:12:"flexform.xml";s:4:"2e79";s:16:"locallang_db.xml";s:4:"a53e";s:13:"template.html";s:4:"4394";s:28:"pi1/class.tx_felogin_pi1.php";s:4:"0a41";s:17:"pi1/locallang.xml";s:4:"6d7d";}',
	'suggests' => array(
	),
);

?>
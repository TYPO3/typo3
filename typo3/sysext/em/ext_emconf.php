<?php

########################################################################
# Extension Manager/Repository config file for ext "em".
#
# Auto generated 24-08-2010 14:42
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Ext Manager',
	'description' => 'TYPO3 Extension Manager',
	'category' => 'module',
	'shy' => 1,
	'dependencies' => 'cms',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => 'view',
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
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'version' => '1.1.0',
	'_md5_values_when_last_written' => 'a:16:{s:12:"ext_icon.gif";s:4:"3a30";s:14:"ext_tables.php";s:4:"6e0d";s:23:"mod1/class.em_index.php";s:4:"4966";s:22:"mod1/class.em_soap.php";s:4:"67b6";s:31:"mod1/class.em_terconnection.php";s:4:"630f";s:23:"mod1/class.em_unzip.php";s:4:"e3a9";s:28:"mod1/class.em_xmlhandler.php";s:4:"8445";s:21:"mod1/class.nusoap.php";s:4:"dfa8";s:14:"mod1/clear.gif";s:4:"cc11";s:13:"mod1/conf.php";s:4:"6ea9";s:17:"mod1/download.png";s:4:"c5b2";s:11:"mod1/em.gif";s:4:"3a30";s:14:"mod1/index.php";s:4:"e8e5";s:16:"mod1/install.gif";s:4:"8d57";s:14:"mod1/oodoc.gif";s:4:"744b";s:18:"mod1/uninstall.gif";s:4:"a77f";}',
	'constraints' => array(
		'depends' => array(
			'cms' => '',
			'php' => '5.1.0-0.0.0',
			'typo3' => '4.5.0-0.0.0',
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
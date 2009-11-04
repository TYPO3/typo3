<?php

########################################################################
# Extension Manager/Repository config file for ext "dbal".
#
# Auto generated 20-10-2009 14:28
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Database Abstraction Layer',
	'description' => 'A database abstraction layer implementation for TYPO3 4.0 based on ADOdb and offering a lot of other features...',
	'category' => 'be',
	'shy' => 0,
	'dependencies' => 'adodb',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => 'mod1',
	'state' => 'beta',
	'internal' => 0,
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author' => 'Xavier Perseguers',
	'author_email' => 'typo3@perseguers.ch',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'version' => '0.9.23',
	'_md5_values_when_last_written' => 'a:20:{s:9:"ChangeLog";s:4:"72b8";s:26:"class.ux_db_list_extra.php";s:4:"b6e3";s:21:"class.ux_t3lib_db.php";s:4:"9c86";s:28:"class.ux_t3lib_sqlengine.php";s:4:"fb48";s:28:"class.ux_t3lib_sqlparser.php";s:4:"b8b8";s:12:"ext_icon.gif";s:4:"1bdc";s:17:"ext_localconf.php";s:4:"c22b";s:14:"ext_tables.php";s:4:"427e";s:14:"ext_tables.sql";s:4:"1f95";s:27:"doc/class.tslib_fe.php.diff";s:4:"0083";s:14:"doc/manual.sxw";s:4:"17d4";s:45:"handlers/class.tx_dbal_handler_openoffice.php";s:4:"e939";s:43:"handlers/class.tx_dbal_handler_rawmysql.php";s:4:"ca2d";s:40:"handlers/class.tx_dbal_handler_xmldb.php";s:4:"cee9";s:14:"mod1/clear.gif";s:4:"cc11";s:13:"mod1/conf.php";s:4:"e53a";s:14:"mod1/index.php";s:4:"c623";s:18:"mod1/locallang.xml";s:4:"0b57";s:22:"mod1/locallang_mod.xml";s:4:"86ef";s:19:"mod1/moduleicon.gif";s:4:"8074";}',
	'constraints' => array(
		'depends' => array(
			'adodb' => '5.8.1-',
			'php' => '5.2.0-0.0.0',
			'typo3' => '4.3.0-4.3.99',
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

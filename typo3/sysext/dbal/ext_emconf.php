<?php

########################################################################
# Extension Manager/Repository config file for ext "dbal".
#
# Auto generated 30-12-2009 13:41
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
	'state' => 'stable',
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
	'version' => '1.0.3',
	'_md5_values_when_last_written' => 'a:31:{s:9:"ChangeLog";s:4:"97bb";s:26:"class.ux_db_list_extra.php";s:4:"7b9e";s:21:"class.ux_t3lib_db.php";s:4:"5057";s:28:"class.ux_t3lib_sqlparser.php";s:4:"1656";s:16:"ext_autoload.php";s:4:"821a";s:21:"ext_conf_template.txt";s:4:"f5cf";s:12:"ext_icon.gif";s:4:"c9ba";s:17:"ext_localconf.php";s:4:"5280";s:14:"ext_tables.php";s:4:"8414";s:14:"ext_tables.sql";s:4:"1f95";s:27:"doc/class.tslib_fe.php.diff";s:4:"0083";s:14:"doc/manual.sxw";s:4:"b022";s:45:"handlers/class.tx_dbal_handler_openoffice.php";s:4:"d6c1";s:43:"handlers/class.tx_dbal_handler_rawmysql.php";s:4:"2f1b";s:40:"handlers/class.tx_dbal_handler_xmldb.php";s:4:"e363";s:31:"lib/class.tx_dbal_sqlengine.php";s:4:"66a9";s:33:"lib/class.tx_dbal_tsparserext.php";s:4:"ce12";s:14:"mod1/clear.gif";s:4:"cc11";s:13:"mod1/conf.php";s:4:"6e63";s:14:"mod1/index.php";s:4:"6944";s:18:"mod1/locallang.xml";s:4:"0b57";s:22:"mod1/locallang_mod.xml";s:4:"86ef";s:19:"mod1/moduleicon.gif";s:4:"2b8f";s:10:"res/README";s:4:"be19";s:43:"res/postgresql/postgresql-compatibility.sql";s:4:"5299";s:22:"tests/BaseTestCase.php";s:4:"8a4a";s:26:"tests/FakeDbConnection.php";s:4:"ed15";s:29:"tests/db_general_testcase.php";s:4:"aa70";s:28:"tests/db_oracle_testcase.php";s:4:"2c73";s:36:"tests/sqlparser_general_testcase.php";s:4:"cd55";s:30:"tests/fixtures/oci8.config.php";s:4:"9ab9";}',
	'constraints' => array(
		'depends' => array(
			'adodb' => '5.10.0-',
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
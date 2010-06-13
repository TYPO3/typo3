<?php

########################################################################
# Extension Manager/Repository config file for ext "dbal".
#
# Auto generated 13-06-2010 23:22
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
	'version' => '1.1.7',
	'_md5_values_when_last_written' => 'a:42:{s:9:"ChangeLog";s:4:"cff9";s:28:"class.tx_dbal_autoloader.php";s:4:"4e1f";s:29:"class.tx_dbal_installtool.php";s:4:"9319";s:26:"class.ux_db_list_extra.php";s:4:"60d9";s:21:"class.ux_t3lib_db.php";s:4:"b8b9";s:28:"class.ux_t3lib_sqlparser.php";s:4:"feb6";s:16:"ext_autoload.php";s:4:"821a";s:21:"ext_conf_template.txt";s:4:"f5cf";s:12:"ext_icon.gif";s:4:"c9ba";s:17:"ext_localconf.php";s:4:"afdd";s:14:"ext_tables.php";s:4:"8414";s:14:"ext_tables.sql";s:4:"1f95";s:27:"doc/class.tslib_fe.php.diff";s:4:"0083";s:14:"doc/manual.sxw";s:4:"b022";s:45:"handlers/class.tx_dbal_handler_openoffice.php";s:4:"775f";s:43:"handlers/class.tx_dbal_handler_rawmysql.php";s:4:"2f1b";s:40:"handlers/class.tx_dbal_handler_xmldb.php";s:4:"e363";s:31:"lib/class.tx_dbal_sqlengine.php";s:4:"f1bb";s:33:"lib/class.tx_dbal_tsparserext.php";s:4:"862d";s:14:"mod1/clear.gif";s:4:"cc11";s:13:"mod1/conf.php";s:4:"6e63";s:14:"mod1/index.php";s:4:"4a5e";s:18:"mod1/locallang.xml";s:4:"0b57";s:22:"mod1/locallang_mod.xml";s:4:"86ef";s:19:"mod1/moduleicon.gif";s:4:"2b8f";s:10:"res/README";s:4:"be19";s:26:"res/Templates/install.html";s:4:"6a62";s:30:"res/oracle/indexed_search.diff";s:4:"ec81";s:23:"res/oracle/realurl.diff";s:4:"86da";s:25:"res/oracle/scheduler.diff";s:4:"7c06";s:27:"res/oracle/templavoila.diff";s:4:"1fd5";s:43:"res/postgresql/postgresql-compatibility.sql";s:4:"034c";s:22:"tests/BaseTestCase.php";s:4:"f736";s:26:"tests/FakeDbConnection.php";s:4:"7bab";s:23:"tests/dbGeneralTest.php";s:4:"7836";s:21:"tests/dbMssqlTest.php";s:4:"1fd9";s:22:"tests/dbOracleTest.php";s:4:"f5d6";s:26:"tests/dbPostgresqlTest.php";s:4:"fcb8";s:30:"tests/sqlParserGeneralTest.php";s:4:"311c";s:31:"tests/fixtures/mssql.config.php";s:4:"ff95";s:30:"tests/fixtures/oci8.config.php";s:4:"819e";s:36:"tests/fixtures/postgresql.config.php";s:4:"87a1";}',
	'constraints' => array(
		'depends' => array(
			'adodb' => '5.10.0-',
			'php' => '5.2.0-0.0.0',
			'typo3' => '4.4.0-4.4.99',
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
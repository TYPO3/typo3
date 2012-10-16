<?php

########################################################################
# Extension Manager/Repository config file for ext "adodb".
#
# Auto generated 16-10-2012 14:05
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'ADOdb',
	'description' => 'This extension just includes a current version of ADOdb, a database abstraction library for PHP, for further use in TYPO3',
	'category' => 'misc',
	'shy' => 0,
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => 0,
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author' => 'Xavier Perseguers',
	'author_email' => 'xavier@typo3.org',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'version' => '5.14.0',
	'_md5_values_when_last_written' => 'a:166:{s:25:"checkconnectionwizard.php";s:4:"c043";s:27:"class.tx_adodb_tceforms.php";s:4:"6a33";s:26:"datasource_flexform_ds.xml";s:4:"96fb";s:12:"ext_icon.gif";s:4:"c778";s:17:"ext_localconf.php";s:4:"62dd";s:31:"locallang_datasource_config.xlf";s:4:"8b08";s:20:"locallang_wizard.xlf";s:4:"8b08";s:33:"adodb/adodb-active-record.inc.php";s:4:"c483";s:34:"adodb/adodb-active-recordx.inc.php";s:4:"c8b6";s:26:"adodb/adodb-csvlib.inc.php";s:4:"712f";s:28:"adodb/adodb-datadict.inc.php";s:4:"8842";s:25:"adodb/adodb-error.inc.php";s:4:"8428";s:32:"adodb/adodb-errorhandler.inc.php";s:4:"0010";s:29:"adodb/adodb-errorpear.inc.php";s:4:"aafe";s:30:"adodb/adodb-exceptions.inc.php";s:4:"13e4";s:28:"adodb/adodb-iterator.inc.php";s:4:"1396";s:23:"adodb/adodb-lib.inc.php";s:4:"bb8e";s:32:"adodb/adodb-memcache.lib.inc.php";s:4:"2a9d";s:25:"adodb/adodb-pager.inc.php";s:4:"abaa";s:24:"adodb/adodb-pear.inc.php";s:4:"5f9a";s:24:"adodb/adodb-perf.inc.php";s:4:"247e";s:24:"adodb/adodb-php4.inc.php";s:4:"2364";s:24:"adodb/adodb-time.inc.php";s:4:"0388";s:29:"adodb/adodb-xmlschema.inc.php";s:4:"6d73";s:31:"adodb/adodb-xmlschema03.inc.php";s:4:"2d3c";s:19:"adodb/adodb.inc.php";s:4:"a699";s:17:"adodb/license.txt";s:4:"af93";s:24:"adodb/pivottable.inc.php";s:4:"5e01";s:16:"adodb/readme.txt";s:4:"a2d2";s:22:"adodb/rsfilter.inc.php";s:4:"dd59";s:16:"adodb/server.php";s:4:"72c7";s:22:"adodb/toexport.inc.php";s:4:"bdd9";s:20:"adodb/tohtml.inc.php";s:4:"5545";s:19:"adodb/xmlschema.dtd";s:4:"26f3";s:21:"adodb/xmlschema03.dtd";s:4:"fa85";s:30:"adodb/contrib/toxmlrpc.inc.php";s:4:"b8a4";s:38:"adodb/datadict/datadict-access.inc.php";s:4:"ebf0";s:35:"adodb/datadict/datadict-db2.inc.php";s:4:"b1a0";s:40:"adodb/datadict/datadict-firebird.inc.php";s:4:"fd87";s:39:"adodb/datadict/datadict-generic.inc.php";s:4:"d11a";s:37:"adodb/datadict/datadict-ibase.inc.php";s:4:"40f3";s:40:"adodb/datadict/datadict-informix.inc.php";s:4:"a252";s:37:"adodb/datadict/datadict-mssql.inc.php";s:4:"7d84";s:43:"adodb/datadict/datadict-mssqlnative.inc.php";s:4:"88b5";s:37:"adodb/datadict/datadict-mysql.inc.php";s:4:"6e8a";s:36:"adodb/datadict/datadict-oci8.inc.php";s:4:"0b89";s:40:"adodb/datadict/datadict-postgres.inc.php";s:4:"fd55";s:37:"adodb/datadict/datadict-sapdb.inc.php";s:4:"bfe9";s:38:"adodb/datadict/datadict-sqlite.inc.php";s:4:"d18d";s:38:"adodb/datadict/datadict-sybase.inc.php";s:4:"ab7e";s:34:"adodb/drivers/adodb-access.inc.php";s:4:"c67c";s:31:"adodb/drivers/adodb-ado.inc.php";s:4:"3cdb";s:32:"adodb/drivers/adodb-ado5.inc.php";s:4:"6684";s:38:"adodb/drivers/adodb-ado_access.inc.php";s:4:"7eba";s:37:"adodb/drivers/adodb-ado_mssql.inc.php";s:4:"7142";s:31:"adodb/drivers/adodb-ads.inc.php";s:4:"c7c7";s:41:"adodb/drivers/adodb-borland_ibase.inc.php";s:4:"a79f";s:31:"adodb/drivers/adodb-csv.inc.php";s:4:"d361";s:31:"adodb/drivers/adodb-db2.inc.php";s:4:"400c";s:34:"adodb/drivers/adodb-db2oci.inc.php";s:4:"2d6f";s:34:"adodb/drivers/adodb-db2ora.inc.php";s:4:"6a88";s:33:"adodb/drivers/adodb-fbsql.inc.php";s:4:"f424";s:36:"adodb/drivers/adodb-firebird.inc.php";s:4:"8de0";s:33:"adodb/drivers/adodb-ibase.inc.php";s:4:"9988";s:36:"adodb/drivers/adodb-informix.inc.php";s:4:"dc03";s:38:"adodb/drivers/adodb-informix72.inc.php";s:4:"8708";s:32:"adodb/drivers/adodb-ldap.inc.php";s:4:"781c";s:33:"adodb/drivers/adodb-mssql.inc.php";s:4:"a5cd";s:35:"adodb/drivers/adodb-mssql_n.inc.php";s:4:"8bbe";s:39:"adodb/drivers/adodb-mssqlnative.inc.php";s:4:"68cd";s:35:"adodb/drivers/adodb-mssqlpo.inc.php";s:4:"7355";s:33:"adodb/drivers/adodb-mysql.inc.php";s:4:"a247";s:34:"adodb/drivers/adodb-mysqli.inc.php";s:4:"f42c";s:35:"adodb/drivers/adodb-mysqlpo.inc.php";s:4:"0cdf";s:34:"adodb/drivers/adodb-mysqlt.inc.php";s:4:"44d9";s:35:"adodb/drivers/adodb-netezza.inc.php";s:4:"badd";s:32:"adodb/drivers/adodb-oci8.inc.php";s:4:"cb43";s:34:"adodb/drivers/adodb-oci805.inc.php";s:4:"9772";s:34:"adodb/drivers/adodb-oci8po.inc.php";s:4:"ca0a";s:32:"adodb/drivers/adodb-odbc.inc.php";s:4:"f642";s:36:"adodb/drivers/adodb-odbc_db2.inc.php";s:4:"67aa";s:38:"adodb/drivers/adodb-odbc_mssql.inc.php";s:4:"c631";s:39:"adodb/drivers/adodb-odbc_oracle.inc.php";s:4:"3933";s:33:"adodb/drivers/adodb-odbtp.inc.php";s:4:"c068";s:41:"adodb/drivers/adodb-odbtp_unicode.inc.php";s:4:"1134";s:34:"adodb/drivers/adodb-oracle.inc.php";s:4:"59e6";s:31:"adodb/drivers/adodb-pdo.inc.php";s:4:"e689";s:37:"adodb/drivers/adodb-pdo_mssql.inc.php";s:4:"7951";s:37:"adodb/drivers/adodb-pdo_mysql.inc.php";s:4:"bb8b";s:35:"adodb/drivers/adodb-pdo_oci.inc.php";s:4:"0844";s:37:"adodb/drivers/adodb-pdo_pgsql.inc.php";s:4:"02ea";s:38:"adodb/drivers/adodb-pdo_sqlite.inc.php";s:4:"efa0";s:36:"adodb/drivers/adodb-postgres.inc.php";s:4:"3281";s:38:"adodb/drivers/adodb-postgres64.inc.php";s:4:"f798";s:37:"adodb/drivers/adodb-postgres7.inc.php";s:4:"725c";s:37:"adodb/drivers/adodb-postgres8.inc.php";s:4:"82d7";s:33:"adodb/drivers/adodb-proxy.inc.php";s:4:"e55f";s:33:"adodb/drivers/adodb-sapdb.inc.php";s:4:"7dce";s:39:"adodb/drivers/adodb-sqlanywhere.inc.php";s:4:"2e91";s:34:"adodb/drivers/adodb-sqlite.inc.php";s:4:"f7a8";s:35:"adodb/drivers/adodb-sqlite3.inc.php";s:4:"b434";s:36:"adodb/drivers/adodb-sqlitepo.inc.php";s:4:"d691";s:34:"adodb/drivers/adodb-sybase.inc.php";s:4:"96f9";s:38:"adodb/drivers/adodb-sybase_ase.inc.php";s:4:"b1bf";s:31:"adodb/drivers/adodb-vfp.inc.php";s:4:"b37d";s:27:"adodb/lang/adodb-ar.inc.php";s:4:"5660";s:27:"adodb/lang/adodb-bg.inc.php";s:4:"37b0";s:31:"adodb/lang/adodb-bgutf8.inc.php";s:4:"08ac";s:27:"adodb/lang/adodb-ca.inc.php";s:4:"b903";s:27:"adodb/lang/adodb-cn.inc.php";s:4:"c8e1";s:27:"adodb/lang/adodb-cz.inc.php";s:4:"0339";s:27:"adodb/lang/adodb-da.inc.php";s:4:"2ea2";s:27:"adodb/lang/adodb-de.inc.php";s:4:"6e6e";s:27:"adodb/lang/adodb-en.inc.php";s:4:"c542";s:27:"adodb/lang/adodb-es.inc.php";s:4:"de07";s:34:"adodb/lang/adodb-esperanto.inc.php";s:4:"32b9";s:27:"adodb/lang/adodb-fa.inc.php";s:4:"aa96";s:27:"adodb/lang/adodb-fr.inc.php";s:4:"237c";s:27:"adodb/lang/adodb-hu.inc.php";s:4:"f308";s:27:"adodb/lang/adodb-it.inc.php";s:4:"15e2";s:27:"adodb/lang/adodb-nl.inc.php";s:4:"ed3d";s:27:"adodb/lang/adodb-pl.inc.php";s:4:"8a53";s:30:"adodb/lang/adodb-pt-br.inc.php";s:4:"14cc";s:27:"adodb/lang/adodb-ro.inc.php";s:4:"7105";s:31:"adodb/lang/adodb-ru1251.inc.php";s:4:"43c8";s:27:"adodb/lang/adodb-sv.inc.php";s:4:"2e5a";s:31:"adodb/lang/adodb-uk1251.inc.php";s:4:"822a";s:27:"adodb/lang/adodb_th.inc.php";s:4:"201d";s:26:"adodb/pear/readme.Auth.txt";s:4:"4970";s:35:"adodb/pear/Auth/Container/ADOdb.php";s:4:"ebd8";s:27:"adodb/perf/perf-db2.inc.php";s:4:"83d7";s:32:"adodb/perf/perf-informix.inc.php";s:4:"1b0f";s:29:"adodb/perf/perf-mssql.inc.php";s:4:"f7f6";s:35:"adodb/perf/perf-mssqlnative.inc.php";s:4:"259c";s:29:"adodb/perf/perf-mysql.inc.php";s:4:"f4ec";s:28:"adodb/perf/perf-oci8.inc.php";s:4:"063a";s:32:"adodb/perf/perf-postgres.inc.php";s:4:"743e";s:38:"adodb/session/adodb-compress-bzip2.php";s:4:"688c";s:37:"adodb/session/adodb-compress-gzip.php";s:4:"d495";s:36:"adodb/session/adodb-cryptsession.php";s:4:"98ba";s:37:"adodb/session/adodb-cryptsession2.php";s:4:"2320";s:38:"adodb/session/adodb-encrypt-mcrypt.php";s:4:"e5ec";s:35:"adodb/session/adodb-encrypt-md5.php";s:4:"7443";s:38:"adodb/session/adodb-encrypt-secret.php";s:4:"98e5";s:36:"adodb/session/adodb-encrypt-sha1.php";s:4:"4c0b";s:28:"adodb/session/adodb-sess.txt";s:4:"a25a";s:36:"adodb/session/adodb-session-clob.php";s:4:"17c5";s:37:"adodb/session/adodb-session-clob2.php";s:4:"af17";s:31:"adodb/session/adodb-session.php";s:4:"ff84";s:32:"adodb/session/adodb-session2.php";s:4:"b4d3";s:38:"adodb/session/adodb-sessions.mysql.sql";s:4:"42fe";s:44:"adodb/session/adodb-sessions.oracle.clob.sql";s:4:"3c64";s:39:"adodb/session/adodb-sessions.oracle.sql";s:4:"08d0";s:27:"adodb/session/crypt.inc.php";s:4:"0559";s:32:"adodb/session/session_schema.xml";s:4:"6443";s:33:"adodb/session/session_schema2.xml";s:4:"3409";s:29:"adodb/xsl/convert-0.1-0.2.xsl";s:4:"29d9";s:29:"adodb/xsl/convert-0.1-0.3.xsl";s:4:"6aad";s:29:"adodb/xsl/convert-0.2-0.1.xsl";s:4:"5d27";s:29:"adodb/xsl/convert-0.2-0.3.xsl";s:4:"4098";s:24:"adodb/xsl/remove-0.2.xsl";s:4:"0b2b";s:24:"adodb/xsl/remove-0.3.xsl";s:4:"678d";s:18:"doc/510.DBAL.patch";s:4:"e88d";s:25:"doc/mssql-error-fix.patch";s:4:"91bf";s:10:"doc/README";s:4:"d375";s:23:"res/checkconnection.gif";s:4:"1760";}',
	'constraints' => array(
		'depends' => array(
			'php' => '5.3.0-0.0.0',
			'typo3' => '4.7.0-0.0.0',
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
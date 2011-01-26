<?php

########################################################################
# Extension Manager/Repository config file for ext "adodb".
#
# Auto generated 26-01-2011 20:08
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
	'author_email' => 'typo3@perseguers.ch',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'version' => '5.11.0',
	'_md5_values_when_last_written' => 'a:163:{s:25:"checkconnectionwizard.php";s:4:"b738";s:27:"class.tx_adodb_tceforms.php";s:4:"6a33";s:26:"datasource_flexform_ds.xml";s:4:"96fb";s:12:"ext_icon.gif";s:4:"c778";s:17:"ext_localconf.php";s:4:"62dd";s:31:"locallang_datasource_config.xml";s:4:"df55";s:20:"locallang_wizard.xml";s:4:"dca8";s:33:"adodb/adodb-active-record.inc.php";s:4:"64e5";s:34:"adodb/adodb-active-recordx.inc.php";s:4:"c6c9";s:26:"adodb/adodb-csvlib.inc.php";s:4:"db96";s:28:"adodb/adodb-datadict.inc.php";s:4:"ab5c";s:25:"adodb/adodb-error.inc.php";s:4:"e76c";s:32:"adodb/adodb-errorhandler.inc.php";s:4:"5add";s:29:"adodb/adodb-errorpear.inc.php";s:4:"7ab4";s:30:"adodb/adodb-exceptions.inc.php";s:4:"526b";s:28:"adodb/adodb-iterator.inc.php";s:4:"3604";s:23:"adodb/adodb-lib.inc.php";s:4:"7e31";s:32:"adodb/adodb-memcache.lib.inc.php";s:4:"1b01";s:25:"adodb/adodb-pager.inc.php";s:4:"5382";s:24:"adodb/adodb-pear.inc.php";s:4:"2f1f";s:24:"adodb/adodb-perf.inc.php";s:4:"46e2";s:24:"adodb/adodb-php4.inc.php";s:4:"e5ce";s:24:"adodb/adodb-time.inc.php";s:4:"8762";s:29:"adodb/adodb-xmlschema.inc.php";s:4:"ff89";s:31:"adodb/adodb-xmlschema03.inc.php";s:4:"0a18";s:19:"adodb/adodb.inc.php";s:4:"3994";s:17:"adodb/license.txt";s:4:"af93";s:24:"adodb/pivottable.inc.php";s:4:"a423";s:16:"adodb/readme.txt";s:4:"a2d2";s:22:"adodb/rsfilter.inc.php";s:4:"cd4e";s:16:"adodb/server.php";s:4:"ebb5";s:22:"adodb/toexport.inc.php";s:4:"3514";s:20:"adodb/tohtml.inc.php";s:4:"f696";s:19:"adodb/xmlschema.dtd";s:4:"26f3";s:21:"adodb/xmlschema03.dtd";s:4:"fa85";s:30:"adodb/contrib/toxmlrpc.inc.php";s:4:"b8a4";s:38:"adodb/datadict/datadict-access.inc.php";s:4:"38fe";s:35:"adodb/datadict/datadict-db2.inc.php";s:4:"58f4";s:40:"adodb/datadict/datadict-firebird.inc.php";s:4:"c97c";s:39:"adodb/datadict/datadict-generic.inc.php";s:4:"3505";s:37:"adodb/datadict/datadict-ibase.inc.php";s:4:"3f44";s:40:"adodb/datadict/datadict-informix.inc.php";s:4:"43ba";s:37:"adodb/datadict/datadict-mssql.inc.php";s:4:"39d7";s:43:"adodb/datadict/datadict-mssqlnative.inc.php";s:4:"58da";s:37:"adodb/datadict/datadict-mysql.inc.php";s:4:"fb01";s:36:"adodb/datadict/datadict-oci8.inc.php";s:4:"1e19";s:40:"adodb/datadict/datadict-postgres.inc.php";s:4:"583a";s:37:"adodb/datadict/datadict-sapdb.inc.php";s:4:"d6c7";s:38:"adodb/datadict/datadict-sqlite.inc.php";s:4:"f4df";s:38:"adodb/datadict/datadict-sybase.inc.php";s:4:"c944";s:34:"adodb/drivers/adodb-access.inc.php";s:4:"e812";s:31:"adodb/drivers/adodb-ado.inc.php";s:4:"fd86";s:32:"adodb/drivers/adodb-ado5.inc.php";s:4:"c758";s:38:"adodb/drivers/adodb-ado_access.inc.php";s:4:"8605";s:37:"adodb/drivers/adodb-ado_mssql.inc.php";s:4:"c272";s:31:"adodb/drivers/adodb-ads.inc.php";s:4:"c7c7";s:41:"adodb/drivers/adodb-borland_ibase.inc.php";s:4:"a9fc";s:31:"adodb/drivers/adodb-csv.inc.php";s:4:"82b3";s:31:"adodb/drivers/adodb-db2.inc.php";s:4:"febe";s:34:"adodb/drivers/adodb-db2oci.inc.php";s:4:"db0d";s:34:"adodb/drivers/adodb-db2ora.inc.php";s:4:"dd44";s:33:"adodb/drivers/adodb-fbsql.inc.php";s:4:"8587";s:36:"adodb/drivers/adodb-firebird.inc.php";s:4:"b89e";s:33:"adodb/drivers/adodb-ibase.inc.php";s:4:"daf7";s:36:"adodb/drivers/adodb-informix.inc.php";s:4:"5beb";s:38:"adodb/drivers/adodb-informix72.inc.php";s:4:"1595";s:32:"adodb/drivers/adodb-ldap.inc.php";s:4:"830d";s:33:"adodb/drivers/adodb-mssql.inc.php";s:4:"c4a2";s:35:"adodb/drivers/adodb-mssql_n.inc.php";s:4:"3c9e";s:39:"adodb/drivers/adodb-mssqlnative.inc.php";s:4:"8d39";s:35:"adodb/drivers/adodb-mssqlpo.inc.php";s:4:"5cd8";s:33:"adodb/drivers/adodb-mysql.inc.php";s:4:"c168";s:34:"adodb/drivers/adodb-mysqli.inc.php";s:4:"90d6";s:35:"adodb/drivers/adodb-mysqlpo.inc.php";s:4:"c0e7";s:34:"adodb/drivers/adodb-mysqlt.inc.php";s:4:"85c8";s:35:"adodb/drivers/adodb-netezza.inc.php";s:4:"f9c8";s:32:"adodb/drivers/adodb-oci8.inc.php";s:4:"bd00";s:34:"adodb/drivers/adodb-oci805.inc.php";s:4:"39e8";s:34:"adodb/drivers/adodb-oci8po.inc.php";s:4:"0b13";s:32:"adodb/drivers/adodb-odbc.inc.php";s:4:"c837";s:36:"adodb/drivers/adodb-odbc_db2.inc.php";s:4:"d6bd";s:38:"adodb/drivers/adodb-odbc_mssql.inc.php";s:4:"9d2c";s:39:"adodb/drivers/adodb-odbc_oracle.inc.php";s:4:"811d";s:33:"adodb/drivers/adodb-odbtp.inc.php";s:4:"cd66";s:41:"adodb/drivers/adodb-odbtp_unicode.inc.php";s:4:"e5c6";s:34:"adodb/drivers/adodb-oracle.inc.php";s:4:"3fe4";s:31:"adodb/drivers/adodb-pdo.inc.php";s:4:"b6eb";s:37:"adodb/drivers/adodb-pdo_mssql.inc.php";s:4:"ec5a";s:37:"adodb/drivers/adodb-pdo_mysql.inc.php";s:4:"b29d";s:35:"adodb/drivers/adodb-pdo_oci.inc.php";s:4:"76c7";s:37:"adodb/drivers/adodb-pdo_pgsql.inc.php";s:4:"1f04";s:38:"adodb/drivers/adodb-pdo_sqlite.inc.php";s:4:"8203";s:36:"adodb/drivers/adodb-postgres.inc.php";s:4:"4229";s:38:"adodb/drivers/adodb-postgres64.inc.php";s:4:"67ff";s:37:"adodb/drivers/adodb-postgres7.inc.php";s:4:"0017";s:37:"adodb/drivers/adodb-postgres8.inc.php";s:4:"0d1f";s:33:"adodb/drivers/adodb-proxy.inc.php";s:4:"3505";s:33:"adodb/drivers/adodb-sapdb.inc.php";s:4:"942c";s:39:"adodb/drivers/adodb-sqlanywhere.inc.php";s:4:"5386";s:34:"adodb/drivers/adodb-sqlite.inc.php";s:4:"167b";s:36:"adodb/drivers/adodb-sqlitepo.inc.php";s:4:"17f6";s:34:"adodb/drivers/adodb-sybase.inc.php";s:4:"1547";s:38:"adodb/drivers/adodb-sybase_ase.inc.php";s:4:"689f";s:31:"adodb/drivers/adodb-vfp.inc.php";s:4:"2c6e";s:27:"adodb/lang/adodb-ar.inc.php";s:4:"5660";s:27:"adodb/lang/adodb-bg.inc.php";s:4:"37b0";s:31:"adodb/lang/adodb-bgutf8.inc.php";s:4:"08ac";s:27:"adodb/lang/adodb-ca.inc.php";s:4:"b903";s:27:"adodb/lang/adodb-cn.inc.php";s:4:"c8e1";s:27:"adodb/lang/adodb-cz.inc.php";s:4:"0339";s:27:"adodb/lang/adodb-da.inc.php";s:4:"2ea2";s:27:"adodb/lang/adodb-de.inc.php";s:4:"6e6e";s:27:"adodb/lang/adodb-en.inc.php";s:4:"c542";s:27:"adodb/lang/adodb-es.inc.php";s:4:"de07";s:34:"adodb/lang/adodb-esperanto.inc.php";s:4:"9ca3";s:27:"adodb/lang/adodb-fa.inc.php";s:4:"aa96";s:27:"adodb/lang/adodb-fr.inc.php";s:4:"237c";s:27:"adodb/lang/adodb-hu.inc.php";s:4:"f308";s:27:"adodb/lang/adodb-it.inc.php";s:4:"481e";s:27:"adodb/lang/adodb-nl.inc.php";s:4:"ed3d";s:27:"adodb/lang/adodb-pl.inc.php";s:4:"8a53";s:30:"adodb/lang/adodb-pt-br.inc.php";s:4:"5130";s:27:"adodb/lang/adodb-ro.inc.php";s:4:"7105";s:31:"adodb/lang/adodb-ru1251.inc.php";s:4:"43c8";s:27:"adodb/lang/adodb-sv.inc.php";s:4:"2e5a";s:31:"adodb/lang/adodb-uk1251.inc.php";s:4:"822a";s:27:"adodb/lang/adodb_th.inc.php";s:4:"201d";s:27:"adodb/perf/perf-db2.inc.php";s:4:"6827";s:32:"adodb/perf/perf-informix.inc.php";s:4:"8e47";s:29:"adodb/perf/perf-mssql.inc.php";s:4:"57b7";s:35:"adodb/perf/perf-mssqlnative.inc.php";s:4:"01f6";s:29:"adodb/perf/perf-mysql.inc.php";s:4:"1241";s:28:"adodb/perf/perf-oci8.inc.php";s:4:"e306";s:32:"adodb/perf/perf-postgres.inc.php";s:4:"ce92";s:38:"adodb/session/adodb-compress-bzip2.php";s:4:"4c97";s:37:"adodb/session/adodb-compress-gzip.php";s:4:"a777";s:36:"adodb/session/adodb-cryptsession.php";s:4:"f246";s:37:"adodb/session/adodb-cryptsession2.php";s:4:"14db";s:38:"adodb/session/adodb-encrypt-mcrypt.php";s:4:"48dd";s:35:"adodb/session/adodb-encrypt-md5.php";s:4:"b153";s:38:"adodb/session/adodb-encrypt-secret.php";s:4:"68b8";s:36:"adodb/session/adodb-encrypt-sha1.php";s:4:"4c0b";s:28:"adodb/session/adodb-sess.txt";s:4:"a25a";s:36:"adodb/session/adodb-session-clob.php";s:4:"414f";s:37:"adodb/session/adodb-session-clob2.php";s:4:"703d";s:31:"adodb/session/adodb-session.php";s:4:"fa9c";s:32:"adodb/session/adodb-session2.php";s:4:"4067";s:38:"adodb/session/adodb-sessions.mysql.sql";s:4:"42fe";s:44:"adodb/session/adodb-sessions.oracle.clob.sql";s:4:"3c64";s:39:"adodb/session/adodb-sessions.oracle.sql";s:4:"08d0";s:27:"adodb/session/crypt.inc.php";s:4:"4dad";s:32:"adodb/session/session_schema.xml";s:4:"6443";s:33:"adodb/session/session_schema2.xml";s:4:"3409";s:29:"adodb/xsl/convert-0.1-0.2.xsl";s:4:"29d9";s:29:"adodb/xsl/convert-0.1-0.3.xsl";s:4:"6aad";s:29:"adodb/xsl/convert-0.2-0.1.xsl";s:4:"5d27";s:29:"adodb/xsl/convert-0.2-0.3.xsl";s:4:"4098";s:24:"adodb/xsl/remove-0.2.xsl";s:4:"0b2b";s:24:"adodb/xsl/remove-0.3.xsl";s:4:"678d";s:18:"doc/510.DBAL.patch";s:4:"e88d";s:10:"doc/README";s:4:"d375";s:25:"doc/mssql-error-fix.patch";s:4:"91bf";s:23:"res/checkconnection.gif";s:4:"1760";}',
	'constraints' => array(
		'depends' => array(
			'php' => '5.2.0-0.0.0',
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
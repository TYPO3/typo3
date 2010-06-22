<?php

########################################################################
# Extension Manager/Repository config file for ext "adodb".
#
# Auto generated 22-06-2010 13:08
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
	'version' => '5.10.0',
	'_md5_values_when_last_written' => 'a:124:{s:25:"checkconnectionwizard.php";s:4:"e9d7";s:27:"class.tx_adodb_tceforms.php";s:4:"164e";s:26:"datasource_flexform_ds.xml";s:4:"96fb";s:12:"ext_icon.gif";s:4:"c778";s:17:"ext_localconf.php";s:4:"62dd";s:31:"locallang_datasource_config.xml";s:4:"df55";s:20:"locallang_wizard.xml";s:4:"dca8";s:33:"adodb/adodb-active-record.inc.php";s:4:"6a59";s:34:"adodb/adodb-active-recordx.inc.php";s:4:"f472";s:26:"adodb/adodb-csvlib.inc.php";s:4:"ce7e";s:28:"adodb/adodb-datadict.inc.php";s:4:"764f";s:25:"adodb/adodb-error.inc.php";s:4:"2106";s:32:"adodb/adodb-errorhandler.inc.php";s:4:"1755";s:29:"adodb/adodb-errorpear.inc.php";s:4:"e6d9";s:30:"adodb/adodb-exceptions.inc.php";s:4:"ea57";s:28:"adodb/adodb-iterator.inc.php";s:4:"813b";s:23:"adodb/adodb-lib.inc.php";s:4:"f968";s:32:"adodb/adodb-memcache.lib.inc.php";s:4:"a3dc";s:25:"adodb/adodb-pager.inc.php";s:4:"63e0";s:24:"adodb/adodb-pear.inc.php";s:4:"f8a9";s:24:"adodb/adodb-time.inc.php";s:4:"36dc";s:19:"adodb/adodb.inc.php";s:4:"254a";s:17:"adodb/license.txt";s:4:"af93";s:16:"adodb/readme.txt";s:4:"a2d2";s:30:"adodb/contrib/toxmlrpc.inc.php";s:4:"aa3f";s:38:"adodb/datadict/datadict-access.inc.php";s:4:"3ce0";s:35:"adodb/datadict/datadict-db2.inc.php";s:4:"da98";s:40:"adodb/datadict/datadict-firebird.inc.php";s:4:"0d09";s:39:"adodb/datadict/datadict-generic.inc.php";s:4:"598e";s:37:"adodb/datadict/datadict-ibase.inc.php";s:4:"e9bf";s:40:"adodb/datadict/datadict-informix.inc.php";s:4:"8ca9";s:37:"adodb/datadict/datadict-mssql.inc.php";s:4:"a75e";s:43:"adodb/datadict/datadict-mssqlnative.inc.php";s:4:"8ae8";s:37:"adodb/datadict/datadict-mysql.inc.php";s:4:"b443";s:36:"adodb/datadict/datadict-oci8.inc.php";s:4:"7501";s:40:"adodb/datadict/datadict-postgres.inc.php";s:4:"bfa3";s:37:"adodb/datadict/datadict-sapdb.inc.php";s:4:"e624";s:38:"adodb/datadict/datadict-sybase.inc.php";s:4:"fa38";s:34:"adodb/drivers/adodb-access.inc.php";s:4:"e4b4";s:31:"adodb/drivers/adodb-ado.inc.php";s:4:"d390";s:32:"adodb/drivers/adodb-ado5.inc.php";s:4:"e905";s:38:"adodb/drivers/adodb-ado_access.inc.php";s:4:"51d3";s:37:"adodb/drivers/adodb-ado_mssql.inc.php";s:4:"220d";s:31:"adodb/drivers/adodb-ads.inc.php";s:4:"c7c7";s:41:"adodb/drivers/adodb-borland_ibase.inc.php";s:4:"4800";s:31:"adodb/drivers/adodb-csv.inc.php";s:4:"68b0";s:31:"adodb/drivers/adodb-db2.inc.php";s:4:"c7be";s:34:"adodb/drivers/adodb-db2oci.inc.php";s:4:"f215";s:33:"adodb/drivers/adodb-fbsql.inc.php";s:4:"d2e7";s:36:"adodb/drivers/adodb-firebird.inc.php";s:4:"9c66";s:33:"adodb/drivers/adodb-ibase.inc.php";s:4:"d667";s:36:"adodb/drivers/adodb-informix.inc.php";s:4:"e4cf";s:38:"adodb/drivers/adodb-informix72.inc.php";s:4:"1917";s:32:"adodb/drivers/adodb-ldap.inc.php";s:4:"2419";s:33:"adodb/drivers/adodb-mssql.inc.php";s:4:"102b";s:35:"adodb/drivers/adodb-mssql_n.inc.php";s:4:"4a68";s:39:"adodb/drivers/adodb-mssqlnative.inc.php";s:4:"35ad";s:35:"adodb/drivers/adodb-mssqlpo.inc.php";s:4:"da12";s:33:"adodb/drivers/adodb-mysql.inc.php";s:4:"36ed";s:34:"adodb/drivers/adodb-mysqli.inc.php";s:4:"1f45";s:35:"adodb/drivers/adodb-mysqlpo.inc.php";s:4:"ed80";s:34:"adodb/drivers/adodb-mysqlt.inc.php";s:4:"ba75";s:35:"adodb/drivers/adodb-netezza.inc.php";s:4:"e2dc";s:32:"adodb/drivers/adodb-oci8.inc.php";s:4:"724f";s:34:"adodb/drivers/adodb-oci805.inc.php";s:4:"2d0a";s:34:"adodb/drivers/adodb-oci8po.inc.php";s:4:"9584";s:32:"adodb/drivers/adodb-odbc.inc.php";s:4:"1c5f";s:36:"adodb/drivers/adodb-odbc_db2.inc.php";s:4:"87e3";s:38:"adodb/drivers/adodb-odbc_mssql.inc.php";s:4:"d9f0";s:39:"adodb/drivers/adodb-odbc_oracle.inc.php";s:4:"6c9e";s:33:"adodb/drivers/adodb-odbtp.inc.php";s:4:"918f";s:41:"adodb/drivers/adodb-odbtp_unicode.inc.php";s:4:"6295";s:34:"adodb/drivers/adodb-oracle.inc.php";s:4:"07d2";s:31:"adodb/drivers/adodb-pdo.inc.php";s:4:"f5ad";s:37:"adodb/drivers/adodb-pdo_mssql.inc.php";s:4:"3268";s:37:"adodb/drivers/adodb-pdo_mysql.inc.php";s:4:"c715";s:35:"adodb/drivers/adodb-pdo_oci.inc.php";s:4:"ef31";s:37:"adodb/drivers/adodb-pdo_pgsql.inc.php";s:4:"7e1e";s:38:"adodb/drivers/adodb-pdo_sqlite.inc.php";s:4:"2782";s:36:"adodb/drivers/adodb-postgres.inc.php";s:4:"90f9";s:38:"adodb/drivers/adodb-postgres64.inc.php";s:4:"e6ff";s:37:"adodb/drivers/adodb-postgres7.inc.php";s:4:"12e3";s:37:"adodb/drivers/adodb-postgres8.inc.php";s:4:"b516";s:33:"adodb/drivers/adodb-proxy.inc.php";s:4:"4e25";s:33:"adodb/drivers/adodb-sapdb.inc.php";s:4:"04f1";s:39:"adodb/drivers/adodb-sqlanywhere.inc.php";s:4:"cb31";s:34:"adodb/drivers/adodb-sqlite.inc.php";s:4:"abba";s:36:"adodb/drivers/adodb-sqlitepo.inc.php";s:4:"d5ee";s:34:"adodb/drivers/adodb-sybase.inc.php";s:4:"1dc6";s:38:"adodb/drivers/adodb-sybase_ase.inc.php";s:4:"5dae";s:31:"adodb/drivers/adodb-vfp.inc.php";s:4:"3c4d";s:27:"adodb/lang/adodb-ar.inc.php";s:4:"5660";s:27:"adodb/lang/adodb-bg.inc.php";s:4:"37b0";s:31:"adodb/lang/adodb-bgutf8.inc.php";s:4:"08ac";s:27:"adodb/lang/adodb-ca.inc.php";s:4:"b903";s:27:"adodb/lang/adodb-cn.inc.php";s:4:"c8e1";s:27:"adodb/lang/adodb-cz.inc.php";s:4:"0339";s:27:"adodb/lang/adodb-da.inc.php";s:4:"2ea2";s:27:"adodb/lang/adodb-de.inc.php";s:4:"6e6e";s:27:"adodb/lang/adodb-en.inc.php";s:4:"c542";s:27:"adodb/lang/adodb-es.inc.php";s:4:"de07";s:34:"adodb/lang/adodb-esperanto.inc.php";s:4:"32b9";s:27:"adodb/lang/adodb-fa.inc.php";s:4:"aa96";s:27:"adodb/lang/adodb-fr.inc.php";s:4:"237c";s:27:"adodb/lang/adodb-hu.inc.php";s:4:"f308";s:27:"adodb/lang/adodb-it.inc.php";s:4:"ae50";s:27:"adodb/lang/adodb-nl.inc.php";s:4:"ed3d";s:27:"adodb/lang/adodb-pl.inc.php";s:4:"8a53";s:30:"adodb/lang/adodb-pt-br.inc.php";s:4:"14cc";s:27:"adodb/lang/adodb-ro.inc.php";s:4:"7105";s:31:"adodb/lang/adodb-ru1251.inc.php";s:4:"43c8";s:27:"adodb/lang/adodb-sv.inc.php";s:4:"2e5a";s:31:"adodb/lang/adodb-uk1251.inc.php";s:4:"822a";s:27:"adodb/lang/adodb_th.inc.php";s:4:"201d";s:29:"adodb/xsl/convert-0.1-0.2.xsl";s:4:"29d9";s:29:"adodb/xsl/convert-0.1-0.3.xsl";s:4:"6aad";s:29:"adodb/xsl/convert-0.2-0.1.xsl";s:4:"5d27";s:29:"adodb/xsl/convert-0.2-0.3.xsl";s:4:"4098";s:24:"adodb/xsl/remove-0.2.xsl";s:4:"0b2b";s:24:"adodb/xsl/remove-0.3.xsl";s:4:"678d";s:18:"doc/510.DBAL.patch";s:4:"e88d";s:10:"doc/README";s:4:"d375";s:25:"doc/mssql-error-fix.patch";s:4:"91bf";s:23:"res/checkconnection.gif";s:4:"1760";}',
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
	'suggests' => array(
	),
);

?>
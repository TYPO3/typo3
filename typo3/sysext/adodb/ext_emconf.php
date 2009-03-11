<?php

########################################################################
# Extension Manager/Repository config file for ext: "adodb"
#
# Auto generated 11-03-2009 19:11
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
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
	'author' => 'Karsten Dambekalns',
	'author_email' => 'karsten@typo3.org',
	'author_company' => 'TYPO3 Association',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'version' => '4.94.0',
	'_md5_values_when_last_written' => 'a:127:{s:25:"checkconnectionwizard.php";s:4:"5d13";s:27:"class.tx_adodb_tceforms.php";s:4:"2dd5";s:26:"datasource_flexform_ds.xml";s:4:"96fb";s:12:"ext_icon.gif";s:4:"c778";s:17:"ext_localconf.php";s:4:"52ad";s:31:"locallang_datasource_config.xml";s:4:"df55";s:20:"locallang_wizard.xml";s:4:"dca8";s:33:"adodb/adodb-active-record.inc.php";s:4:"f467";s:26:"adodb/adodb-csvlib.inc.php";s:4:"64b4";s:28:"adodb/adodb-datadict.inc.php";s:4:"ec0b";s:25:"adodb/adodb-error.inc.php";s:4:"48fd";s:32:"adodb/adodb-errorhandler.inc.php";s:4:"de51";s:29:"adodb/adodb-errorpear.inc.php";s:4:"21c0";s:30:"adodb/adodb-exceptions.inc.php";s:4:"3990";s:28:"adodb/adodb-iterator.inc.php";s:4:"5d8e";s:23:"adodb/adodb-lib.inc.php";s:4:"311b";s:32:"adodb/adodb-memcache.lib.inc.php";s:4:"d34c";s:25:"adodb/adodb-pager.inc.php";s:4:"d170";s:24:"adodb/adodb-pear.inc.php";s:4:"eb28";s:24:"adodb/adodb-perf.inc.php";s:4:"839a";s:24:"adodb/adodb-php4.inc.php";s:4:"a759";s:24:"adodb/adodb-time.inc.php";s:4:"896a";s:29:"adodb/adodb-xmlschema.inc.php";s:4:"7668";s:31:"adodb/adodb-xmlschema03.inc.php";s:4:"50f5";s:19:"adodb/adodb.inc.php";s:4:"dd26";s:17:"adodb/license.txt";s:4:"af93";s:24:"adodb/pivottable.inc.php";s:4:"b05b";s:16:"adodb/readme.txt";s:4:"a2d2";s:22:"adodb/rsfilter.inc.php";s:4:"c464";s:16:"adodb/server.php";s:4:"7e2b";s:22:"adodb/toexport.inc.php";s:4:"09fd";s:20:"adodb/tohtml.inc.php";s:4:"0b11";s:19:"adodb/xmlschema.dtd";s:4:"26f3";s:21:"adodb/xmlschema03.dtd";s:4:"fa85";s:30:"adodb/contrib/toxmlrpc.inc.php";s:4:"4c40";s:38:"adodb/datadict/datadict-access.inc.php";s:4:"b39a";s:35:"adodb/datadict/datadict-db2.inc.php";s:4:"e0ff";s:40:"adodb/datadict/datadict-firebird.inc.php";s:4:"a361";s:39:"adodb/datadict/datadict-generic.inc.php";s:4:"a3c4";s:37:"adodb/datadict/datadict-ibase.inc.php";s:4:"4f0b";s:40:"adodb/datadict/datadict-informix.inc.php";s:4:"6f7d";s:37:"adodb/datadict/datadict-mssql.inc.php";s:4:"2ec2";s:37:"adodb/datadict/datadict-mysql.inc.php";s:4:"09a5";s:36:"adodb/datadict/datadict-oci8.inc.php";s:4:"f1ef";s:40:"adodb/datadict/datadict-postgres.inc.php";s:4:"c5c5";s:37:"adodb/datadict/datadict-sapdb.inc.php";s:4:"aa27";s:38:"adodb/datadict/datadict-sybase.inc.php";s:4:"ac80";s:34:"adodb/drivers/adodb-access.inc.php";s:4:"3505";s:31:"adodb/drivers/adodb-ado.inc.php";s:4:"7c39";s:32:"adodb/drivers/adodb-ado5.inc.php";s:4:"4017";s:38:"adodb/drivers/adodb-ado_access.inc.php";s:4:"c94e";s:37:"adodb/drivers/adodb-ado_mssql.inc.php";s:4:"f6e6";s:41:"adodb/drivers/adodb-borland_ibase.inc.php";s:4:"b433";s:31:"adodb/drivers/adodb-csv.inc.php";s:4:"e7ce";s:31:"adodb/drivers/adodb-db2.inc.php";s:4:"9992";s:33:"adodb/drivers/adodb-fbsql.inc.php";s:4:"6df2";s:36:"adodb/drivers/adodb-firebird.inc.php";s:4:"91e1";s:33:"adodb/drivers/adodb-ibase.inc.php";s:4:"c268";s:36:"adodb/drivers/adodb-informix.inc.php";s:4:"5377";s:38:"adodb/drivers/adodb-informix72.inc.php";s:4:"f008";s:32:"adodb/drivers/adodb-ldap.inc.php";s:4:"1db9";s:33:"adodb/drivers/adodb-mssql.inc.php";s:4:"49be";s:35:"adodb/drivers/adodb-mssqlpo.inc.php";s:4:"d6f4";s:33:"adodb/drivers/adodb-mysql.inc.php";s:4:"7c41";s:34:"adodb/drivers/adodb-mysqli.inc.php";s:4:"e46b";s:34:"adodb/drivers/adodb-mysqlt.inc.php";s:4:"5d6d";s:35:"adodb/drivers/adodb-netezza.inc.php";s:4:"11ca";s:32:"adodb/drivers/adodb-oci8.inc.php";s:4:"8c73";s:34:"adodb/drivers/adodb-oci805.inc.php";s:4:"5d72";s:34:"adodb/drivers/adodb-oci8po.inc.php";s:4:"58cc";s:32:"adodb/drivers/adodb-odbc.inc.php";s:4:"0f5d";s:36:"adodb/drivers/adodb-odbc_db2.inc.php";s:4:"e468";s:38:"adodb/drivers/adodb-odbc_mssql.inc.php";s:4:"bd54";s:39:"adodb/drivers/adodb-odbc_oracle.inc.php";s:4:"f279";s:33:"adodb/drivers/adodb-odbtp.inc.php";s:4:"20b8";s:41:"adodb/drivers/adodb-odbtp_unicode.inc.php";s:4:"e8a3";s:34:"adodb/drivers/adodb-oracle.inc.php";s:4:"a41c";s:31:"adodb/drivers/adodb-pdo.inc.php";s:4:"2dbc";s:37:"adodb/drivers/adodb-pdo_mssql.inc.php";s:4:"d874";s:37:"adodb/drivers/adodb-pdo_mysql.inc.php";s:4:"4b35";s:35:"adodb/drivers/adodb-pdo_oci.inc.php";s:4:"3be0";s:37:"adodb/drivers/adodb-pdo_pgsql.inc.php";s:4:"706b";s:36:"adodb/drivers/adodb-postgres.inc.php";s:4:"d73a";s:38:"adodb/drivers/adodb-postgres64.inc.php";s:4:"002a";s:37:"adodb/drivers/adodb-postgres7.inc.php";s:4:"bc47";s:37:"adodb/drivers/adodb-postgres8.inc.php";s:4:"23eb";s:33:"adodb/drivers/adodb-proxy.inc.php";s:4:"cf98";s:33:"adodb/drivers/adodb-sapdb.inc.php";s:4:"6dcc";s:39:"adodb/drivers/adodb-sqlanywhere.inc.php";s:4:"9b5c";s:34:"adodb/drivers/adodb-sqlite.inc.php";s:4:"15e7";s:36:"adodb/drivers/adodb-sqlitepo.inc.php";s:4:"503b";s:34:"adodb/drivers/adodb-sybase.inc.php";s:4:"3155";s:38:"adodb/drivers/adodb-sybase_ase.inc.php";s:4:"0a30";s:31:"adodb/drivers/adodb-vfp.inc.php";s:4:"6ceb";s:27:"adodb/lang/adodb-ar.inc.php";s:4:"6f2a";s:27:"adodb/lang/adodb-bg.inc.php";s:4:"666d";s:31:"adodb/lang/adodb-bgutf8.inc.php";s:4:"42c7";s:27:"adodb/lang/adodb-ca.inc.php";s:4:"9306";s:27:"adodb/lang/adodb-cn.inc.php";s:4:"c8e1";s:27:"adodb/lang/adodb-cz.inc.php";s:4:"0339";s:27:"adodb/lang/adodb-da.inc.php";s:4:"2ea2";s:27:"adodb/lang/adodb-de.inc.php";s:4:"6e6e";s:27:"adodb/lang/adodb-en.inc.php";s:4:"0820";s:27:"adodb/lang/adodb-es.inc.php";s:4:"de07";s:34:"adodb/lang/adodb-esperanto.inc.php";s:4:"32b9";s:27:"adodb/lang/adodb-fr.inc.php";s:4:"237c";s:27:"adodb/lang/adodb-hu.inc.php";s:4:"f308";s:27:"adodb/lang/adodb-it.inc.php";s:4:"ae50";s:27:"adodb/lang/adodb-nl.inc.php";s:4:"ed3d";s:27:"adodb/lang/adodb-pl.inc.php";s:4:"333e";s:30:"adodb/lang/adodb-pt-br.inc.php";s:4:"e63b";s:27:"adodb/lang/adodb-ro.inc.php";s:4:"779e";s:31:"adodb/lang/adodb-ru1251.inc.php";s:4:"43c8";s:27:"adodb/lang/adodb-sv.inc.php";s:4:"2e5a";s:31:"adodb/lang/adodb-uk1251.inc.php";s:4:"3203";s:26:"adodb/pear/readme.Auth.txt";s:4:"87dd";s:35:"adodb/pear/Auth/Container/ADOdb.php";s:4:"fa37";s:29:"adodb/xsl/convert-0.1-0.2.xsl";s:4:"29d9";s:29:"adodb/xsl/convert-0.1-0.3.xsl";s:4:"6aad";s:29:"adodb/xsl/convert-0.2-0.1.xsl";s:4:"5d27";s:29:"adodb/xsl/convert-0.2-0.3.xsl";s:4:"4098";s:24:"adodb/xsl/remove-0.2.xsl";s:4:"0b2b";s:24:"adodb/xsl/remove-0.3.xsl";s:4:"678d";s:18:"doc/494.DBAL.patch";s:4:"b563";s:10:"doc/README";s:4:"d375";s:25:"doc/mssql-error-fix.patch";s:4:"8757";s:23:"res/checkconnection.gif";s:4:"1760";}',
	'constraints' => array(
		'depends' => array(
			'php' => '5.1.0-0.0.0',
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
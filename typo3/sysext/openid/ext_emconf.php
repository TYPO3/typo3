<?php

########################################################################
# Extension Manager/Repository config file for ext "openid".
#
# Auto generated 16-10-2012 14:07
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'OpenID authentication',
	'description' => 'Adds OpenID authentication to TYPO3',
	'category' => 'services',
	'author' => 'Dmitry Dulepov',
	'author_email' => 'dmitry@typo3.org',
	'shy' => '',
	'dependencies' => '',
	'conflicts' => 'naw_openid,naw_openid_be',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => 'fe_users,be_users',
	'clearCacheOnLoad' => 0,
	'lockType' => 'system',
	'author_company' => 'TYPO3 core team',
	'version' => '4.7.5',
	'constraints' => array(
		'depends' => array(
			'php' => '5.3.0-0.0.0',
			'typo3' => '4.7.0-0.0.0',
		),
		'conflicts' => array(
			'naw_openid' => '',
			'naw_openid_be' => '',
		),
		'suggests' => array(
		),
	),
	'suggests' => array(
	),
	'_md5_values_when_last_written' => 'a:57:{s:23:"class.tx_openid_eid.php";s:4:"4f2f";s:29:"class.tx_openid_mod_setup.php";s:4:"80a4";s:26:"class.tx_openid_return.php";s:4:"65c3";s:12:"ext_icon.gif";s:4:"5cfe";s:17:"ext_localconf.php";s:4:"a16c";s:14:"ext_tables.php";s:4:"d534";s:14:"ext_tables.sql";s:4:"9ab4";s:17:"locallang_csh.xlf";s:4:"9151";s:21:"locallang_csh_mod.xlf";s:4:"0848";s:16:"locallang_db.xlf";s:4:"4224";s:4:"TODO";s:4:"6adf";s:14:"doc/manual.sxw";s:4:"05d1";s:22:"lib/php-openid/COPYING";s:4:"3b83";s:37:"lib/php-openid/php-openid-typo3.patch";s:4:"3fb6";s:25:"lib/php-openid/README.txt";s:4:"eb02";s:30:"lib/php-openid/Auth/OpenID.php";s:4:"201f";s:42:"lib/php-openid/Auth/OpenID/Association.php";s:4:"5b10";s:33:"lib/php-openid/Auth/OpenID/AX.php";s:4:"18c3";s:38:"lib/php-openid/Auth/OpenID/BigMath.php";s:4:"2b2b";s:39:"lib/php-openid/Auth/OpenID/Consumer.php";s:4:"db5b";s:40:"lib/php-openid/Auth/OpenID/CryptUtil.php";s:4:"6276";s:49:"lib/php-openid/Auth/OpenID/DatabaseConnection.php";s:4:"660d";s:44:"lib/php-openid/Auth/OpenID/DiffieHellman.php";s:4:"e6e8";s:39:"lib/php-openid/Auth/OpenID/Discover.php";s:4:"1abc";s:40:"lib/php-openid/Auth/OpenID/DumbStore.php";s:4:"c1e9";s:40:"lib/php-openid/Auth/OpenID/Extension.php";s:4:"5aae";s:40:"lib/php-openid/Auth/OpenID/FileStore.php";s:4:"20ba";s:35:"lib/php-openid/Auth/OpenID/HMAC.php";s:4:"a0a3";s:40:"lib/php-openid/Auth/OpenID/Interface.php";s:4:"421b";s:37:"lib/php-openid/Auth/OpenID/KVForm.php";s:4:"3c7c";s:45:"lib/php-openid/Auth/OpenID/MemcachedStore.php";s:4:"cb6d";s:38:"lib/php-openid/Auth/OpenID/Message.php";s:4:"717a";s:41:"lib/php-openid/Auth/OpenID/MySQLStore.php";s:4:"4607";s:36:"lib/php-openid/Auth/OpenID/Nonce.php";s:4:"2738";s:35:"lib/php-openid/Auth/OpenID/PAPE.php";s:4:"decb";s:36:"lib/php-openid/Auth/OpenID/Parse.php";s:4:"28c9";s:46:"lib/php-openid/Auth/OpenID/PostgreSQLStore.php";s:4:"a2da";s:37:"lib/php-openid/Auth/OpenID/Server.php";s:4:"e37b";s:44:"lib/php-openid/Auth/OpenID/ServerRequest.php";s:4:"d29d";s:42:"lib/php-openid/Auth/OpenID/SQLiteStore.php";s:4:"4855";s:39:"lib/php-openid/Auth/OpenID/SQLStore.php";s:4:"87cb";s:35:"lib/php-openid/Auth/OpenID/SReg.php";s:4:"5e7e";s:40:"lib/php-openid/Auth/OpenID/TrustRoot.php";s:4:"2866";s:38:"lib/php-openid/Auth/OpenID/URINorm.php";s:4:"d61b";s:41:"lib/php-openid/Auth/Yadis/HTTPFetcher.php";s:4:"bdaa";s:37:"lib/php-openid/Auth/Yadis/Manager.php";s:4:"ee7d";s:34:"lib/php-openid/Auth/Yadis/Misc.php";s:4:"65f6";s:49:"lib/php-openid/Auth/Yadis/ParanoidHTTPFetcher.php";s:4:"170e";s:39:"lib/php-openid/Auth/Yadis/ParseHTML.php";s:4:"d8f8";s:46:"lib/php-openid/Auth/Yadis/PlainHTTPFetcher.php";s:4:"6d0f";s:33:"lib/php-openid/Auth/Yadis/XML.php";s:4:"bb7e";s:34:"lib/php-openid/Auth/Yadis/XRDS.php";s:4:"12e5";s:33:"lib/php-openid/Auth/Yadis/XRI.php";s:4:"27e8";s:36:"lib/php-openid/Auth/Yadis/XRIRes.php";s:4:"a683";s:35:"lib/php-openid/Auth/Yadis/Yadis.php";s:4:"8da3";s:29:"sv1/class.tx_openid_store.php";s:4:"751f";s:27:"sv1/class.tx_openid_sv1.php";s:4:"617a";}',
);

?>
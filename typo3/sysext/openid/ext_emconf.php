<?php

########################################################################
# Extension Manager/Repository config file for ext: "openid"
#
# Auto generated 29-10-2008 13:30
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
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
	'state' => 'beta',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => 'typo3temp/tx_openid',
	'modify_tables' => 'fe_users,be_users',
	'clearCacheOnLoad' => 0,
	'lockType' => 'system',
	'author_company' => 'TYPO3 core team',
	'version' => '0.1.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '4.3.0-0.0.0',
			'php' => '5.2.0-0.0.0',
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
	'_md5_values_when_last_written' => 'a:56:{s:4:"TODO";s:4:"977e";s:23:"class.tx_openid_eid.php";s:4:"4938";s:26:"class.tx_openid_return.php";s:4:"ce0d";s:19:"ext_emconf.php.mine";s:4:"5bb6";s:20:"ext_emconf.php.r4368";s:4:"4780";s:20:"ext_emconf.php.r4380";s:4:"03fd";s:12:"ext_icon.gif";s:4:"ef0a";s:17:"ext_localconf.php";s:4:"cad0";s:14:"ext_tables.php";s:4:"65b0";s:14:"ext_tables.sql";s:4:"8a3e";s:17:"locallang_csh.xml";s:4:"5c81";s:16:"locallang_db.xml";s:4:"39ff";s:22:"lib/php-openid/COPYING";s:4:"3b83";s:25:"lib/php-openid/README.txt";s:4:"953e";s:37:"lib/php-openid/php-openid-typo3.patch";s:4:"3375";s:30:"lib/php-openid/Auth/OpenID.php";s:4:"3be9";s:33:"lib/php-openid/Auth/OpenID/AX.php";s:4:"b68e";s:42:"lib/php-openid/Auth/OpenID/Association.php";s:4:"9b1e";s:38:"lib/php-openid/Auth/OpenID/BigMath.php";s:4:"a56d";s:39:"lib/php-openid/Auth/OpenID/Consumer.php";s:4:"ec57";s:40:"lib/php-openid/Auth/OpenID/CryptUtil.php";s:4:"6276";s:49:"lib/php-openid/Auth/OpenID/DatabaseConnection.php";s:4:"660d";s:44:"lib/php-openid/Auth/OpenID/DiffieHellman.php";s:4:"1a0b";s:39:"lib/php-openid/Auth/OpenID/Discover.php";s:4:"1a9b";s:40:"lib/php-openid/Auth/OpenID/DumbStore.php";s:4:"c1e9";s:40:"lib/php-openid/Auth/OpenID/Extension.php";s:4:"5aae";s:40:"lib/php-openid/Auth/OpenID/FileStore.php";s:4:"69da";s:35:"lib/php-openid/Auth/OpenID/HMAC.php";s:4:"a0a3";s:40:"lib/php-openid/Auth/OpenID/Interface.php";s:4:"421b";s:37:"lib/php-openid/Auth/OpenID/KVForm.php";s:4:"3c7c";s:45:"lib/php-openid/Auth/OpenID/MemcachedStore.php";s:4:"db8c";s:38:"lib/php-openid/Auth/OpenID/Message.php";s:4:"413e";s:41:"lib/php-openid/Auth/OpenID/MySQLStore.php";s:4:"4607";s:36:"lib/php-openid/Auth/OpenID/Nonce.php";s:4:"2738";s:35:"lib/php-openid/Auth/OpenID/PAPE.php";s:4:"e586";s:36:"lib/php-openid/Auth/OpenID/Parse.php";s:4:"28c9";s:46:"lib/php-openid/Auth/OpenID/PostgreSQLStore.php";s:4:"cd44";s:39:"lib/php-openid/Auth/OpenID/SQLStore.php";s:4:"29d2";s:42:"lib/php-openid/Auth/OpenID/SQLiteStore.php";s:4:"4855";s:35:"lib/php-openid/Auth/OpenID/SReg.php";s:4:"ae70";s:37:"lib/php-openid/Auth/OpenID/Server.php";s:4:"2006";s:44:"lib/php-openid/Auth/OpenID/ServerRequest.php";s:4:"d29d";s:40:"lib/php-openid/Auth/OpenID/TrustRoot.php";s:4:"002d";s:38:"lib/php-openid/Auth/OpenID/URINorm.php";s:4:"e4fb";s:41:"lib/php-openid/Auth/Yadis/HTTPFetcher.php";s:4:"c2ed";s:37:"lib/php-openid/Auth/Yadis/Manager.php";s:4:"ee7d";s:34:"lib/php-openid/Auth/Yadis/Misc.php";s:4:"65f6";s:49:"lib/php-openid/Auth/Yadis/ParanoidHTTPFetcher.php";s:4:"86ea";s:39:"lib/php-openid/Auth/Yadis/ParseHTML.php";s:4:"1d59";s:46:"lib/php-openid/Auth/Yadis/PlainHTTPFetcher.php";s:4:"6d0f";s:33:"lib/php-openid/Auth/Yadis/XML.php";s:4:"09f1";s:34:"lib/php-openid/Auth/Yadis/XRDS.php";s:4:"4bcd";s:33:"lib/php-openid/Auth/Yadis/XRI.php";s:4:"5eca";s:36:"lib/php-openid/Auth/Yadis/XRIRes.php";s:4:"9b44";s:35:"lib/php-openid/Auth/Yadis/Yadis.php";s:4:"d6ee";s:27:"sv1/class.tx_openid_sv1.php";s:4:"ab2a";}',
);

?>
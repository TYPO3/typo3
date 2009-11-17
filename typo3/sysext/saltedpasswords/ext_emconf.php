<?php

########################################################################
# Extension Manager/Repository config file for ext "saltedpasswords".
#
# Auto generated 17-11-2009 20:38
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Salted user password hashes',
	'description' => 'Uses a password hashing framework for storing passwords. Integrates into the system extension "felogin". Use SSL or rsaauth to secure datatransfer! Please read the manual first!',
	'category' => 'services',
	'shy' => 0,
	'dependencies' => 'cms',
	'conflicts' => 'kb_md5fepw,newloginbox,pt_feauthcryptpw,t3sec_saltedpw',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => 'fe_users,be_users',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author' => 'Marcus Krause, Steffen Ritter',
	'author_email' => 'marcus#exp2009@t3sec.info',
	'author_company' => 'TYPO3 Security Team',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'version' => '1.0.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '4.3.0-0.0.0',
			'php' => '5.2.0-0.0.0',
			'cms' => '',
		),
		'conflicts' => array(
			'kb_md5fepw' => '',
			'newloginbox' => '',
			'pt_feauthcryptpw' => '',
			't3sec_saltedpw' => '',
		),
		'suggests' => array(
			'rsaauth' => '',
		),
	),
	'_md5_values_when_last_written' => 'a:25:{s:9:"ChangeLog";s:4:"6895";s:16:"ext_autoload.php";s:4:"8275";s:21:"ext_conf_template.txt";s:4:"1a62";s:12:"ext_icon.gif";s:4:"4324";s:17:"ext_localconf.php";s:4:"05a6";s:14:"ext_tables.php";s:4:"8646";s:14:"ext_tables.sql";s:4:"a0d9";s:13:"locallang.xml";s:4:"3d55";s:40:"classes/class.tx_saltedpasswords_div.php";s:4:"44d6";s:49:"classes/class.tx_saltedpasswords_emconfhelper.php";s:4:"f612";s:46:"classes/eval/class.tx_saltedpasswords_eval.php";s:4:"f63a";s:49:"classes/eval/class.tx_saltedpasswords_eval_be.php";s:4:"e7b9";s:49:"classes/eval/class.tx_saltedpasswords_eval_fe.php";s:4:"abc8";s:57:"classes/salts/class.tx_saltedpasswords_abstract_salts.php";s:4:"bc87";s:57:"classes/salts/class.tx_saltedpasswords_salts_blowfish.php";s:4:"c2b8";s:56:"classes/salts/class.tx_saltedpasswords_salts_factory.php";s:4:"0d12";s:52:"classes/salts/class.tx_saltedpasswords_salts_md5.php";s:4:"e87c";s:55:"classes/salts/class.tx_saltedpasswords_salts_phpass.php";s:4:"a6de";s:63:"classes/salts/interfaces/interface.tx_saltedpasswords_salts.php";s:4:"79e3";s:36:"sv1/class.tx_saltedpasswords_sv1.php";s:4:"938e";s:41:"tests/tx_saltedpasswords_div_testcase.php";s:4:"e64d";s:52:"tests/tx_saltedpasswords_salts_blowfish_testcase.php";s:4:"2a8a";s:51:"tests/tx_saltedpasswords_salts_factory_testcase.php";s:4:"989e";s:47:"tests/tx_saltedpasswords_salts_md5_testcase.php";s:4:"8802";s:50:"tests/tx_saltedpasswords_salts_phpass_testcase.php";s:4:"d786";}',
	'suggests' => array(
	),
);

?>
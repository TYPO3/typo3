<?php

########################################################################
# Extension Manager/Repository config file for ext "saltedpasswords".
#
# Auto generated 16-10-2012 14:07
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
	'version' => '4.7.5',
	'constraints' => array(
		'depends' => array(
			'php' => '5.3.0-0.0.0',
			'typo3' => '4.7.0-0.0.0',
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
	'_md5_values_when_last_written' => 'a:27:{s:9:"ChangeLog";s:4:"6895";s:16:"ext_autoload.php";s:4:"25ee";s:21:"ext_conf_template.txt";s:4:"1a62";s:12:"ext_icon.gif";s:4:"18f9";s:17:"ext_localconf.php";s:4:"811f";s:14:"ext_tables.php";s:4:"f146";s:13:"locallang.xlf";s:4:"0669";s:47:"classes/class.tx_saltedpasswords_autoloader.php";s:4:"d999";s:40:"classes/class.tx_saltedpasswords_div.php";s:4:"0300";s:49:"classes/class.tx_saltedpasswords_emconfhelper.php";s:4:"60a0";s:46:"classes/eval/class.tx_saltedpasswords_eval.php";s:4:"8fdf";s:49:"classes/eval/class.tx_saltedpasswords_eval_be.php";s:4:"a979";s:49:"classes/eval/class.tx_saltedpasswords_eval_fe.php";s:4:"0164";s:57:"classes/salts/class.tx_saltedpasswords_abstract_salts.php";s:4:"ec15";s:57:"classes/salts/class.tx_saltedpasswords_salts_blowfish.php";s:4:"ba18";s:56:"classes/salts/class.tx_saltedpasswords_salts_factory.php";s:4:"5d0b";s:52:"classes/salts/class.tx_saltedpasswords_salts_md5.php";s:4:"9871";s:55:"classes/salts/class.tx_saltedpasswords_salts_phpass.php";s:4:"1f75";s:63:"classes/salts/interfaces/interface.tx_saltedpasswords_salts.php";s:4:"e4a5";s:59:"classes/tasks/class.tx_saltedpasswords_tasks_bulkupdate.php";s:4:"9c59";s:14:"doc/manual.sxw";s:4:"fc4b";s:36:"sv1/class.tx_saltedpasswords_sv1.php";s:4:"f079";s:36:"tests/tx_saltedpasswords_divTest.php";s:4:"40e5";s:47:"tests/tx_saltedpasswords_salts_blowfishTest.php";s:4:"0e0e";s:46:"tests/tx_saltedpasswords_salts_factoryTest.php";s:4:"fd08";s:42:"tests/tx_saltedpasswords_salts_md5Test.php";s:4:"8c19";s:45:"tests/tx_saltedpasswords_salts_phpassTest.php";s:4:"8c3f";}',
	'suggests' => array(
	),
);

?>
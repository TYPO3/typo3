<?php

########################################################################
# Extension Manager/Repository config file for ext "sv".
#
# Auto generated 22-06-2010 13:09
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'TYPO3 System Services',
	'description' => 'The core/default sevices. This includes the default authentication services for now.',
	'category' => 'services',
	'shy' => 1,
	'dependencies' => '',
	'conflicts' => '',
	'priority' => 'top',
	'module' => '',
	'state' => 'stable',
	'internal' => 1,
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 1,
	'lockType' => 'S',
	'author' => 'Rene Fritz',
	'author_email' => 'r.fritz@colorcube.de',
	'author_company' => 'Colorcube',
	'version' => '1.1.0',
	'_md5_values_when_last_written' => 'a:11:{s:20:"class.tx_sv_auth.php";s:4:"a05f";s:24:"class.tx_sv_authbase.php";s:4:"2a78";s:29:"class.tx_sv_loginformhook.php";s:4:"3278";s:16:"ext_autoload.php";s:4:"8389";s:12:"ext_icon.gif";s:4:"87d7";s:17:"ext_localconf.php";s:4:"9a4f";s:14:"ext_tables.php";s:4:"8b1c";s:44:"reports/class.tx_sv_reports_serviceslist.php";s:4:"aadb";s:21:"reports/locallang.xml";s:4:"dbc2";s:24:"reports/tx_sv_report.css";s:4:"8cda";s:24:"reports/tx_sv_report.png";s:4:"84bb";}',
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
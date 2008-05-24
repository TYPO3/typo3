<?php

########################################################################
# Extension Manager/Repository config file for ext: "sv"
#
# Auto generated 23-04-2008 10:25
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
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
	'_md5_values_when_last_written' => 'a:5:{s:20:"class.tx_sv_auth.php";s:4:"3242";s:24:"class.tx_sv_authbase.php";s:4:"23b3";s:12:"ext_icon.gif";s:4:"87d7";s:17:"ext_localconf.php";s:4:"0ea3";s:14:"ext_tables.php";s:4:"d4b5";}',
	'constraints' => array(
		'depends' => array(
			'php' => '5.1.0-0.0.0',
			'typo3' => '4.2.0-4.2.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
);

?>
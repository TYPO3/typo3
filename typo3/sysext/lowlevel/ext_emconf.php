<?php

########################################################################
# Extension Manager/Repository config file for ext: "lowlevel"
#
# Auto generated 23-04-2008 10:24
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Tools>Config+DBint',
	'description' => 'Enables the \'Config\' and \'DBint\' modules for technical analysis of the system. This includes raw database search, checking relations, counting pages and records etc.',
	'category' => 'module',
	'shy' => 1,
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => 'config,dbint,dbint/cli',
	'state' => 'stable',
	'internal' => 0,
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author' => 'Kasper Skaarhoj',
	'author_email' => 'kasperYYYY@typo3.com',
	'author_company' => 'Curby Soft Multimedia',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'version' => '1.2.0',
	'_md5_values_when_last_written' => 'a:30:{s:38:"HOWTO_clean_up_TYPO3_installations.txt";s:4:"bd7c";s:29:"class.tx_lowlevel_cleaner.php";s:4:"175e";s:34:"class.tx_lowlevel_cleaner_core.php";s:4:"e4ad";s:12:"ext_icon.gif";s:4:"4bcb";s:17:"ext_localconf.php";s:4:"97ec";s:14:"ext_tables.php";s:4:"ad58";s:15:"dbint/clear.gif";s:4:"cc11";s:14:"dbint/conf.php";s:4:"9a7b";s:12:"dbint/db.gif";s:4:"4bcb";s:15:"dbint/index.php";s:4:"d87f";s:19:"dbint/locallang.xml";s:4:"2083";s:23:"dbint/locallang_mod.xml";s:4:"cf60";s:25:"dbint/cli/cleaner_cli.php";s:4:"6608";s:26:"dbint/cli/refindex_cli.php";s:4:"9be9";s:30:"clmods/class.cleanflexform.php";s:4:"1ffe";s:24:"clmods/class.deleted.php";s:4:"5a5c";s:29:"clmods/class.double_files.php";s:4:"c7ff";s:27:"clmods/class.lost_files.php";s:4:"a846";s:30:"clmods/class.missing_files.php";s:4:"e873";s:34:"clmods/class.missing_relations.php";s:4:"005a";s:31:"clmods/class.orphan_records.php";s:4:"11f1";s:27:"clmods/class.rte_images.php";s:4:"680d";s:23:"clmods/class.syslog.php";s:4:"7203";s:25:"clmods/class.versions.php";s:4:"755b";s:12:"doc/TODO.txt";s:4:"4dcc";s:16:"config/clear.gif";s:4:"cc11";s:15:"config/conf.php";s:4:"1434";s:17:"config/config.gif";s:4:"2d41";s:16:"config/index.php";s:4:"5e51";s:24:"config/locallang_mod.xml";s:4:"5fe4";}',
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
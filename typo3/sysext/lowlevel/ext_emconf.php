<?php

########################################################################
# Extension Manager/Repository config file for ext: "lowlevel"
#
# Auto generated 11-03-2009 19:04
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
	'_md5_values_when_last_written' => 'a:34:{s:38:"HOWTO_clean_up_TYPO3_installations.txt";s:4:"bd7c";s:13:"admin_cli.php";s:4:"539d";s:32:"class.tx_lowlevel_admin_core.php";s:4:"7b4b";s:29:"class.tx_lowlevel_cleaner.php";s:4:"a7b4";s:34:"class.tx_lowlevel_cleaner_core.php";s:4:"d670";s:12:"ext_icon.gif";s:4:"4bcb";s:17:"ext_localconf.php";s:4:"fc55";s:14:"ext_tables.php";s:4:"ad58";s:30:"clmods/class.cleanflexform.php";s:4:"5317";s:24:"clmods/class.deleted.php";s:4:"3d4a";s:29:"clmods/class.double_files.php";s:4:"4754";s:27:"clmods/class.lost_files.php";s:4:"c385";s:30:"clmods/class.missing_files.php";s:4:"b248";s:34:"clmods/class.missing_relations.php";s:4:"f2df";s:31:"clmods/class.orphan_records.php";s:4:"2f82";s:27:"clmods/class.rte_images.php";s:4:"9e23";s:23:"clmods/class.syslog.php";s:4:"7416";s:25:"clmods/class.versions.php";s:4:"a67d";s:16:"config/clear.gif";s:4:"cc11";s:15:"config/conf.php";s:4:"1434";s:17:"config/config.gif";s:4:"2d41";s:16:"config/index.php";s:4:"87ce";s:20:"config/locallang.xml";s:4:"deac";s:24:"config/locallang_mod.xml";s:4:"5fe4";s:15:"dbint/clear.gif";s:4:"cc11";s:14:"dbint/conf.php";s:4:"9a7b";s:12:"dbint/db.gif";s:4:"4bcb";s:15:"dbint/index.php";s:4:"dfe3";s:19:"dbint/locallang.xml";s:4:"9dc4";s:23:"dbint/locallang_mod.xml";s:4:"cf60";s:25:"dbint/cli/cleaner_cli.php";s:4:"e50c";s:29:"dbint/cli/cleaner_cli.php.rej";s:4:"777a";s:26:"dbint/cli/refindex_cli.php";s:4:"956f";s:12:"doc/TODO.txt";s:4:"4dcc";}',
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
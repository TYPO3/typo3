<?php

########################################################################
# Extension Manager/Repository config file for ext: "lowlevel"
#
# Auto generated 20-01-2009 14:25
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
	'author' => 'Kasper Skårhøj',
	'author_email' => 'kasperYYYY@typo3.com',
	'author_company' => 'Curby Soft Multimedia',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'version' => '1.1.2',
	'_md5_values_when_last_written' => 'a:74:{s:29:"class.tx_lowlevel_cleaner.php";s:4:"7e9a";s:34:"class.tx_lowlevel_cleaner_core.php";s:4:"e696";s:12:"ext_icon.gif";s:4:"4bcb";s:14:"ext_tables.php";s:4:"da7d";s:12:"doc/TODO.txt";s:4:"4dcc";s:20:"doc/.svn/all-wcprops";s:4:"70d7";s:16:"doc/.svn/entries";s:4:"234e";s:15:"doc/.svn/format";s:4:"7c5a";s:36:"doc/.svn/text-base/TODO.txt.svn-base";s:4:"4dcc";s:36:"doc/.svn/prop-base/TODO.txt.svn-base";s:4:"3c71";s:16:".svn/all-wcprops";s:4:"c282";s:12:".svn/entries";s:4:"c86b";s:11:".svn/format";s:4:"7c5a";s:53:".svn/text-base/class.tx_lowlevel_cleaner.php.svn-base";s:4:"7e9a";s:58:".svn/text-base/class.tx_lowlevel_cleaner_core.php.svn-base";s:4:"e696";s:38:".svn/text-base/ext_emconf.php.svn-base";s:4:"b949";s:36:".svn/text-base/ext_icon.gif.svn-base";s:4:"4bcb";s:38:".svn/text-base/ext_tables.php.svn-base";s:4:"da7d";s:53:".svn/prop-base/class.tx_lowlevel_cleaner.php.svn-base";s:4:"685f";s:58:".svn/prop-base/class.tx_lowlevel_cleaner_core.php.svn-base";s:4:"685f";s:38:".svn/prop-base/ext_emconf.php.svn-base";s:4:"3c71";s:36:".svn/prop-base/ext_icon.gif.svn-base";s:4:"c5ac";s:38:".svn/prop-base/ext_tables.php.svn-base";s:4:"3c71";s:16:"config/clear.gif";s:4:"cc11";s:15:"config/conf.php";s:4:"1434";s:17:"config/config.gif";s:4:"2d41";s:16:"config/index.php";s:4:"07ac";s:24:"config/locallang_mod.xml";s:4:"5fe4";s:23:"config/.svn/all-wcprops";s:4:"e756";s:19:"config/.svn/entries";s:4:"ee27";s:18:"config/.svn/format";s:4:"7c5a";s:40:"config/.svn/text-base/clear.gif.svn-base";s:4:"cc11";s:39:"config/.svn/text-base/conf.php.svn-base";s:4:"1434";s:41:"config/.svn/text-base/config.gif.svn-base";s:4:"2d41";s:40:"config/.svn/text-base/index.php.svn-base";s:4:"07ac";s:48:"config/.svn/text-base/locallang_mod.xml.svn-base";s:4:"5fe4";s:40:"config/.svn/prop-base/clear.gif.svn-base";s:4:"c5ac";s:39:"config/.svn/prop-base/conf.php.svn-base";s:4:"3c71";s:41:"config/.svn/prop-base/config.gif.svn-base";s:4:"c5ac";s:40:"config/.svn/prop-base/index.php.svn-base";s:4:"3c71";s:48:"config/.svn/prop-base/locallang_mod.xml.svn-base";s:4:"3c71";s:15:"dbint/clear.gif";s:4:"cc11";s:14:"dbint/conf.php";s:4:"9a7b";s:12:"dbint/db.gif";s:4:"4bcb";s:15:"dbint/index.php";s:4:"59b9";s:19:"dbint/locallang.xml";s:4:"2083";s:23:"dbint/locallang_mod.xml";s:4:"cf60";s:22:"dbint/.svn/all-wcprops";s:4:"37ca";s:18:"dbint/.svn/entries";s:4:"6ecb";s:17:"dbint/.svn/format";s:4:"7c5a";s:39:"dbint/.svn/text-base/clear.gif.svn-base";s:4:"cc11";s:38:"dbint/.svn/text-base/conf.php.svn-base";s:4:"9a7b";s:36:"dbint/.svn/text-base/db.gif.svn-base";s:4:"4bcb";s:39:"dbint/.svn/text-base/index.php.svn-base";s:4:"59b9";s:43:"dbint/.svn/text-base/locallang.xml.svn-base";s:4:"2083";s:47:"dbint/.svn/text-base/locallang_mod.xml.svn-base";s:4:"cf60";s:39:"dbint/.svn/prop-base/clear.gif.svn-base";s:4:"c5ac";s:38:"dbint/.svn/prop-base/conf.php.svn-base";s:4:"3c71";s:36:"dbint/.svn/prop-base/db.gif.svn-base";s:4:"c5ac";s:39:"dbint/.svn/prop-base/index.php.svn-base";s:4:"3c71";s:43:"dbint/.svn/prop-base/locallang.xml.svn-base";s:4:"685f";s:47:"dbint/.svn/prop-base/locallang_mod.xml.svn-base";s:4:"3c71";s:27:"dbint/cli/cleaner_cli.phpsh";s:4:"bce0";s:18:"dbint/cli/conf.php";s:4:"2a03";s:28:"dbint/cli/refindex_cli.phpsh";s:4:"e953";s:26:"dbint/cli/.svn/all-wcprops";s:4:"5853";s:22:"dbint/cli/.svn/entries";s:4:"d066";s:21:"dbint/cli/.svn/format";s:4:"7c5a";s:51:"dbint/cli/.svn/text-base/cleaner_cli.phpsh.svn-base";s:4:"bce0";s:42:"dbint/cli/.svn/text-base/conf.php.svn-base";s:4:"2a03";s:52:"dbint/cli/.svn/text-base/refindex_cli.phpsh.svn-base";s:4:"e953";s:51:"dbint/cli/.svn/prop-base/cleaner_cli.phpsh.svn-base";s:4:"1131";s:42:"dbint/cli/.svn/prop-base/conf.php.svn-base";s:4:"25e6";s:52:"dbint/cli/.svn/prop-base/refindex_cli.phpsh.svn-base";s:4:"25e6";}',
	'constraints' => array(
		'depends' => array(
			'php' => '3.0.0-0.0.0',
			'typo3' => '3.7.0-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
);

?>
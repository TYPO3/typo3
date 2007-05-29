<?php

########################################################################
# Extension Manager/Repository config file for ext: "dbal"
#
# Auto generated 27-02-2007 12:25
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Database Abstraction Layer',
	'description' => 'A database abstraction layer implementation for TYPO3 4.0 based on ADOdb and offering a lot of other features...',
	'category' => 'be',
	'shy' => 0,
	'dependencies' => 'adodb',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => 'mod1',
	'state' => 'beta',
	'internal' => 0,
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author' => 'Karsten Dambekalns',
	'author_email' => 'k.dambekalns@fishfarm.de',
	'author_company' => 'TYPO3 Association',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'version' => '0.9.20',
	'_md5_values_when_last_written' => 'a:75:{s:9:".DS_Store";s:4:"f5e4";s:9:"ChangeLog";s:4:"48f1";s:26:"class.ux_db_list_extra.php";s:4:"ea49";s:21:"class.ux_t3lib_db.php";s:4:"bfa7";s:28:"class.ux_t3lib_sqlengine.php";s:4:"99c0";s:28:"class.ux_t3lib_sqlparser.php";s:4:"c549";s:11:"dropall.sql";s:4:"d41d";s:12:"ext_icon.gif";s:4:"1bdc";s:17:"ext_localconf.php";s:4:"579d";s:14:"ext_tables.php";s:4:"427e";s:14:"ext_tables.sql";s:4:"d452";s:16:".svn/all-wcprops";s:4:"229c";s:12:".svn/entries";s:4:"f15f";s:11:".svn/format";s:4:"c30f";s:33:".svn/prop-base/ChangeLog.svn-base";s:4:"685f";s:45:".svn/prop-base/class.ux_t3lib_db.php.svn-base";s:4:"685f";s:52:".svn/prop-base/class.ux_t3lib_sqlengine.php.svn-base";s:4:"685f";s:52:".svn/prop-base/class.ux_t3lib_sqlparser.php.svn-base";s:4:"685f";s:38:".svn/prop-base/ext_emconf.php.svn-base";s:4:"685f";s:36:".svn/prop-base/ext_icon.gif.svn-base";s:4:"1131";s:41:".svn/prop-base/ext_localconf.php.svn-base";s:4:"685f";s:38:".svn/prop-base/ext_tables.php.svn-base";s:4:"685f";s:38:".svn/prop-base/ext_tables.sql.svn-base";s:4:"25e6";s:33:".svn/text-base/ChangeLog.svn-base";s:4:"7121";s:45:".svn/text-base/class.ux_t3lib_db.php.svn-base";s:4:"6309";s:52:".svn/text-base/class.ux_t3lib_sqlengine.php.svn-base";s:4:"e188";s:52:".svn/text-base/class.ux_t3lib_sqlparser.php.svn-base";s:4:"ed47";s:38:".svn/text-base/ext_emconf.php.svn-base";s:4:"703c";s:36:".svn/text-base/ext_icon.gif.svn-base";s:4:"1bdc";s:41:".svn/text-base/ext_localconf.php.svn-base";s:4:"ce33";s:38:".svn/text-base/ext_tables.php.svn-base";s:4:"427e";s:38:".svn/text-base/ext_tables.sql.svn-base";s:4:"d452";s:13:"doc/.DS_Store";s:4:"cf24";s:27:"doc/class.tslib_fe.php.diff";s:4:"0083";s:14:"doc/manual.sxw";s:4:"5a53";s:20:"doc/.svn/all-wcprops";s:4:"5b49";s:16:"doc/.svn/entries";s:4:"3552";s:15:"doc/.svn/format";s:4:"c30f";s:51:"doc/.svn/prop-base/class.tslib_fe.php.diff.svn-base";s:4:"685f";s:38:"doc/.svn/prop-base/manual.sxw.svn-base";s:4:"1131";s:51:"doc/.svn/text-base/class.tslib_fe.php.diff.svn-base";s:4:"0083";s:38:"doc/.svn/text-base/manual.sxw.svn-base";s:4:"5ed3";s:45:"handlers/class.tx_dbal_handler_openoffice.php";s:4:"88d4";s:43:"handlers/class.tx_dbal_handler_rawmysql.php";s:4:"8965";s:40:"handlers/class.tx_dbal_handler_xmldb.php";s:4:"75c1";s:25:"handlers/.svn/all-wcprops";s:4:"ec2c";s:21:"handlers/.svn/entries";s:4:"8da6";s:20:"handlers/.svn/format";s:4:"c30f";s:69:"handlers/.svn/prop-base/class.tx_dbal_handler_openoffice.php.svn-base";s:4:"685f";s:67:"handlers/.svn/prop-base/class.tx_dbal_handler_rawmysql.php.svn-base";s:4:"685f";s:64:"handlers/.svn/prop-base/class.tx_dbal_handler_xmldb.php.svn-base";s:4:"685f";s:69:"handlers/.svn/text-base/class.tx_dbal_handler_openoffice.php.svn-base";s:4:"4171";s:67:"handlers/.svn/text-base/class.tx_dbal_handler_rawmysql.php.svn-base";s:4:"fce6";s:64:"handlers/.svn/text-base/class.tx_dbal_handler_xmldb.php.svn-base";s:4:"edd5";s:14:"mod1/clear.gif";s:4:"cc11";s:13:"mod1/conf.php";s:4:"cf19";s:14:"mod1/index.php";s:4:"749d";s:18:"mod1/locallang.xml";s:4:"0b57";s:22:"mod1/locallang_mod.xml";s:4:"86ef";s:19:"mod1/moduleicon.gif";s:4:"8074";s:21:"mod1/.svn/all-wcprops";s:4:"3047";s:17:"mod1/.svn/entries";s:4:"0a99";s:16:"mod1/.svn/format";s:4:"c30f";s:38:"mod1/.svn/prop-base/clear.gif.svn-base";s:4:"1131";s:37:"mod1/.svn/prop-base/conf.php.svn-base";s:4:"685f";s:38:"mod1/.svn/prop-base/index.php.svn-base";s:4:"685f";s:42:"mod1/.svn/prop-base/locallang.xml.svn-base";s:4:"685f";s:46:"mod1/.svn/prop-base/locallang_mod.xml.svn-base";s:4:"685f";s:43:"mod1/.svn/prop-base/moduleicon.gif.svn-base";s:4:"1131";s:38:"mod1/.svn/text-base/clear.gif.svn-base";s:4:"cc11";s:37:"mod1/.svn/text-base/conf.php.svn-base";s:4:"cf19";s:38:"mod1/.svn/text-base/index.php.svn-base";s:4:"d073";s:42:"mod1/.svn/text-base/locallang.xml.svn-base";s:4:"0b57";s:46:"mod1/.svn/text-base/locallang_mod.xml.svn-base";s:4:"86ef";s:43:"mod1/.svn/text-base/moduleicon.gif.svn-base";s:4:"8074";}',
	'constraints' => array(
		'depends' => array(
			'adodb' => '4.94.0-',
			'php' => '4.1.0-',
			'typo3' => '4.0.0-',
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

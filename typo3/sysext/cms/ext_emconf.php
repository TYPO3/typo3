<?php

########################################################################
# Extension Manager/Repository config file for ext: 'cms'
# 
# Auto generated 12-02-2003 21:25
# 
# Manual updates:
# Only the data in the array - anything else is removed by next write
########################################################################

$EM_CONF[$_EXTKEY] = Array (
	'title' => 'Typo3 CMS (TypoScript)',
	'description' => 'The core TypoScript Content Management engine in TYPO3.
This should probably not be disabled. But the point is that TYPO3 is able to work as a framework for... anything without this (and the whole tslib/ frontend which is tied to this extension). A LOT of the other extensions - in particular all plugins - are dependant on this extension being loaded.',
	'category' => 'be',
	'shy' => 1,
	'dependencies' => '',
	'conflicts' => '',
	'priority' => 'top',
	'module' => 'layout',
	'state' => 'stable',
	'internal' => 1,
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => 'pages',
	'clearCacheOnLoad' => 1,
	'lockType' => 'S',
	'author' => 'Kasper Skaarhoj',
	'author_email' => 'kasper@typo3.com',
	'author_company' => 'Curby Soft Multimedia',
	'private' => 0,
	'download_password' => '',
	'version' => '1.0.14',	// Don't modify this! Managed automatically during upload to repository.
	'_md5_values_when_last_written' => 'a:17:{s:12:"ext_icon.gif";s:4:"87d7";s:17:"ext_localconf.php";s:4:"cdcb";s:14:"ext_tables.php";s:4:"3b6b";s:14:"ext_tables.sql";s:4:"1fcb";s:25:"ext_tables_static+adt.sql";s:4:"5809";s:17:"locallang_tca.php";s:4:"8ac5";s:17:"locallang_ttc.php";s:4:"5737";s:10:"readme.txt";s:4:"0d56";s:11:"tbl_cms.php";s:4:"c1bb";s:18:"tbl_tt_content.php";s:4:"493f";s:16:"layout/clear.gif";s:4:"cc11";s:15:"layout/conf.php";s:4:"badf";s:17:"layout/layout.gif";s:4:"9730";s:20:"layout/locallang.php";s:4:"2a28";s:24:"layout/locallang_mod.php";s:4:"13da";s:33:"web_info/class.tx_cms_webinfo.php";s:4:"1baa";s:22:"web_info/locallang.php";s:4:"00a2";}',
);

?>
<?php

########################################################################
# Extension Manager/Repository config file for ext: "impexp"
#
# Auto generated 20-01-2009 14:24
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Import/Export',
	'description' => 'Import and Export of records from TYPO3 in a custom serialized format (.T3D) for data exchange with other TYPO3 systems.',
	'category' => 'be',
	'shy' => 1,
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => 'app',
	'state' => 'beta',
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
	'version' => '0.2.2',
	'_md5_values_when_last_written' => 'a:140:{s:19:"class.tx_impexp.php";s:4:"aff1";s:29:"class.tx_impexp_clickmenu.php";s:4:"50d8";s:10:"export.gif";s:4:"3b59";s:14:"ext_tables.php";s:4:"5508";s:14:"ext_tables.sql";s:4:"78be";s:10:"import.gif";s:4:"374c";s:17:"locallang_csh.xml";s:4:"63eb";s:12:"doc/TODO.txt";s:4:"1967";s:20:"doc/.svn/all-wcprops";s:4:"d936";s:16:"doc/.svn/entries";s:4:"b888";s:15:"doc/.svn/format";s:4:"7c5a";s:36:"doc/.svn/text-base/TODO.txt.svn-base";s:4:"1967";s:36:"doc/.svn/prop-base/TODO.txt.svn-base";s:4:"3c71";s:16:".svn/all-wcprops";s:4:"b77c";s:12:".svn/entries";s:4:"c1c6";s:11:".svn/format";s:4:"7c5a";s:43:".svn/text-base/class.tx_impexp.php.svn-base";s:4:"aff1";s:53:".svn/text-base/class.tx_impexp_clickmenu.php.svn-base";s:4:"50d8";s:34:".svn/text-base/export.gif.svn-base";s:4:"3b59";s:38:".svn/text-base/ext_emconf.php.svn-base";s:4:"4c99";s:38:".svn/text-base/ext_tables.php.svn-base";s:4:"5508";s:38:".svn/text-base/ext_tables.sql.svn-base";s:4:"78be";s:34:".svn/text-base/import.gif.svn-base";s:4:"374c";s:41:".svn/text-base/locallang_csh.xml.svn-base";s:4:"63eb";s:43:".svn/prop-base/class.tx_impexp.php.svn-base";s:4:"3c71";s:53:".svn/prop-base/class.tx_impexp_clickmenu.php.svn-base";s:4:"3c71";s:34:".svn/prop-base/export.gif.svn-base";s:4:"c5ac";s:38:".svn/prop-base/ext_emconf.php.svn-base";s:4:"3c71";s:38:".svn/prop-base/ext_tables.php.svn-base";s:4:"3c71";s:38:".svn/prop-base/ext_tables.sql.svn-base";s:4:"a362";s:34:".svn/prop-base/import.gif.svn-base";s:4:"c5ac";s:41:".svn/prop-base/locallang_csh.xml.svn-base";s:4:"3c71";s:22:"cshimages/diffview.png";s:4:"ee3c";s:24:"cshimages/excludebox.png";s:4:"6ece";s:20:"cshimages/export.png";s:4:"e850";s:21:"cshimages/export1.png";s:4:"8e9a";s:21:"cshimages/export2.png";s:4:"ec12";s:20:"cshimages/extdep.png";s:4:"9349";s:24:"cshimages/fileformat.png";s:4:"eed0";s:21:"cshimages/htmlcss.png";s:4:"1f13";s:20:"cshimages/impexp.png";s:4:"eef2";s:25:"cshimages/impexp_misc.png";s:4:"f8c7";s:26:"cshimages/impexp_misc1.png";s:4:"80d3";s:26:"cshimages/impexp_misc2.png";s:4:"f530";s:26:"cshimages/impexp_misc3.png";s:4:"3c82";s:26:"cshimages/impexp_misc4.png";s:4:"2ea9";s:20:"cshimages/import.png";s:4:"35fc";s:31:"cshimages/import_selectfile.png";s:4:"7df9";s:22:"cshimages/metadata.png";s:4:"de32";s:25:"cshimages/pagetreecfg.png";s:4:"3ed8";s:20:"cshimages/phpext.png";s:4:"06c7";s:24:"cshimages/references.png";s:4:"ed7c";s:25:"cshimages/references1.png";s:4:"e817";s:25:"cshimages/references2.png";s:4:"960f";s:26:"cshimages/singlerecord.png";s:4:"885b";s:20:"cshimages/static.png";s:4:"db15";s:21:"cshimages/static1.png";s:4:"83ea";s:23:"cshimages/tablelist.png";s:4:"6b64";s:20:"cshimages/update.png";s:4:"e013";s:26:"cshimages/.svn/all-wcprops";s:4:"b9fd";s:22:"cshimages/.svn/entries";s:4:"80b2";s:21:"cshimages/.svn/format";s:4:"7c5a";s:46:"cshimages/.svn/text-base/diffview.png.svn-base";s:4:"ee3c";s:48:"cshimages/.svn/text-base/excludebox.png.svn-base";s:4:"6ece";s:44:"cshimages/.svn/text-base/export.png.svn-base";s:4:"e850";s:45:"cshimages/.svn/text-base/export1.png.svn-base";s:4:"8e9a";s:45:"cshimages/.svn/text-base/export2.png.svn-base";s:4:"ec12";s:44:"cshimages/.svn/text-base/extdep.png.svn-base";s:4:"9349";s:48:"cshimages/.svn/text-base/fileformat.png.svn-base";s:4:"eed0";s:45:"cshimages/.svn/text-base/htmlcss.png.svn-base";s:4:"1f13";s:44:"cshimages/.svn/text-base/impexp.png.svn-base";s:4:"eef2";s:49:"cshimages/.svn/text-base/impexp_misc.png.svn-base";s:4:"f8c7";s:50:"cshimages/.svn/text-base/impexp_misc1.png.svn-base";s:4:"80d3";s:50:"cshimages/.svn/text-base/impexp_misc2.png.svn-base";s:4:"f530";s:50:"cshimages/.svn/text-base/impexp_misc3.png.svn-base";s:4:"3c82";s:50:"cshimages/.svn/text-base/impexp_misc4.png.svn-base";s:4:"2ea9";s:44:"cshimages/.svn/text-base/import.png.svn-base";s:4:"35fc";s:55:"cshimages/.svn/text-base/import_selectfile.png.svn-base";s:4:"7df9";s:46:"cshimages/.svn/text-base/metadata.png.svn-base";s:4:"de32";s:49:"cshimages/.svn/text-base/pagetreecfg.png.svn-base";s:4:"3ed8";s:44:"cshimages/.svn/text-base/phpext.png.svn-base";s:4:"06c7";s:48:"cshimages/.svn/text-base/references.png.svn-base";s:4:"ed7c";s:49:"cshimages/.svn/text-base/references1.png.svn-base";s:4:"e817";s:49:"cshimages/.svn/text-base/references2.png.svn-base";s:4:"960f";s:50:"cshimages/.svn/text-base/singlerecord.png.svn-base";s:4:"885b";s:44:"cshimages/.svn/text-base/static.png.svn-base";s:4:"db15";s:45:"cshimages/.svn/text-base/static1.png.svn-base";s:4:"83ea";s:47:"cshimages/.svn/text-base/tablelist.png.svn-base";s:4:"6b64";s:44:"cshimages/.svn/text-base/update.png.svn-base";s:4:"e013";s:46:"cshimages/.svn/prop-base/diffview.png.svn-base";s:4:"c5ac";s:48:"cshimages/.svn/prop-base/excludebox.png.svn-base";s:4:"c5ac";s:44:"cshimages/.svn/prop-base/export.png.svn-base";s:4:"c5ac";s:45:"cshimages/.svn/prop-base/export1.png.svn-base";s:4:"c5ac";s:45:"cshimages/.svn/prop-base/export2.png.svn-base";s:4:"c5ac";s:44:"cshimages/.svn/prop-base/extdep.png.svn-base";s:4:"c5ac";s:48:"cshimages/.svn/prop-base/fileformat.png.svn-base";s:4:"c5ac";s:45:"cshimages/.svn/prop-base/htmlcss.png.svn-base";s:4:"c5ac";s:44:"cshimages/.svn/prop-base/impexp.png.svn-base";s:4:"c5ac";s:49:"cshimages/.svn/prop-base/impexp_misc.png.svn-base";s:4:"c5ac";s:50:"cshimages/.svn/prop-base/impexp_misc1.png.svn-base";s:4:"c5ac";s:50:"cshimages/.svn/prop-base/impexp_misc2.png.svn-base";s:4:"c5ac";s:50:"cshimages/.svn/prop-base/impexp_misc3.png.svn-base";s:4:"c5ac";s:50:"cshimages/.svn/prop-base/impexp_misc4.png.svn-base";s:4:"c5ac";s:44:"cshimages/.svn/prop-base/import.png.svn-base";s:4:"c5ac";s:55:"cshimages/.svn/prop-base/import_selectfile.png.svn-base";s:4:"c5ac";s:46:"cshimages/.svn/prop-base/metadata.png.svn-base";s:4:"c5ac";s:49:"cshimages/.svn/prop-base/pagetreecfg.png.svn-base";s:4:"c5ac";s:44:"cshimages/.svn/prop-base/phpext.png.svn-base";s:4:"c5ac";s:48:"cshimages/.svn/prop-base/references.png.svn-base";s:4:"c5ac";s:49:"cshimages/.svn/prop-base/references1.png.svn-base";s:4:"c5ac";s:49:"cshimages/.svn/prop-base/references2.png.svn-base";s:4:"c5ac";s:50:"cshimages/.svn/prop-base/singlerecord.png.svn-base";s:4:"c5ac";s:44:"cshimages/.svn/prop-base/static.png.svn-base";s:4:"c5ac";s:45:"cshimages/.svn/prop-base/static1.png.svn-base";s:4:"c5ac";s:47:"cshimages/.svn/prop-base/tablelist.png.svn-base";s:4:"c5ac";s:44:"cshimages/.svn/prop-base/update.png.svn-base";s:4:"c5ac";s:37:"modfunc1/class.tx_impexp_modfunc1.php";s:4:"060c";s:22:"modfunc1/locallang.xml";s:4:"3fe4";s:25:"modfunc1/.svn/all-wcprops";s:4:"0a6d";s:21:"modfunc1/.svn/entries";s:4:"1c39";s:20:"modfunc1/.svn/format";s:4:"7c5a";s:61:"modfunc1/.svn/text-base/class.tx_impexp_modfunc1.php.svn-base";s:4:"060c";s:46:"modfunc1/.svn/text-base/locallang.xml.svn-base";s:4:"3fe4";s:61:"modfunc1/.svn/prop-base/class.tx_impexp_modfunc1.php.svn-base";s:4:"3c71";s:46:"modfunc1/.svn/prop-base/locallang.xml.svn-base";s:4:"3c71";s:13:"app/clear.gif";s:4:"cc11";s:12:"app/conf.php";s:4:"720f";s:13:"app/index.php";s:4:"53fd";s:17:"app/locallang.xml";s:4:"a173";s:20:"app/.svn/all-wcprops";s:4:"1737";s:16:"app/.svn/entries";s:4:"70de";s:15:"app/.svn/format";s:4:"7c5a";s:37:"app/.svn/text-base/clear.gif.svn-base";s:4:"cc11";s:36:"app/.svn/text-base/conf.php.svn-base";s:4:"720f";s:37:"app/.svn/text-base/index.php.svn-base";s:4:"53fd";s:41:"app/.svn/text-base/locallang.xml.svn-base";s:4:"a173";s:37:"app/.svn/prop-base/clear.gif.svn-base";s:4:"c5ac";s:36:"app/.svn/prop-base/conf.php.svn-base";s:4:"3c71";s:37:"app/.svn/prop-base/index.php.svn-base";s:4:"3c71";s:41:"app/.svn/prop-base/locallang.xml.svn-base";s:4:"3c71";}',
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
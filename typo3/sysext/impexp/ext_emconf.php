<?php

########################################################################
# Extension Manager/Repository config file for ext "impexp".
#
# Auto generated 16-10-2012 14:06
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Import/Export',
	'description' => 'Import and Export of records from TYPO3 in a custom serialized format (.T3D) for data exchange with other TYPO3 systems.',
	'category' => 'be',
	'shy' => 1,
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => 'app',
	'doNotLoadInFE' => 1,
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
	'version' => '4.7.5',
	'_md5_values_when_last_written' => 'a:46:{s:19:"class.tx_impexp.php";s:4:"870e";s:29:"class.tx_impexp_clickmenu.php";s:4:"3cec";s:10:"export.gif";s:4:"e13a";s:16:"ext_autoload.php";s:4:"e335";s:12:"ext_icon.gif";s:4:"e9df";s:14:"ext_tables.php";s:4:"385f";s:14:"ext_tables.sql";s:4:"c4c7";s:10:"import.gif";s:4:"374c";s:17:"locallang_csh.xlf";s:4:"b9a8";s:20:"locallang_csh_45.xlf";s:4:"c1c3";s:13:"app/clear.gif";s:4:"cc11";s:12:"app/conf.php";s:4:"6593";s:13:"app/index.php";s:4:"ef33";s:17:"app/locallang.xlf";s:4:"3408";s:17:"app/template.html";s:4:"29f0";s:22:"cshimages/diffview.png";s:4:"32b9";s:24:"cshimages/excludebox.png";s:4:"40c0";s:20:"cshimages/export.png";s:4:"a96f";s:21:"cshimages/export1.png";s:4:"cf47";s:21:"cshimages/export2.png";s:4:"afec";s:20:"cshimages/extdep.png";s:4:"3e6b";s:24:"cshimages/fileformat.png";s:4:"7acd";s:21:"cshimages/htmlcss.png";s:4:"a407";s:20:"cshimages/impexp.png";s:4:"49b3";s:25:"cshimages/impexp_misc.png";s:4:"46e4";s:26:"cshimages/impexp_misc1.png";s:4:"f303";s:26:"cshimages/impexp_misc2.png";s:4:"e6a5";s:26:"cshimages/impexp_misc3.png";s:4:"388c";s:26:"cshimages/impexp_misc4.png";s:4:"ed04";s:20:"cshimages/import.png";s:4:"83c4";s:31:"cshimages/import_selectfile.png";s:4:"0bf7";s:22:"cshimages/metadata.png";s:4:"e462";s:25:"cshimages/pagetreecfg.png";s:4:"bc11";s:20:"cshimages/phpext.png";s:4:"17ca";s:24:"cshimages/references.png";s:4:"b643";s:25:"cshimages/references1.png";s:4:"5661";s:25:"cshimages/references2.png";s:4:"004b";s:26:"cshimages/singlerecord.png";s:4:"c010";s:20:"cshimages/static.png";s:4:"8724";s:21:"cshimages/static1.png";s:4:"8668";s:23:"cshimages/tablelist.png";s:4:"7fa4";s:20:"cshimages/update.png";s:4:"7a80";s:12:"doc/TODO.txt";s:4:"1967";s:37:"modfunc1/class.tx_impexp_modfunc1.php";s:4:"7f4a";s:22:"modfunc1/locallang.xlf";s:4:"fb28";s:29:"task/class.tx_impexp_task.php";s:4:"bede";}',
	'constraints' => array(
		'depends' => array(
			'php' => '5.3.0-0.0.0',
			'typo3' => '4.7.0-0.0.0',
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
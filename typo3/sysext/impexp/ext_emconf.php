<?php

########################################################################
# Extension Manager/Repository config file for ext "impexp".
#
# Auto generated 22-06-2010 13:47
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
	'state' => 'beta',
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
	'version' => '0.3.0',
	'_md5_values_when_last_written' => 'a:44:{s:19:"class.tx_impexp.php";s:4:"4186";s:29:"class.tx_impexp_clickmenu.php";s:4:"4b8c";s:10:"export.gif";s:4:"3b59";s:16:"ext_autoload.php";s:4:"e335";s:14:"ext_tables.php";s:4:"c8b4";s:14:"ext_tables.sql";s:4:"c4c7";s:10:"import.gif";s:4:"374c";s:17:"locallang_csh.xml";s:4:"ec50";s:13:"app/clear.gif";s:4:"cc11";s:12:"app/conf.php";s:4:"6593";s:13:"app/index.php";s:4:"6290";s:17:"app/locallang.xml";s:4:"a173";s:17:"app/template.html";s:4:"29f0";s:22:"cshimages/diffview.png";s:4:"11d2";s:24:"cshimages/excludebox.png";s:4:"b12f";s:20:"cshimages/export.png";s:4:"5ee0";s:21:"cshimages/export1.png";s:4:"e9a5";s:21:"cshimages/export2.png";s:4:"2e7c";s:20:"cshimages/extdep.png";s:4:"97fc";s:24:"cshimages/fileformat.png";s:4:"d445";s:21:"cshimages/htmlcss.png";s:4:"9458";s:20:"cshimages/impexp.png";s:4:"470c";s:25:"cshimages/impexp_misc.png";s:4:"84f5";s:26:"cshimages/impexp_misc1.png";s:4:"9b11";s:26:"cshimages/impexp_misc2.png";s:4:"109e";s:26:"cshimages/impexp_misc3.png";s:4:"28c6";s:26:"cshimages/impexp_misc4.png";s:4:"7c31";s:20:"cshimages/import.png";s:4:"3758";s:31:"cshimages/import_selectfile.png";s:4:"5b16";s:22:"cshimages/metadata.png";s:4:"6e57";s:25:"cshimages/pagetreecfg.png";s:4:"0b2e";s:20:"cshimages/phpext.png";s:4:"f132";s:24:"cshimages/references.png";s:4:"3f2e";s:25:"cshimages/references1.png";s:4:"2ac1";s:25:"cshimages/references2.png";s:4:"8c7c";s:26:"cshimages/singlerecord.png";s:4:"c0a6";s:20:"cshimages/static.png";s:4:"9537";s:21:"cshimages/static1.png";s:4:"545d";s:23:"cshimages/tablelist.png";s:4:"e5cb";s:20:"cshimages/update.png";s:4:"4428";s:12:"doc/TODO.txt";s:4:"1967";s:37:"modfunc1/class.tx_impexp_modfunc1.php";s:4:"40ab";s:22:"modfunc1/locallang.xml";s:4:"3fe4";s:29:"task/class.tx_impexp_task.php";s:4:"827d";}',
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
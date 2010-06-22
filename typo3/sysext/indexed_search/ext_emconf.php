<?php

########################################################################
# Extension Manager/Repository config file for ext "indexed_search".
#
# Auto generated 22-06-2010 13:08
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Indexed Search Engine',
	'description' => 'Indexed Search Engine for TYPO3 pages, PDF-files, Word-files, HTML and text files. Provides a backend module for statistics of the indexer and a frontend plugin. Documentation can be found in the extension "doc_indexed_search".',
	'category' => 'plugin',
	'shy' => 0,
	'dependencies' => 'cms',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => 'mod,cli',
	'state' => 'stable',
	'internal' => 1,
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author' => 'Kasper Skaarhoj',
	'author_email' => 'kasperYYYY@typo3.com',
	'author_company' => 'Curby Soft Multimedia',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'version' => '2.12.0',
	'_md5_values_when_last_written' => 'a:58:{s:9:"ChangeLog";s:4:"7479";s:17:"class.crawler.php";s:4:"fabe";s:25:"class.doublemetaphone.php";s:4:"28e4";s:25:"class.external_parser.php";s:4:"b3f9";s:17:"class.indexer.php";s:4:"bf71";s:15:"class.lexer.php";s:4:"72fd";s:21:"ext_conf_template.txt";s:4:"0c64";s:12:"ext_icon.gif";s:4:"4cbf";s:17:"ext_localconf.php";s:4:"4c42";s:14:"ext_tables.php";s:4:"3a84";s:14:"ext_tables.sql";s:4:"7084";s:28:"ext_typoscript_editorcfg.txt";s:4:"0a34";s:24:"ext_typoscript_setup.txt";s:4:"9b5f";s:13:"locallang.xml";s:4:"cd0c";s:26:"locallang_csh_indexcfg.xml";s:4:"f4f3";s:16:"locallang_db.xml";s:4:"f142";s:7:"tca.php";s:4:"f24a";s:12:"cli/conf.php";s:4:"19fe";s:21:"cli/indexer_cli.phpsh";s:4:"d236";s:14:"doc/README.txt";s:4:"a737";s:12:"doc/TODO.txt";s:4:"c804";s:29:"example/class.crawlerhook.php";s:4:"626a";s:24:"example/class.pihook.php";s:4:"bf31";s:46:"hooks/class.tx_indexedsearch_tslib_fe_hook.php";s:4:"c27f";s:13:"mod/clear.gif";s:4:"cc11";s:12:"mod/conf.php";s:4:"9062";s:13:"mod/index.php";s:4:"bc3a";s:15:"mod/isearch.gif";s:4:"4cbf";s:21:"mod/locallang_mod.xml";s:4:"1624";s:21:"mod/mod_template.html";s:4:"a7f2";s:44:"modfunc1/class.tx_indexedsearch_modfunc1.php";s:4:"d46b";s:49:"modfunc1/class.tx_indexedsearch_modfunc1.php.orig";s:4:"8d28";s:22:"modfunc1/locallang.xml";s:4:"4806";s:44:"modfunc2/class.tx_indexedsearch_modfunc2.php";s:4:"81bb";s:22:"modfunc2/locallang.xml";s:4:"a889";s:29:"pi/class.tx_indexedsearch.php";s:4:"0957";s:21:"pi/considerations.txt";s:4:"e3df";s:22:"pi/indexed_search.tmpl";s:4:"4b28";s:16:"pi/locallang.xml";s:4:"4f34";s:20:"pi/template_css.tmpl";s:4:"0df0";s:14:"pi/res/csv.gif";s:4:"e413";s:14:"pi/res/doc.gif";s:4:"0975";s:15:"pi/res/html.gif";s:4:"5647";s:14:"pi/res/jpg.gif";s:4:"23ac";s:17:"pi/res/locked.gif";s:4:"c212";s:16:"pi/res/pages.gif";s:4:"1923";s:14:"pi/res/pdf.gif";s:4:"9451";s:14:"pi/res/pps.gif";s:4:"926b";s:14:"pi/res/ppt.gif";s:4:"ada5";s:14:"pi/res/rtf.gif";s:4:"f660";s:14:"pi/res/sxc.gif";s:4:"00a6";s:14:"pi/res/sxi.gif";s:4:"ef83";s:14:"pi/res/sxw.gif";s:4:"4a8f";s:14:"pi/res/tif.gif";s:4:"533b";s:14:"pi/res/txt.gif";s:4:"c576";s:14:"pi/res/xls.gif";s:4:"4a22";s:14:"pi/res/xml.gif";s:4:"2e7b";s:38:"tests/tx_indexedsearch_indexerTest.php";s:4:"22ed";}',
	'constraints' => array(
		'depends' => array(
			'cms' => '',
			'php' => '5.1.0-0.0.0',
			'typo3' => '4.4.0-0.0.0',
		),
		'conflicts' => array(
		),
		'suggests' => array(
			'doc_indexed_search' => '',
		),
	),
	'suggests' => array(
	),
);

?>
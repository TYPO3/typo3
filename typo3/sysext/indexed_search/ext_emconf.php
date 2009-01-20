<?php

########################################################################
# Extension Manager/Repository config file for ext: "indexed_search"
#
# Auto generated 23-04-2008 10:23
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
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
	'version' => '2.11.1',
	'_md5_values_when_last_written' => 'a:54:{s:9:"ChangeLog";s:4:"b02b";s:17:"class.crawler.php";s:4:"df89";s:25:"class.doublemetaphone.php";s:4:"8d81";s:25:"class.external_parser.php";s:4:"7ed5";s:17:"class.indexer.php";s:4:"044d";s:15:"class.lexer.php";s:4:"ac05";s:21:"ext_conf_template.txt";s:4:"0c64";s:12:"ext_icon.gif";s:4:"4cbf";s:17:"ext_localconf.php";s:4:"732c";s:14:"ext_tables.php";s:4:"9bda";s:14:"ext_tables.sql";s:4:"f9e0";s:28:"ext_typoscript_editorcfg.txt";s:4:"0a34";s:24:"ext_typoscript_setup.txt";s:4:"c2e7";s:13:"locallang.xml";s:4:"0a76";s:26:"locallang_csh_indexcfg.xml";s:4:"f4f3";s:16:"locallang_db.xml";s:4:"2c55";s:7:"tca.php";s:4:"8991";s:29:"example/class.crawlerhook.php";s:4:"0ce8";s:24:"example/class.pihook.php";s:4:"e221";s:12:"cli/conf.php";s:4:"bbcd";s:21:"cli/indexer_cli.phpsh";s:4:"d236";s:44:"modfunc2/class.tx_indexedsearch_modfunc2.php";s:4:"b531";s:22:"modfunc2/locallang.xml";s:4:"a889";s:44:"modfunc1/class.tx_indexedsearch_modfunc1.php";s:4:"9fb3";s:22:"modfunc1/locallang.xml";s:4:"4806";s:29:"pi/class.tx_indexedsearch.php";s:4:"56ef";s:21:"pi/considerations.txt";s:4:"e3df";s:22:"pi/indexed_search.tmpl";s:4:"7ada";s:16:"pi/locallang.xml";s:4:"f62f";s:20:"pi/template_css.tmpl";s:4:"a2e2";s:14:"pi/res/csv.gif";s:4:"e413";s:14:"pi/res/doc.gif";s:4:"0975";s:15:"pi/res/html.gif";s:4:"5647";s:14:"pi/res/jpg.gif";s:4:"23ac";s:17:"pi/res/locked.gif";s:4:"c212";s:16:"pi/res/pages.gif";s:4:"1923";s:14:"pi/res/pdf.gif";s:4:"9451";s:14:"pi/res/pps.gif";s:4:"926b";s:14:"pi/res/ppt.gif";s:4:"ada5";s:14:"pi/res/rtf.gif";s:4:"f660";s:14:"pi/res/sxc.gif";s:4:"00a6";s:14:"pi/res/sxi.gif";s:4:"ef83";s:14:"pi/res/sxw.gif";s:4:"4a8f";s:14:"pi/res/tif.gif";s:4:"533b";s:14:"pi/res/txt.gif";s:4:"c576";s:14:"pi/res/xls.gif";s:4:"4a22";s:14:"pi/res/xml.gif";s:4:"2e7b";s:13:"mod/clear.gif";s:4:"cc11";s:12:"mod/conf.php";s:4:"9062";s:13:"mod/index.php";s:4:"4dbd";s:15:"mod/isearch.gif";s:4:"4cbf";s:21:"mod/locallang_mod.xml";s:4:"1624";s:14:"doc/README.txt";s:4:"a737";s:12:"doc/TODO.txt";s:4:"c804";}',
	'constraints' => array(
		'depends' => array(
			'cms' => '',
			'php' => '5.1.0-0.0.0',
			'typo3' => '4.2.0-4.2.99',
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
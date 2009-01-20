<?php

########################################################################
# Extension Manager/Repository config file for ext: "css_styled_content"
#
# Auto generated 20-01-2009 14:23
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'CSS styled content',
	'description' => 'Contains configuration for CSS content-rendering of the table "tt_content". This is meant as a modern substitute for the classic "content (default)" template which was based more on <font>-tags, while this is pure CSS. It is intended to work with all modern browsers (which excludes the NS4 series).',
	'category' => 'fe',
	'shy' => 0,
	'dependencies' => 'cms',
	'conflicts' => '',
	'priority' => 'top',
	'loadOrder' => '',
	'module' => '',
	'state' => 'beta',
	'internal' => 1,
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author' => 'Kasper Skårhøj',
	'author_email' => 'kasperYYYY@typo3.com',
	'author_company' => 'Curby Soft Multimedia',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'version' => '0.3.1',
	'_md5_values_when_last_written' => 'a:77:{s:21:"ext_conf_template.txt";s:4:"fe7c";s:12:"ext_icon.gif";s:4:"1845";s:17:"ext_localconf.php";s:4:"10e8";s:14:"ext_tables.php";s:4:"e8f8";s:15:"flexform_ds.xml";s:4:"6f8f";s:16:"locallang_db.xml";s:4:"0284";s:16:"pageTSconfig.txt";s:4:"1072";s:12:"doc/TODO.txt";s:4:"6534";s:14:"doc/manual.sxw";s:4:"0179";s:20:"doc/.svn/all-wcprops";s:4:"fa89";s:16:"doc/.svn/entries";s:4:"b31d";s:15:"doc/.svn/format";s:4:"7c5a";s:36:"doc/.svn/text-base/TODO.txt.svn-base";s:4:"6534";s:38:"doc/.svn/text-base/manual.sxw.svn-base";s:4:"0179";s:36:"doc/.svn/prop-base/TODO.txt.svn-base";s:4:"3c71";s:38:"doc/.svn/prop-base/manual.sxw.svn-base";s:4:"c5ac";s:16:".svn/all-wcprops";s:4:"9327";s:12:".svn/entries";s:4:"3173";s:11:".svn/format";s:4:"7c5a";s:45:".svn/text-base/ext_conf_template.txt.svn-base";s:4:"3193";s:38:".svn/text-base/ext_emconf.php.svn-base";s:4:"5f4c";s:36:".svn/text-base/ext_icon.gif.svn-base";s:4:"1845";s:41:".svn/text-base/ext_localconf.php.svn-base";s:4:"e9ef";s:38:".svn/text-base/ext_tables.php.svn-base";s:4:"8a25";s:39:".svn/text-base/flexform_ds.xml.svn-base";s:4:"6f8f";s:40:".svn/text-base/locallang_db.xml.svn-base";s:4:"0284";s:40:".svn/text-base/pageTSconfig.txt.svn-base";s:4:"f126";s:45:".svn/prop-base/ext_conf_template.txt.svn-base";s:4:"3c71";s:38:".svn/prop-base/ext_emconf.php.svn-base";s:4:"3c71";s:36:".svn/prop-base/ext_icon.gif.svn-base";s:4:"c5ac";s:41:".svn/prop-base/ext_localconf.php.svn-base";s:4:"3c71";s:38:".svn/prop-base/ext_tables.php.svn-base";s:4:"3c71";s:39:".svn/prop-base/flexform_ds.xml.svn-base";s:4:"685f";s:40:".svn/prop-base/locallang_db.xml.svn-base";s:4:"685f";s:40:".svn/prop-base/pageTSconfig.txt.svn-base";s:4:"3c71";s:20:"static/constants.txt";s:4:"2aac";s:20:"static/editorcfg.txt";s:4:"f1f4";s:16:"static/setup.txt";s:4:"f98d";s:23:"static/.svn/all-wcprops";s:4:"812e";s:19:"static/.svn/entries";s:4:"9e54";s:18:"static/.svn/format";s:4:"7c5a";s:44:"static/.svn/text-base/constants.txt.svn-base";s:4:"d5de";s:44:"static/.svn/text-base/editorcfg.txt.svn-base";s:4:"4fd0";s:40:"static/.svn/text-base/setup.txt.svn-base";s:4:"47a7";s:44:"static/.svn/prop-base/constants.txt.svn-base";s:4:"3c71";s:44:"static/.svn/prop-base/editorcfg.txt.svn-base";s:4:"3c71";s:40:"static/.svn/prop-base/setup.txt.svn-base";s:4:"3c71";s:37:"pi1/class.tx_cssstyledcontent_pi1.php";s:4:"be47";s:17:"pi1/locallang.xml";s:4:"974c";s:20:"pi1/.svn/all-wcprops";s:4:"b2c0";s:16:"pi1/.svn/entries";s:4:"ff5f";s:15:"pi1/.svn/format";s:4:"7c5a";s:61:"pi1/.svn/text-base/class.tx_cssstyledcontent_pi1.php.svn-base";s:4:"0abf";s:41:"pi1/.svn/text-base/locallang.xml.svn-base";s:4:"974c";s:61:"pi1/.svn/prop-base/class.tx_cssstyledcontent_pi1.php.svn-base";s:4:"3c71";s:41:"pi1/.svn/prop-base/locallang.xml.svn-base";s:4:"3c71";s:15:"css/example.css";s:4:"9804";s:24:"css/example_outlines.css";s:4:"e656";s:14:"css/readme.txt";s:4:"ee9d";s:31:"css/img/background_gradient.gif";s:4:"45d7";s:28:"css/img/red_arrow_bullet.gif";s:4:"82d6";s:24:"css/img/.svn/all-wcprops";s:4:"ddec";s:20:"css/img/.svn/entries";s:4:"4015";s:19:"css/img/.svn/format";s:4:"7c5a";s:55:"css/img/.svn/text-base/background_gradient.gif.svn-base";s:4:"45d7";s:52:"css/img/.svn/text-base/red_arrow_bullet.gif.svn-base";s:4:"82d6";s:55:"css/img/.svn/prop-base/background_gradient.gif.svn-base";s:4:"c5ac";s:52:"css/img/.svn/prop-base/red_arrow_bullet.gif.svn-base";s:4:"c5ac";s:20:"css/.svn/all-wcprops";s:4:"ad5d";s:16:"css/.svn/entries";s:4:"134d";s:15:"css/.svn/format";s:4:"7c5a";s:39:"css/.svn/text-base/example.css.svn-base";s:4:"9804";s:48:"css/.svn/text-base/example_outlines.css.svn-base";s:4:"e656";s:38:"css/.svn/text-base/readme.txt.svn-base";s:4:"ee9d";s:39:"css/.svn/prop-base/example.css.svn-base";s:4:"3c71";s:48:"css/.svn/prop-base/example_outlines.css.svn-base";s:4:"3c71";s:38:"css/.svn/prop-base/readme.txt.svn-base";s:4:"3c71";}',
	'constraints' => array(
		'depends' => array(
			'cms' => '',
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
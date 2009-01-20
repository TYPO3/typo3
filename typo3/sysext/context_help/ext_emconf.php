<?php

########################################################################
# Extension Manager/Repository config file for ext: "context_help"
#
# Auto generated 20-01-2009 14:23
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Context Sensitive Help',
	'description' => 'Provides context sensitive help to tables, fields and modules in the system languages.',
	'category' => 'be',
	'shy' => 1,
	'dependencies' => 'cms',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
	'state' => 'beta',
	'internal' => 1,
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
	'version' => '1.0.9',
	'_md5_values_when_last_written' => 'a:101:{s:12:"ext_icon.gif";s:4:"d050";s:14:"ext_tables.php";s:4:"4bcf";s:27:"locallang_csh_fe_groups.xml";s:4:"7ad1";s:26:"locallang_csh_fe_users.xml";s:4:"ef2e";s:23:"locallang_csh_pages.xml";s:4:"e0df";s:26:"locallang_csh_pageslol.xml";s:4:"f075";s:27:"locallang_csh_statictpl.xml";s:4:"4890";s:27:"locallang_csh_sysdomain.xml";s:4:"34c9";s:25:"locallang_csh_systmpl.xml";s:4:"ea4d";s:27:"locallang_csh_ttcontent.xml";s:4:"d3f5";s:16:".svn/all-wcprops";s:4:"24c0";s:12:".svn/entries";s:4:"11a8";s:11:".svn/format";s:4:"7c5a";s:38:".svn/text-base/ext_emconf.php.svn-base";s:4:"4e26";s:36:".svn/text-base/ext_icon.gif.svn-base";s:4:"d050";s:38:".svn/text-base/ext_tables.php.svn-base";s:4:"4bcf";s:51:".svn/text-base/locallang_csh_fe_groups.xml.svn-base";s:4:"7ad1";s:50:".svn/text-base/locallang_csh_fe_users.xml.svn-base";s:4:"ef2e";s:47:".svn/text-base/locallang_csh_pages.xml.svn-base";s:4:"e0df";s:50:".svn/text-base/locallang_csh_pageslol.xml.svn-base";s:4:"f075";s:51:".svn/text-base/locallang_csh_statictpl.xml.svn-base";s:4:"4890";s:51:".svn/text-base/locallang_csh_sysdomain.xml.svn-base";s:4:"34c9";s:49:".svn/text-base/locallang_csh_systmpl.xml.svn-base";s:4:"ea4d";s:51:".svn/text-base/locallang_csh_ttcontent.xml.svn-base";s:4:"d3f5";s:38:".svn/prop-base/ext_emconf.php.svn-base";s:4:"3c71";s:36:".svn/prop-base/ext_icon.gif.svn-base";s:4:"c5ac";s:38:".svn/prop-base/ext_tables.php.svn-base";s:4:"3c71";s:51:".svn/prop-base/locallang_csh_fe_groups.xml.svn-base";s:4:"3c71";s:50:".svn/prop-base/locallang_csh_fe_users.xml.svn-base";s:4:"3c71";s:47:".svn/prop-base/locallang_csh_pages.xml.svn-base";s:4:"3c71";s:50:".svn/prop-base/locallang_csh_pageslol.xml.svn-base";s:4:"3c71";s:51:".svn/prop-base/locallang_csh_statictpl.xml.svn-base";s:4:"3c71";s:51:".svn/prop-base/locallang_csh_sysdomain.xml.svn-base";s:4:"3c71";s:49:".svn/prop-base/locallang_csh_systmpl.xml.svn-base";s:4:"3c71";s:51:".svn/prop-base/locallang_csh_ttcontent.xml.svn-base";s:4:"3c71";s:24:"cshimages/fegroups_3.png";s:4:"5596";s:24:"cshimages/fegroups_4.png";s:4:"1023";s:23:"cshimages/feusers_1.png";s:4:"219b";s:23:"cshimages/feusers_2.png";s:4:"1b35";s:25:"cshimages/hidden_page.gif";s:4:"b7d1";s:25:"cshimages/hidden_page.png";s:4:"f56a";s:27:"cshimages/page_shortcut.gif";s:4:"3dc0";s:27:"cshimages/page_shortcut.png";s:4:"06fd";s:21:"cshimages/pages_1.png";s:4:"f2dc";s:21:"cshimages/pages_2.png";s:4:"20d1";s:20:"cshimages/static.png";s:4:"7d17";s:25:"cshimages/systemplate.png";s:4:"7f27";s:26:"cshimages/systemplate1.png";s:4:"18a9";s:26:"cshimages/systemplate2.png";s:4:"80a5";s:25:"cshimages/ttcontent_1.png";s:4:"2b09";s:25:"cshimages/ttcontent_2.png";s:4:"8865";s:25:"cshimages/ttcontent_3.png";s:4:"4100";s:25:"cshimages/ttcontent_4.png";s:4:"e572";s:25:"cshimages/ttcontent_5.png";s:4:"211e";s:25:"cshimages/ttcontent_6.png";s:4:"f075";s:25:"cshimages/ttcontent_7.png";s:4:"05e7";s:26:"cshimages/.svn/all-wcprops";s:4:"3710";s:22:"cshimages/.svn/entries";s:4:"cd6a";s:21:"cshimages/.svn/format";s:4:"7c5a";s:48:"cshimages/.svn/text-base/fegroups_3.png.svn-base";s:4:"5596";s:48:"cshimages/.svn/text-base/fegroups_4.png.svn-base";s:4:"1023";s:47:"cshimages/.svn/text-base/feusers_1.png.svn-base";s:4:"219b";s:47:"cshimages/.svn/text-base/feusers_2.png.svn-base";s:4:"1b35";s:49:"cshimages/.svn/text-base/hidden_page.gif.svn-base";s:4:"b7d1";s:49:"cshimages/.svn/text-base/hidden_page.png.svn-base";s:4:"f56a";s:51:"cshimages/.svn/text-base/page_shortcut.gif.svn-base";s:4:"3dc0";s:51:"cshimages/.svn/text-base/page_shortcut.png.svn-base";s:4:"06fd";s:45:"cshimages/.svn/text-base/pages_1.png.svn-base";s:4:"f2dc";s:45:"cshimages/.svn/text-base/pages_2.png.svn-base";s:4:"20d1";s:44:"cshimages/.svn/text-base/static.png.svn-base";s:4:"7d17";s:49:"cshimages/.svn/text-base/systemplate.png.svn-base";s:4:"7f27";s:50:"cshimages/.svn/text-base/systemplate1.png.svn-base";s:4:"18a9";s:50:"cshimages/.svn/text-base/systemplate2.png.svn-base";s:4:"80a5";s:49:"cshimages/.svn/text-base/ttcontent_1.png.svn-base";s:4:"2b09";s:49:"cshimages/.svn/text-base/ttcontent_2.png.svn-base";s:4:"8865";s:49:"cshimages/.svn/text-base/ttcontent_3.png.svn-base";s:4:"4100";s:49:"cshimages/.svn/text-base/ttcontent_4.png.svn-base";s:4:"e572";s:49:"cshimages/.svn/text-base/ttcontent_5.png.svn-base";s:4:"211e";s:49:"cshimages/.svn/text-base/ttcontent_6.png.svn-base";s:4:"f075";s:49:"cshimages/.svn/text-base/ttcontent_7.png.svn-base";s:4:"05e7";s:48:"cshimages/.svn/prop-base/fegroups_3.png.svn-base";s:4:"c5ac";s:48:"cshimages/.svn/prop-base/fegroups_4.png.svn-base";s:4:"c5ac";s:47:"cshimages/.svn/prop-base/feusers_1.png.svn-base";s:4:"c5ac";s:47:"cshimages/.svn/prop-base/feusers_2.png.svn-base";s:4:"c5ac";s:49:"cshimages/.svn/prop-base/hidden_page.gif.svn-base";s:4:"c5ac";s:49:"cshimages/.svn/prop-base/hidden_page.png.svn-base";s:4:"c5ac";s:51:"cshimages/.svn/prop-base/page_shortcut.gif.svn-base";s:4:"c5ac";s:51:"cshimages/.svn/prop-base/page_shortcut.png.svn-base";s:4:"c5ac";s:45:"cshimages/.svn/prop-base/pages_1.png.svn-base";s:4:"c5ac";s:45:"cshimages/.svn/prop-base/pages_2.png.svn-base";s:4:"c5ac";s:44:"cshimages/.svn/prop-base/static.png.svn-base";s:4:"c5ac";s:49:"cshimages/.svn/prop-base/systemplate.png.svn-base";s:4:"c5ac";s:50:"cshimages/.svn/prop-base/systemplate1.png.svn-base";s:4:"c5ac";s:50:"cshimages/.svn/prop-base/systemplate2.png.svn-base";s:4:"c5ac";s:49:"cshimages/.svn/prop-base/ttcontent_1.png.svn-base";s:4:"c5ac";s:49:"cshimages/.svn/prop-base/ttcontent_2.png.svn-base";s:4:"c5ac";s:49:"cshimages/.svn/prop-base/ttcontent_3.png.svn-base";s:4:"c5ac";s:49:"cshimages/.svn/prop-base/ttcontent_4.png.svn-base";s:4:"c5ac";s:49:"cshimages/.svn/prop-base/ttcontent_5.png.svn-base";s:4:"c5ac";s:49:"cshimages/.svn/prop-base/ttcontent_6.png.svn-base";s:4:"c5ac";s:49:"cshimages/.svn/prop-base/ttcontent_7.png.svn-base";s:4:"c5ac";}',
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
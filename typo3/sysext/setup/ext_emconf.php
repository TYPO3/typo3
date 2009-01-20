<?php

########################################################################
# Extension Manager/Repository config file for ext: "setup"
#
# Auto generated 20-01-2009 14:26
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'User>Setup',
	'description' => 'Allows users to edit a limited set of options for their user profile, eg. preferred language and their name and email address.',
	'category' => 'module',
	'shy' => 1,
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => 'mod',
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
	'version' => '0.0.16',
	'_md5_values_when_last_written' => 'a:80:{s:12:"ext_icon.gif";s:4:"6187";s:14:"ext_tables.php";s:4:"cc19";s:21:"locallang_csh_mod.xml";s:4:"a977";s:16:".svn/all-wcprops";s:4:"eb70";s:12:".svn/entries";s:4:"48a9";s:11:".svn/format";s:4:"7c5a";s:38:".svn/text-base/ext_emconf.php.svn-base";s:4:"852c";s:36:".svn/text-base/ext_icon.gif.svn-base";s:4:"6187";s:38:".svn/text-base/ext_tables.php.svn-base";s:4:"cc19";s:45:".svn/text-base/locallang_csh_mod.xml.svn-base";s:4:"a977";s:38:".svn/prop-base/ext_emconf.php.svn-base";s:4:"3c71";s:36:".svn/prop-base/ext_icon.gif.svn-base";s:4:"c5ac";s:38:".svn/prop-base/ext_tables.php.svn-base";s:4:"3c71";s:45:".svn/prop-base/locallang_csh_mod.xml.svn-base";s:4:"3c71";s:13:"mod/clear.gif";s:4:"cc11";s:12:"mod/conf.php";s:4:"a8bf";s:13:"mod/index.php";s:4:"a61b";s:17:"mod/locallang.xml";s:4:"799d";s:21:"mod/locallang_mod.xml";s:4:"e2a3";s:13:"mod/setup.gif";s:4:"6187";s:20:"mod/.svn/all-wcprops";s:4:"68ee";s:16:"mod/.svn/entries";s:4:"a09c";s:15:"mod/.svn/format";s:4:"7c5a";s:37:"mod/.svn/text-base/clear.gif.svn-base";s:4:"cc11";s:36:"mod/.svn/text-base/conf.php.svn-base";s:4:"a8bf";s:37:"mod/.svn/text-base/index.php.svn-base";s:4:"a61b";s:41:"mod/.svn/text-base/locallang.xml.svn-base";s:4:"799d";s:45:"mod/.svn/text-base/locallang_mod.xml.svn-base";s:4:"e2a3";s:37:"mod/.svn/text-base/setup.gif.svn-base";s:4:"6187";s:37:"mod/.svn/prop-base/clear.gif.svn-base";s:4:"c5ac";s:36:"mod/.svn/prop-base/conf.php.svn-base";s:4:"3c71";s:37:"mod/.svn/prop-base/index.php.svn-base";s:4:"3c71";s:41:"mod/.svn/prop-base/locallang.xml.svn-base";s:4:"3c71";s:45:"mod/.svn/prop-base/locallang_mod.xml.svn-base";s:4:"3c71";s:37:"mod/.svn/prop-base/setup.gif.svn-base";s:4:"c5ac";s:18:"cshimages/lang.png";s:4:"237d";s:17:"cshimages/rte.png";s:4:"aaeb";s:20:"cshimages/setup1.png";s:4:"7e74";s:21:"cshimages/setup10.png";s:4:"4f5b";s:21:"cshimages/setup11.png";s:4:"2210";s:21:"cshimages/setup12.png";s:4:"7976";s:20:"cshimages/setup2.png";s:4:"8c62";s:20:"cshimages/setup3.png";s:4:"b6cf";s:20:"cshimages/setup4.png";s:4:"57e1";s:20:"cshimages/setup5.png";s:4:"7623";s:20:"cshimages/setup6.png";s:4:"2c85";s:20:"cshimages/setup7.png";s:4:"8543";s:20:"cshimages/setup8.png";s:4:"c12a";s:20:"cshimages/setup9.png";s:4:"42c9";s:26:"cshimages/.svn/all-wcprops";s:4:"e1ca";s:22:"cshimages/.svn/entries";s:4:"8fbe";s:21:"cshimages/.svn/format";s:4:"7c5a";s:42:"cshimages/.svn/text-base/lang.png.svn-base";s:4:"237d";s:41:"cshimages/.svn/text-base/rte.png.svn-base";s:4:"aaeb";s:44:"cshimages/.svn/text-base/setup1.png.svn-base";s:4:"7e74";s:45:"cshimages/.svn/text-base/setup10.png.svn-base";s:4:"4f5b";s:45:"cshimages/.svn/text-base/setup11.png.svn-base";s:4:"2210";s:45:"cshimages/.svn/text-base/setup12.png.svn-base";s:4:"7976";s:44:"cshimages/.svn/text-base/setup2.png.svn-base";s:4:"8c62";s:44:"cshimages/.svn/text-base/setup3.png.svn-base";s:4:"b6cf";s:44:"cshimages/.svn/text-base/setup4.png.svn-base";s:4:"57e1";s:44:"cshimages/.svn/text-base/setup5.png.svn-base";s:4:"7623";s:44:"cshimages/.svn/text-base/setup6.png.svn-base";s:4:"2c85";s:44:"cshimages/.svn/text-base/setup7.png.svn-base";s:4:"8543";s:44:"cshimages/.svn/text-base/setup8.png.svn-base";s:4:"c12a";s:44:"cshimages/.svn/text-base/setup9.png.svn-base";s:4:"42c9";s:42:"cshimages/.svn/prop-base/lang.png.svn-base";s:4:"c5ac";s:41:"cshimages/.svn/prop-base/rte.png.svn-base";s:4:"c5ac";s:44:"cshimages/.svn/prop-base/setup1.png.svn-base";s:4:"c5ac";s:45:"cshimages/.svn/prop-base/setup10.png.svn-base";s:4:"c5ac";s:45:"cshimages/.svn/prop-base/setup11.png.svn-base";s:4:"c5ac";s:45:"cshimages/.svn/prop-base/setup12.png.svn-base";s:4:"c5ac";s:44:"cshimages/.svn/prop-base/setup2.png.svn-base";s:4:"c5ac";s:44:"cshimages/.svn/prop-base/setup3.png.svn-base";s:4:"c5ac";s:44:"cshimages/.svn/prop-base/setup4.png.svn-base";s:4:"c5ac";s:44:"cshimages/.svn/prop-base/setup5.png.svn-base";s:4:"c5ac";s:44:"cshimages/.svn/prop-base/setup6.png.svn-base";s:4:"c5ac";s:44:"cshimages/.svn/prop-base/setup7.png.svn-base";s:4:"c5ac";s:44:"cshimages/.svn/prop-base/setup8.png.svn-base";s:4:"c5ac";s:44:"cshimages/.svn/prop-base/setup9.png.svn-base";s:4:"c5ac";}',
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
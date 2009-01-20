<?php

########################################################################
# Extension Manager/Repository config file for ext: "sys_action"
#
# Auto generated 20-01-2009 14:26
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'User>Task Center, Actions',
	'description' => 'Actions are \'programmed\' admin tasks which can be performed by selected regular users from the Task Center. An action could be creation of backend users, fixed SQL SELECT queries, listing of records, direct edit access to selected records etc.',
	'category' => 'module',
	'shy' => 0,
	'dependencies' => 'taskcenter',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => '',
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
	'version' => '1.1.2',
	'_md5_values_when_last_written' => 'a:38:{s:8:"TODO.txt";s:4:"17ff";s:22:"class.tx_sysaction.php";s:4:"72c6";s:12:"ext_icon.gif";s:4:"f410";s:14:"ext_tables.php";s:4:"1265";s:14:"ext_tables.sql";s:4:"0fa5";s:13:"locallang.xml";s:4:"c8dd";s:27:"locallang_csh_sysaction.xml";s:4:"a1d4";s:17:"locallang_tca.xml";s:4:"20ca";s:14:"sys_action.gif";s:4:"eb3a";s:17:"sys_action__h.gif";s:4:"7a29";s:7:"tca.php";s:4:"4429";s:16:".svn/all-wcprops";s:4:"5dab";s:12:".svn/entries";s:4:"2d4d";s:11:".svn/format";s:4:"7c5a";s:32:".svn/text-base/TODO.txt.svn-base";s:4:"17ff";s:46:".svn/text-base/class.tx_sysaction.php.svn-base";s:4:"72c6";s:38:".svn/text-base/ext_emconf.php.svn-base";s:4:"e0b0";s:36:".svn/text-base/ext_icon.gif.svn-base";s:4:"f410";s:38:".svn/text-base/ext_tables.php.svn-base";s:4:"1265";s:38:".svn/text-base/ext_tables.sql.svn-base";s:4:"0fa5";s:37:".svn/text-base/locallang.xml.svn-base";s:4:"c8dd";s:51:".svn/text-base/locallang_csh_sysaction.xml.svn-base";s:4:"a1d4";s:41:".svn/text-base/locallang_tca.xml.svn-base";s:4:"20ca";s:38:".svn/text-base/sys_action.gif.svn-base";s:4:"eb3a";s:41:".svn/text-base/sys_action__h.gif.svn-base";s:4:"7a29";s:31:".svn/text-base/tca.php.svn-base";s:4:"4429";s:32:".svn/prop-base/TODO.txt.svn-base";s:4:"3c71";s:46:".svn/prop-base/class.tx_sysaction.php.svn-base";s:4:"3c71";s:38:".svn/prop-base/ext_emconf.php.svn-base";s:4:"3c71";s:36:".svn/prop-base/ext_icon.gif.svn-base";s:4:"c5ac";s:38:".svn/prop-base/ext_tables.php.svn-base";s:4:"3c71";s:38:".svn/prop-base/ext_tables.sql.svn-base";s:4:"a362";s:37:".svn/prop-base/locallang.xml.svn-base";s:4:"3c71";s:51:".svn/prop-base/locallang_csh_sysaction.xml.svn-base";s:4:"3c71";s:41:".svn/prop-base/locallang_tca.xml.svn-base";s:4:"3c71";s:38:".svn/prop-base/sys_action.gif.svn-base";s:4:"c5ac";s:41:".svn/prop-base/sys_action__h.gif.svn-base";s:4:"c5ac";s:31:".svn/prop-base/tca.php.svn-base";s:4:"3c71";}',
	'constraints' => array(
		'depends' => array(
			'taskcenter' => '',
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
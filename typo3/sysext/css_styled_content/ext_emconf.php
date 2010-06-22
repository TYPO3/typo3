<?php

########################################################################
# Extension Manager/Repository config file for ext "css_styled_content".
#
# Auto generated 25-11-2009 21:58
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
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
	'version' => '1.0.0',
	'_md5_values_when_last_written' => 'a:25:{s:21:"ext_conf_template.txt";s:4:"3e65";s:12:"ext_icon.gif";s:4:"1845";s:17:"ext_localconf.php";s:4:"f1ff";s:14:"ext_tables.php";s:4:"3323";s:15:"flexform_ds.xml";s:4:"fc4a";s:16:"locallang_db.xml";s:4:"c74b";s:16:"pageTSconfig.txt";s:4:"c321";s:15:"css/example.css";s:4:"86e7";s:24:"css/example_outlines.css";s:4:"85b2";s:14:"css/readme.txt";s:4:"ee9d";s:31:"css/img/background_gradient.gif";s:4:"45d7";s:28:"css/img/red_arrow_bullet.gif";s:4:"82d6";s:12:"doc/TODO.txt";s:4:"6534";s:14:"doc/manual.sxw";s:4:"0179";s:37:"pi1/class.tx_cssstyledcontent_pi1.php";s:4:"e682";s:17:"pi1/locallang.xml";s:4:"974c";s:20:"static/constants.txt";s:4:"03fa";s:20:"static/editorcfg.txt";s:4:"52b8";s:16:"static/setup.txt";s:4:"e99c";s:25:"static/v3.8/constants.txt";s:4:"e761";s:21:"static/v3.8/setup.txt";s:4:"399a";s:25:"static/v3.9/constants.txt";s:4:"f1f3";s:21:"static/v3.9/setup.txt";s:4:"d84e";s:25:"static/v4.2/constants.txt";s:4:"a02b";s:21:"static/v4.2/setup.txt";s:4:"6650";}',
	'constraints' => array(
		'depends' => array(
			'cms' => '',
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
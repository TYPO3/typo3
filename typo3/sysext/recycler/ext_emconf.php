<?php

########################################################################
# Extension Manager/Repository config file for ext: "recycler"
#
# Auto generated 10-03-2009 22:56
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Recycler',
	'description' => 'The recycler offers the possibilities of cleaning up the garbage collection or to restore data again. Based on an ExtJS interface its possible to get a quick overview of the accordant records, filter the resultset and execute the required actions. This new feature is the modernized and core-specific version of the kj_recycler extension, that has been available in the TER for years now.',
	'category' => 'module',
	'author' => 'Julian Kleinhans',
	'author_email' => 'typo3@kj187.de',
	'shy' => '',
	'dependencies' => 'cms',
	'conflicts' => '',
	'priority' => '',
	'module' => 'mod1',
	'state' => 'alpha',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '1.0.1',
	'constraints' => array(
		'depends' => array(
			'cms' => '',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:34:{s:9:"ChangeLog";s:4:"7b94";s:12:"ext_icon.gif";s:4:"cf3b";s:17:"ext_localconf.php";s:4:"cee4";s:14:"ext_tables.php";s:4:"2275";s:16:"locallang_db.xml";s:4:"a06e";s:56:"classes/controller/class.tx_recycler_controller_ajax.php";s:4:"566a";s:43:"classes/helper/class.tx_recycler_helper.php";s:4:"411c";s:56:"classes/model/class.tx_recycler_model_deletedRecords.php";s:4:"b7b0";s:48:"classes/model/class.tx_recycler_model_tables.php";s:4:"9cc8";s:54:"classes/view/class.tx_recycler_view_deletedRecords.php";s:4:"c552";s:14:"doc/manual.sxw";s:4:"3528";s:14:"mod1/clear.gif";s:4:"cc11";s:13:"mod1/conf.php";s:4:"e060";s:14:"mod1/index.php";s:4:"cb21";s:18:"mod1/locallang.xml";s:4:"11a1";s:22:"mod1/locallang_mod.xml";s:4:"188b";s:22:"mod1/mod_template.html";s:4:"08a6";s:19:"mod1/moduleicon.gif";s:4:"cf3b";s:23:"res/css/customExtJs.css";s:4:"8697";s:20:"res/icons/accept.png";s:4:"8bfe";s:24:"res/icons/arrow_redo.png";s:4:"343b";s:40:"res/icons/arrow_rotate_anticlockwise.png";s:4:"a7db";s:17:"res/icons/bin.png";s:4:"728a";s:24:"res/icons/bin_closed.png";s:4:"c5b3";s:23:"res/icons/bin_empty.png";s:4:"2e76";s:27:"res/icons/database_save.png";s:4:"8303";s:20:"res/icons/delete.gif";s:4:"5a2a";s:26:"res/icons/filter_clear.png";s:4:"3862";s:28:"res/icons/filter_refresh.png";s:4:"b051";s:22:"res/icons/recycler.gif";s:4:"7b41";s:23:"res/icons/recycler2.gif";s:4:"cf3b";s:22:"res/js/ext_expander.js";s:4:"bb02";s:22:"res/js/search_field.js";s:4:"efae";s:21:"res/js/t3_recycler.js";s:4:"6021";}',
	'suggests' => array(
	),
);

?>
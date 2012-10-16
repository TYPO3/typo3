<?php

########################################################################
# Extension Manager/Repository config file for ext "recycler".
#
# Auto generated 16-10-2012 14:07
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Recycler',
	'description' => 'The recycler offers the possibility to restore deleted records or remove them from the database permanently. These actions can be applied to a single record, multiple records, and recursively to child records (ex. restoring a page can restore all content elements on that page). Filtering by page and by table provides a quick overview of deleted records before taking action on them.',
	'category' => 'module',
	'author' => 'Julian Kleinhans',
	'author_email' => 'typo3@kj187.de',
	'shy' => '',
	'dependencies' => 'cms',
	'conflicts' => '',
	'priority' => '',
	'module' => 'mod1',
	'doNotLoadInFE' => 1,
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '4.7.5',
	'constraints' => array(
		'depends' => array(
			'php' => '5.3.0-0.0.0',
			'typo3' => '4.7.0-0.0.0',
			'cms' => '',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:34:{s:9:"ChangeLog";s:4:"7b94";s:12:"ext_icon.gif";s:4:"1426";s:17:"ext_localconf.php";s:4:"cee4";s:14:"ext_tables.php";s:4:"1a0d";s:16:"locallang_db.xlf";s:4:"ffeb";s:56:"classes/controller/class.tx_recycler_controller_ajax.php";s:4:"13c7";s:43:"classes/helper/class.tx_recycler_helper.php";s:4:"403d";s:56:"classes/model/class.tx_recycler_model_deletedRecords.php";s:4:"7926";s:48:"classes/model/class.tx_recycler_model_tables.php";s:4:"68c8";s:54:"classes/view/class.tx_recycler_view_deletedRecords.php";s:4:"50df";s:14:"doc/manual.sxw";s:4:"3528";s:14:"mod1/clear.gif";s:4:"cc11";s:13:"mod1/conf.php";s:4:"e060";s:14:"mod1/index.php";s:4:"5a54";s:18:"mod1/locallang.xlf";s:4:"76ed";s:22:"mod1/locallang_mod.xlf";s:4:"8def";s:22:"mod1/mod_template.html";s:4:"7c59";s:19:"mod1/moduleicon.gif";s:4:"7ba2";s:23:"res/css/customExtJs.css";s:4:"32f9";s:20:"res/icons/accept.png";s:4:"e36c";s:24:"res/icons/arrow_redo.png";s:4:"5e7c";s:40:"res/icons/arrow_rotate_anticlockwise.png";s:4:"d471";s:17:"res/icons/bin.png";s:4:"62fd";s:24:"res/icons/bin_closed.png";s:4:"3ed7";s:23:"res/icons/bin_empty.png";s:4:"3979";s:27:"res/icons/database_save.png";s:4:"33b7";s:20:"res/icons/delete.gif";s:4:"1a79";s:26:"res/icons/filter_clear.png";s:4:"fb1a";s:28:"res/icons/filter_refresh.png";s:4:"1d4b";s:21:"res/icons/loading.gif";s:4:"8ae1";s:22:"res/icons/recycler.gif";s:4:"26d8";s:23:"res/icons/recycler2.gif";s:4:"cf3b";s:26:"res/icons/x_toolbar_bg.gif";s:4:"b312";s:21:"res/js/t3_recycler.js";s:4:"cc97";}',
	'suggests' => array(
	),
);

?>
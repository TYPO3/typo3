<?php

########################################################################
# Extension Manager/Repository config file for ext: "opendocs"
#
# Auto generated 23-04-2008 10:59
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'User>Open Documents',
	'description' => 'Shows opened documents by the user. This concept is more widely used with the "Classic Backend".',
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
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author' => 'Benjamin Mack',
	'author_email' => 'mack@xnos.org',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'version' => '0.0.1',
	'_md5_values_when_last_written' => 'a:13:{s:21:"class.tx_opendocs.php";s:4:"8e87";s:21:"ext_conf_template.txt";s:4:"967e";s:12:"ext_icon.gif";s:4:"fdf4";s:14:"ext_tables.php";s:4:"f56d";s:22:"locallang_opendocs.xml";s:4:"0326";s:12:"opendocs.css";s:4:"1e57";s:11:"opendocs.js";s:4:"787b";s:12:"opendocs.png";s:4:"917a";s:23:"registerToolbarItem.php";s:4:"439c";s:26:"toolbar_item_active_bg.png";s:4:"c036";s:12:"mod/conf.php";s:4:"a8fe";s:16:"mod/document.gif";s:4:"fdf4";s:21:"mod/locallang_mod.xml";s:4:"7240";}',
	'constraints' => array(
		'depends' => array(
			'php' => '5.1.0-0.0.0',
			'typo3' => '4.2.0-4.2.99',
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
<?php

########################################################################
# Extension Manager/Repository config file for ext "opendocs".
#
# Auto generated 26-01-2011 20:08
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'User>Open Documents',
	'description' => 'Shows opened documents by the user. This concept is more widely used with the "Classic Backend".',
	'category' => 'module',
	'shy' => 0,
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => 'mod',
	'doNotLoadInFE' => 1,
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
	'version' => '1.0.0',
	'_md5_values_when_last_written' => 'a:13:{s:21:"class.tx_opendocs.php";s:4:"90e7";s:21:"ext_conf_template.txt";s:4:"967e";s:12:"ext_icon.gif";s:4:"fdf4";s:14:"ext_tables.php";s:4:"f56d";s:22:"locallang_opendocs.xml";s:4:"0326";s:12:"opendocs.css";s:4:"c0f4";s:11:"opendocs.js";s:4:"613f";s:12:"opendocs.png";s:4:"08b3";s:23:"registerToolbarItem.php";s:4:"520e";s:26:"toolbar_item_active_bg.png";s:4:"5623";s:12:"mod/conf.php";s:4:"69b8";s:16:"mod/document.gif";s:4:"fdf4";s:21:"mod/locallang_mod.xml";s:4:"7240";}',
	'constraints' => array(
		'depends' => array(
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
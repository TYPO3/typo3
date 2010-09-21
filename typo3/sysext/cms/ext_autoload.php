<?php
/*
 * Register necessary class names with autoloader
 *
 * $Id: ext_autoload.php 6536 2009-11-25 14:07:18Z stucki $
 */
return array(
	'tslib_adminpanel' => PATH_tslib . 'class.tslib_adminpanel.php',
	'tslib_cobj' => PATH_tslib . 'class.tslib_content.php',
	'tslib_frameset' => PATH_tslib . 'class.tslib_content.php',
	'tslib_tableoffset' => PATH_tslib . 'class.tslib_content.php',
	'tslib_controltable' => PATH_tslib . 'class.tslib_content.php',
	'tslib_eidtools' => PATH_tslib . 'class.tslib_eidtools.php',
	'tslib_fe' => PATH_tslib . 'class.tslib_fe.php',
	'tslib_fecompression' => PATH_tslib . 'class.tslib_fecompression.php',
	'tslib_fetce' => PATH_tslib . 'class.tslib_fetce.php',
	'tslib_feuserauth' => PATH_tslib . 'class.tslib_feuserauth.php',
	'tslib_gifbuilder' => PATH_tslib . 'class.tslib_gifbuilder.php',
	'tslib_menu' => PATH_tslib . 'class.tslib_menu.php',
	'tslib_tmenu' => PATH_tslib . 'class.tslib_menu.php',
	'tslib_gmenu' => PATH_tslib . 'class.tslib_menu.php',
	'tslib_imgmenu' => PATH_tslib . 'class.tslib_menu.php',
	'tslib_jsmenu' => PATH_tslib . 'class.tslib_menu.php',
	'tspagegen' => PATH_tslib . 'class.tslib_pagegen.php',
	'fe_loaddbgroup' => PATH_tslib . 'class.tslib_pagegen.php',
	'tslib_pibase' => PATH_tslib . 'class.tslib_pibase.php',
	'tslib_search' => PATH_tslib . 'class.tslib_search.php',
	'tslib_extdirecteid' => PATH_tslib . 'class.tslib_extdirecteid.php',
	'sc_tslib_showpic' => PATH_tslib . 'showpic.php',
	'tx_cms_mediaitems' => PATH_tslib . 'hooks/class.tx_cms_mediaitems.php',
	'tx_cms_treelistcacheupdate' => PATH_tslib . 'hooks/class.tx_cms_treelistcacheupdate.php',
	'tslib_content_cobjgetsinglehook' => PATH_tslib . 'interfaces/interface.tslib_content_cobjgetsinglehook.php',
	'tslib_menu_filterMenuPagesHook' => PATH_tslib . 'interfaces/interface.tslib_menu_filterMenuPagesHook.php',
	'tslib_content_getdatahook' => PATH_tslib . 'interfaces/interface.tslib_content_getdatahook.php',
	'tslib_cobj_getimgresourcehook' => PATH_tslib . 'interfaces/interface.tslib_content_getimgresourcehook.php',
	'tslib_content_postinithook' => PATH_tslib . 'interfaces/interface.tslib_content_postinithook.php',
	'tslib_content_stdwraphook' => PATH_tslib . 'interfaces/interface.tslib_content_stdwraphook.php',
	'user_various' => PATH_tslib . 'media/scripts/example_callfunction.php',
	'tslib_gmenu_foldout' => PATH_tslib . 'media/scripts/gmenu_foldout.php',
	'tslib_gmenu_layers' => PATH_tslib . 'media/scripts/gmenu_layers.php',
	'tslib_tmenu_layers' => PATH_tslib . 'media/scripts/tmenu_layers.php',
	'tslib_mediawizardprovider' => PATH_tslib . 'interfaces/interface.tslib_mediawizardprovider.php',
	'tslib_mediawizardcoreprovider' => PATH_tslib . 'class.tslib_mediawizardcoreprovider.php',
	'tslib_mediawizardmanager' => PATH_tslib . 'class.tslib_mediawizardmanager.php',
);
?>
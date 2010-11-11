<?php

########################################################################
# Extension Manager/Repository config file for ext "workspaces".
#
# Auto generated 11-11-2010 20:39
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Workspaces',
	'description' => '',
	'category' => 'module',
	'author' => 'Workspaces Team',
	'author_email' => 'workspaces@typo3.org',
	'shy' => '',
	'dependencies' => 'version,extbase,fluid',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'beta',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '0.9.0',
	'constraints' => array(
		'depends' => array(
			'version' => '',
			'extbase' => '',
			'fluid' => '',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:72:{s:16:"ext_autoload.php";s:4:"41ee";s:12:"ext_icon.gif";s:4:"55bc";s:17:"ext_localconf.php";s:4:"f8bd";s:14:"ext_tables.php";s:4:"c18c";s:14:"ext_tables.sql";s:4:"2119";s:7:"tca.php";s:4:"4d74";s:61:"Classes/BackendUserInterface/WorkspaceSelectorToolbarItem.php";s:4:"e23f";s:41:"Classes/Controller/AbstractController.php";s:4:"a67a";s:40:"Classes/Controller/PreviewController.php";s:4:"8b93";s:39:"Classes/Controller/ReviewController.php";s:4:"a7a4";s:37:"Classes/ExtDirect/AbstractHandler.php";s:4:"0b9b";s:35:"Classes/ExtDirect/ActionHandler.php";s:4:"22a4";s:39:"Classes/ExtDirect/MassActionHandler.php";s:4:"ba41";s:28:"Classes/ExtDirect/Server.php";s:4:"e3d6";s:33:"Classes/ExtDirect/ToolbarMenu.php";s:4:"5cb0";s:31:"Classes/Service/AutoPublish.php";s:4:"e0d8";s:35:"Classes/Service/AutoPublishTask.php";s:4:"bd1f";s:26:"Classes/Service/Befunc.php";s:4:"2ef2";s:28:"Classes/Service/GridData.php";s:4:"baf7";s:26:"Classes/Service/Stages.php";s:4:"ad54";s:27:"Classes/Service/Tcemain.php";s:4:"e45c";s:30:"Classes/Service/Workspaces.php";s:4:"1644";s:40:"Resources/Private/Language/locallang.xml";s:4:"fd0b";s:56:"Resources/Private/Language/locallang_csh_sysws_stage.xml";s:4:"d2f3";s:43:"Resources/Private/Language/locallang_db.xml";s:4:"57f7";s:44:"Resources/Private/Language/locallang_mod.xml";s:4:"9fc7";s:37:"Resources/Private/Layouts/module.html";s:4:"aeef";s:36:"Resources/Private/Layouts/nodoc.html";s:4:"5963";s:36:"Resources/Private/Layouts/popup.html";s:4:"710d";s:38:"Resources/Private/Partials/legend.html";s:4:"246f";s:42:"Resources/Private/Partials/navigation.html";s:4:"a5fa";s:45:"Resources/Private/Templates/Preview/Help.html";s:4:"4568";s:46:"Resources/Private/Templates/Preview/Index.html";s:4:"2309";s:49:"Resources/Private/Templates/Review/FullIndex.html";s:4:"242c";s:45:"Resources/Private/Templates/Review/Index.html";s:4:"9d5d";s:51:"Resources/Private/Templates/Review/SingleIndex.html";s:4:"93ce";s:30:"Resources/Public/Images/bg.gif";s:4:"916d";s:38:"Resources/Public/Images/moduleicon.gif";s:4:"55bc";s:61:"Resources/Public/Images/version-workspace-sendtonextstage.png";s:4:"46fa";s:61:"Resources/Public/Images/version-workspace-sendtoprevstage.png";s:4:"851d";s:38:"Resources/Public/JavaScript/actions.js";s:4:"87c5";s:40:"Resources/Public/JavaScript/component.js";s:4:"4740";s:44:"Resources/Public/JavaScript/configuration.js";s:4:"90bc";s:35:"Resources/Public/JavaScript/grid.js";s:4:"b6bc";s:38:"Resources/Public/JavaScript/helpers.js";s:4:"066e";s:38:"Resources/Public/JavaScript/preview.js";s:4:"a1b9";s:38:"Resources/Public/JavaScript/toolbar.js";s:4:"f56d";s:44:"Resources/Public/JavaScript/workspacegrid.js";s:4:"8d4c";s:44:"Resources/Public/JavaScript/workspacemenu.js";s:4:"f85e";s:41:"Resources/Public/JavaScript/workspaces.js";s:4:"d26d";s:54:"Resources/Public/JavaScript/gridfilters/GridFilters.js";s:4:"a576";s:59:"Resources/Public/JavaScript/gridfilters/css/GridFilters.css";s:4:"aec0";s:57:"Resources/Public/JavaScript/gridfilters/css/RangeMenu.css";s:4:"745a";s:63:"Resources/Public/JavaScript/gridfilters/filter/BooleanFilter.js";s:4:"0f9d";s:60:"Resources/Public/JavaScript/gridfilters/filter/DateFilter.js";s:4:"0692";s:56:"Resources/Public/JavaScript/gridfilters/filter/Filter.js";s:4:"3a71";s:60:"Resources/Public/JavaScript/gridfilters/filter/ListFilter.js";s:4:"c79a";s:63:"Resources/Public/JavaScript/gridfilters/filter/NumericFilter.js";s:4:"a933";s:62:"Resources/Public/JavaScript/gridfilters/filter/StringFilter.js";s:4:"2d59";s:57:"Resources/Public/JavaScript/gridfilters/images/equals.png";s:4:"87b7";s:55:"Resources/Public/JavaScript/gridfilters/images/find.png";s:4:"9f1c";s:63:"Resources/Public/JavaScript/gridfilters/images/greater_than.png";s:4:"746c";s:60:"Resources/Public/JavaScript/gridfilters/images/less_than.png";s:4:"2fb7";s:68:"Resources/Public/JavaScript/gridfilters/images/sort_filtered_asc.gif";s:4:"9e7a";s:69:"Resources/Public/JavaScript/gridfilters/images/sort_filtered_desc.gif";s:4:"6d59";s:56:"Resources/Public/JavaScript/gridfilters/menu/ListMenu.js";s:4:"9e88";s:57:"Resources/Public/JavaScript/gridfilters/menu/RangeMenu.js";s:4:"7d96";s:38:"Resources/Public/StyleSheet/module.css";s:4:"6c4e";s:36:"Tests/Service/Workspace_testcase.php";s:4:"aa4d";s:41:"Tests/Service/fixtures/dbDefaultPages.xml";s:4:"06f4";s:46:"Tests/Service/fixtures/dbDefaultWorkspaces.xml";s:4:"32a7";s:41:"Tests/Service/fixtures/dbMovedContent.xml";s:4:"1016";}',
	'suggests' => array(
	),
);

?>
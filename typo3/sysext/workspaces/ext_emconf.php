<?php

########################################################################
# Extension Manager/Repository config file for ext "workspaces".
#
# Auto generated 06-03-2012 11:01
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Workspaces Management',
	'description' => 'Adds workspaces functionality with custom stages to TYPO3.',
	'category' => 'be',
	'author' => 'Workspaces Team',
	'author_email' => '',
	'shy' => '',
	'dependencies' => 'version,extbase,fluid',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author_company' => '',
	'version' => '4.5.12',
	'constraints' => array(
		'depends' => array(
			'typo3' => '4.5.9-0.0.0',
			'version' => '',
			'extbase' => '',
			'fluid' => '',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:87:{s:9:"ChangeLog";s:4:"9209";s:16:"ext_autoload.php";s:4:"678b";s:12:"ext_icon.gif";s:4:"55bc";s:17:"ext_localconf.php";s:4:"6ea7";s:14:"ext_tables.php";s:4:"66d1";s:14:"ext_tables.sql";s:4:"6ecf";s:7:"tca.php";s:4:"1741";s:61:"Classes/BackendUserInterface/WorkspaceSelectorToolbarItem.php";s:4:"77a4";s:41:"Classes/Controller/AbstractController.php";s:4:"d33c";s:40:"Classes/Controller/PreviewController.php";s:4:"270c";s:39:"Classes/Controller/ReviewController.php";s:4:"3e2e";s:37:"Classes/ExtDirect/AbstractHandler.php";s:4:"d3fd";s:35:"Classes/ExtDirect/ActionHandler.php";s:4:"6096";s:39:"Classes/ExtDirect/MassActionHandler.php";s:4:"a5f7";s:28:"Classes/ExtDirect/Server.php";s:4:"a1aa";s:33:"Classes/ExtDirect/ToolbarMenu.php";s:4:"0d40";s:34:"Classes/Reports/StatusProvider.php";s:4:"8add";s:31:"Classes/Service/AutoPublish.php";s:4:"4f81";s:35:"Classes/Service/AutoPublishTask.php";s:4:"f886";s:26:"Classes/Service/Befunc.php";s:4:"c838";s:27:"Classes/Service/Fehooks.php";s:4:"3f84";s:28:"Classes/Service/GridData.php";s:4:"f4bb";s:26:"Classes/Service/Stages.php";s:4:"4bda";s:27:"Classes/Service/Tcemain.php";s:4:"6f0e";s:30:"Classes/Service/Workspaces.php";s:4:"18f6";s:24:"Documentation/manual.odt";s:4:"724c";s:24:"Documentation/manual.pdf";s:4:"8aa6";s:24:"Documentation/manual.sxw";s:4:"cc4a";s:40:"Resources/Private/Language/locallang.xml";s:4:"9b39";s:56:"Resources/Private/Language/locallang_csh_sysws_stage.xml";s:4:"d2f3";s:43:"Resources/Private/Language/locallang_db.xml";s:4:"57f7";s:44:"Resources/Private/Language/locallang_mod.xml";s:4:"8b66";s:37:"Resources/Private/Layouts/module.html";s:4:"9f5e";s:36:"Resources/Private/Layouts/nodoc.html";s:4:"a3ce";s:36:"Resources/Private/Layouts/popup.html";s:4:"3701";s:38:"Resources/Private/Partials/legend.html";s:4:"246f";s:42:"Resources/Private/Partials/navigation.html";s:4:"a0a0";s:45:"Resources/Private/Templates/Preview/Help.html";s:4:"a77a";s:46:"Resources/Private/Templates/Preview/Index.html";s:4:"ff3a";s:48:"Resources/Private/Templates/Preview/NewPage.html";s:4:"6cbc";s:48:"Resources/Private/Templates/Preview/Preview.html";s:4:"7b26";s:49:"Resources/Private/Templates/Review/FullIndex.html";s:4:"478e";s:45:"Resources/Private/Templates/Review/Index.html";s:4:"159f";s:51:"Resources/Private/Templates/Review/SingleIndex.html";s:4:"b5c9";s:30:"Resources/Public/Images/bg.gif";s:4:"916d";s:52:"Resources/Public/Images/generate-ws-preview-link.png";s:4:"e107";s:38:"Resources/Public/Images/moduleicon.gif";s:4:"55bc";s:37:"Resources/Public/Images/slider-bg.png";s:4:"f5e8";s:40:"Resources/Public/Images/slider-thumb.png";s:4:"86d8";s:38:"Resources/Public/Images/typo3-logo.png";s:4:"284a";s:61:"Resources/Public/Images/version-workspace-sendtonextstage.png";s:4:"46fa";s:61:"Resources/Public/Images/version-workspace-sendtoprevstage.png";s:4:"851d";s:53:"Resources/Public/Images/workspaces-comments-arrow.gif";s:4:"2423";s:63:"Resources/Public/JavaScript/Ext.ux.plugins.TabStripContainer.js";s:4:"9a5a";s:38:"Resources/Public/JavaScript/actions.js";s:4:"7607";s:40:"Resources/Public/JavaScript/component.js";s:4:"2aef";s:44:"Resources/Public/JavaScript/configuration.js";s:4:"1439";s:35:"Resources/Public/JavaScript/grid.js";s:4:"b4f4";s:38:"Resources/Public/JavaScript/helpers.js";s:4:"a391";s:38:"Resources/Public/JavaScript/preview.js";s:4:"f031";s:38:"Resources/Public/JavaScript/toolbar.js";s:4:"c3ec";s:44:"Resources/Public/JavaScript/workspacegrid.js";s:4:"0b4b";s:44:"Resources/Public/JavaScript/workspacemenu.js";s:4:"dadf";s:41:"Resources/Public/JavaScript/workspaces.js";s:4:"ceee";s:54:"Resources/Public/JavaScript/gridfilters/GridFilters.js";s:4:"4b22";s:59:"Resources/Public/JavaScript/gridfilters/css/GridFilters.css";s:4:"84a8";s:57:"Resources/Public/JavaScript/gridfilters/css/RangeMenu.css";s:4:"745a";s:63:"Resources/Public/JavaScript/gridfilters/filter/BooleanFilter.js";s:4:"3c02";s:60:"Resources/Public/JavaScript/gridfilters/filter/DateFilter.js";s:4:"c80d";s:56:"Resources/Public/JavaScript/gridfilters/filter/Filter.js";s:4:"583f";s:60:"Resources/Public/JavaScript/gridfilters/filter/ListFilter.js";s:4:"9554";s:63:"Resources/Public/JavaScript/gridfilters/filter/NumericFilter.js";s:4:"91a2";s:62:"Resources/Public/JavaScript/gridfilters/filter/StringFilter.js";s:4:"acc8";s:57:"Resources/Public/JavaScript/gridfilters/images/equals.png";s:4:"87b7";s:55:"Resources/Public/JavaScript/gridfilters/images/find.png";s:4:"9f1c";s:63:"Resources/Public/JavaScript/gridfilters/images/greater_than.png";s:4:"746c";s:60:"Resources/Public/JavaScript/gridfilters/images/less_than.png";s:4:"2fb7";s:68:"Resources/Public/JavaScript/gridfilters/images/sort_filtered_asc.gif";s:4:"9e7a";s:69:"Resources/Public/JavaScript/gridfilters/images/sort_filtered_desc.gif";s:4:"6d59";s:56:"Resources/Public/JavaScript/gridfilters/menu/ListMenu.js";s:4:"d14b";s:57:"Resources/Public/JavaScript/gridfilters/menu/RangeMenu.js";s:4:"0bbd";s:38:"Resources/Public/StyleSheet/module.css";s:4:"3a84";s:39:"Resources/Public/StyleSheet/preview.css";s:4:"8f08";s:31:"Tests/Service/WorkspaceTest.php";s:4:"2b56";s:41:"Tests/Service/fixtures/dbDefaultPages.xml";s:4:"a86b";s:46:"Tests/Service/fixtures/dbDefaultWorkspaces.xml";s:4:"32a7";s:41:"Tests/Service/fixtures/dbMovedContent.xml";s:4:"dd73";}',
	'suggests' => array(
	),
);

?>
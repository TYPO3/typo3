<?php

########################################################################
# Extension Manager/Repository config file for ext "workspaces".
#
# Auto generated 16-10-2012 12:43
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
	'docPath' => 'Documentation',
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'author_company' => '',
	'version' => '4.7.5',
	'constraints' => array(
		'depends' => array(
			'typo3' => '4.7.0-0.0.0',
			'version' => '',
			'extbase' => '',
			'fluid' => '',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:91:{s:9:"ChangeLog";s:4:"eb74";s:16:"ext_autoload.php";s:4:"4bc0";s:12:"ext_icon.gif";s:4:"55bc";s:17:"ext_localconf.php";s:4:"04d0";s:14:"ext_tables.php";s:4:"20de";s:14:"ext_tables.sql";s:4:"6b7e";s:7:"tca.php";s:4:"ec6c";s:41:"Classes/Controller/AbstractController.php";s:4:"d33c";s:40:"Classes/Controller/PreviewController.php";s:4:"3157";s:39:"Classes/Controller/ReviewController.php";s:4:"4850";s:37:"Classes/ExtDirect/AbstractHandler.php";s:4:"f58c";s:35:"Classes/ExtDirect/ActionHandler.php";s:4:"ed03";s:39:"Classes/ExtDirect/MassActionHandler.php";s:4:"940e";s:50:"Classes/ExtDirect/PagetreeCollectionsProcessor.php";s:4:"5abe";s:28:"Classes/ExtDirect/Server.php";s:4:"15a8";s:33:"Classes/ExtDirect/ToolbarMenu.php";s:4:"5588";s:50:"Classes/ExtDirect/WorkspaceSelectorToolbarItem.php";s:4:"5252";s:31:"Classes/Service/AutoPublish.php";s:4:"9c8f";s:35:"Classes/Service/AutoPublishTask.php";s:4:"7075";s:26:"Classes/Service/Befunc.php";s:4:"1ef1";s:42:"Classes/Service/CleanupPreviewLinkTask.php";s:4:"49bf";s:27:"Classes/Service/Fehooks.php";s:4:"0fe0";s:28:"Classes/Service/GridData.php";s:4:"b7b1";s:26:"Classes/Service/Stages.php";s:4:"d836";s:27:"Classes/Service/Tcemain.php";s:4:"ce51";s:30:"Classes/Service/Workspaces.php";s:4:"7565";s:24:"Documentation/manual.odt";s:4:"4fa0";s:24:"Documentation/manual.pdf";s:4:"3841";s:24:"Documentation/manual.sxw";s:4:"00e7";s:40:"Resources/Private/Language/locallang.xlf";s:4:"8d10";s:56:"Resources/Private/Language/locallang_csh_sysws_stage.xlf";s:4:"0558";s:43:"Resources/Private/Language/locallang_db.xlf";s:4:"4053";s:44:"Resources/Private/Language/locallang_mod.xlf";s:4:"a52d";s:37:"Resources/Private/Layouts/Module.html";s:4:"9f5e";s:36:"Resources/Private/Layouts/Nodoc.html";s:4:"a3ce";s:36:"Resources/Private/Layouts/Popup.html";s:4:"3701";s:38:"Resources/Private/Partials/legend.html";s:4:"246f";s:42:"Resources/Private/Partials/navigation.html";s:4:"b186";s:45:"Resources/Private/Templates/Preview/Help.html";s:4:"a77a";s:46:"Resources/Private/Templates/Preview/Index.html";s:4:"ff3a";s:48:"Resources/Private/Templates/Preview/NewPage.html";s:4:"6cbc";s:48:"Resources/Private/Templates/Preview/Preview.html";s:4:"4682";s:49:"Resources/Private/Templates/Review/FullIndex.html";s:4:"478e";s:45:"Resources/Private/Templates/Review/Index.html";s:4:"159f";s:51:"Resources/Private/Templates/Review/SingleIndex.html";s:4:"b5c9";s:30:"Resources/Public/Images/bg.gif";s:4:"916d";s:42:"Resources/Public/Images/button_approve.png";s:4:"7e25";s:42:"Resources/Public/Images/button_discard.png";s:4:"15ac";s:41:"Resources/Public/Images/button_reject.png";s:4:"cce5";s:52:"Resources/Public/Images/generate-ws-preview-link.png";s:4:"e107";s:38:"Resources/Public/Images/moduleicon.gif";s:4:"55bc";s:37:"Resources/Public/Images/slider-bg.png";s:4:"f5e8";s:40:"Resources/Public/Images/slider-thumb.png";s:4:"86d8";s:38:"Resources/Public/Images/typo3-logo.png";s:4:"284a";s:61:"Resources/Public/Images/version-workspace-sendtonextstage.png";s:4:"46fa";s:61:"Resources/Public/Images/version-workspace-sendtoprevstage.png";s:4:"851d";s:53:"Resources/Public/Images/workspaces-comments-arrow.gif";s:4:"2423";s:38:"Resources/Public/JavaScript/actions.js";s:4:"3c00";s:40:"Resources/Public/JavaScript/component.js";s:4:"95cf";s:44:"Resources/Public/JavaScript/configuration.js";s:4:"a1dc";s:63:"Resources/Public/JavaScript/Ext.ux.plugins.TabStripContainer.js";s:4:"2290";s:35:"Resources/Public/JavaScript/grid.js";s:4:"bf8e";s:38:"Resources/Public/JavaScript/helpers.js";s:4:"3114";s:38:"Resources/Public/JavaScript/preview.js";s:4:"3691";s:38:"Resources/Public/JavaScript/toolbar.js";s:4:"82b2";s:44:"Resources/Public/JavaScript/workspacemenu.js";s:4:"1ada";s:41:"Resources/Public/JavaScript/workspaces.js";s:4:"e70d";s:46:"Resources/Public/JavaScript/Store/mainstore.js";s:4:"87cc";s:54:"Resources/Public/JavaScript/gridfilters/GridFilters.js";s:4:"4b22";s:59:"Resources/Public/JavaScript/gridfilters/css/GridFilters.css";s:4:"84a8";s:57:"Resources/Public/JavaScript/gridfilters/css/RangeMenu.css";s:4:"745a";s:63:"Resources/Public/JavaScript/gridfilters/filter/BooleanFilter.js";s:4:"3c02";s:60:"Resources/Public/JavaScript/gridfilters/filter/DateFilter.js";s:4:"c80d";s:56:"Resources/Public/JavaScript/gridfilters/filter/Filter.js";s:4:"583f";s:60:"Resources/Public/JavaScript/gridfilters/filter/ListFilter.js";s:4:"9554";s:63:"Resources/Public/JavaScript/gridfilters/filter/NumericFilter.js";s:4:"91a2";s:62:"Resources/Public/JavaScript/gridfilters/filter/StringFilter.js";s:4:"acc8";s:57:"Resources/Public/JavaScript/gridfilters/images/equals.png";s:4:"87b7";s:55:"Resources/Public/JavaScript/gridfilters/images/find.png";s:4:"9f1c";s:63:"Resources/Public/JavaScript/gridfilters/images/greater_than.png";s:4:"746c";s:60:"Resources/Public/JavaScript/gridfilters/images/less_than.png";s:4:"2fb7";s:68:"Resources/Public/JavaScript/gridfilters/images/sort_filtered_asc.gif";s:4:"9e7a";s:69:"Resources/Public/JavaScript/gridfilters/images/sort_filtered_desc.gif";s:4:"6d59";s:56:"Resources/Public/JavaScript/gridfilters/menu/ListMenu.js";s:4:"d14b";s:57:"Resources/Public/JavaScript/gridfilters/menu/RangeMenu.js";s:4:"0bbd";s:38:"Resources/Public/StyleSheet/module.css";s:4:"3a84";s:39:"Resources/Public/StyleSheet/preview.css";s:4:"9cee";s:31:"Tests/Service/WorkspaceTest.php";s:4:"cfdd";s:41:"Tests/Service/fixtures/dbDefaultPages.xml";s:4:"a86b";s:46:"Tests/Service/fixtures/dbDefaultWorkspaces.xml";s:4:"32a7";s:41:"Tests/Service/fixtures/dbMovedContent.xml";s:4:"dd73";}',
	'suggests' => array(
	),
);

?>
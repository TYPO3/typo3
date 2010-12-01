<?php

########################################################################
# Extension Manager/Repository config file for ext "fal".
#
# Auto generated 01-12-2010 21:27
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'File Abstraction Layer (FAL)',
	'description' => 'Used to create references to files rather than storing their names in the DB fields.',
	'category' => 'misc',
	'author' => 'FAL team',
	'author_email' => '',
	'shy' => 0,
	'dependencies' => 'cms',
	'conflicts' => '',
	'priority' => '',
	'module' => 'mod_extjs',
	'state' => 'beta',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '1.0.0',
	'constraints' => array(
		'depends' => array(
			'cms' => '',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:82:{s:9:"ChangeLog";s:4:"9e69";s:10:"README.txt";s:4:"ee2d";s:24:"ds_filesystemstorage.xml";s:4:"f4dd";s:16:"ext_autoload.php";s:4:"02fa";s:12:"ext_icon.gif";s:4:"1bdc";s:17:"ext_localconf.php";s:4:"998e";s:14:"ext_tables.php";s:4:"d10e";s:14:"ext_tables.sql";s:4:"cc15";s:29:"icon_tx_fal_contentimages.gif";s:4:"475a";s:25:"icon_tx_fal_sys_files.gif";s:4:"475a";s:13:"locallang.xml";s:4:"92d0";s:16:"locallang_db.xml";s:4:"fac4";s:7:"tca.php";s:4:"4b02";s:35:"classes/class.tx_fal_Collection.php";s:4:"2d40";s:29:"classes/class.tx_fal_File.php";s:4:"c18d";s:31:"classes/class.tx_fal_Helper.php";s:4:"501e";s:32:"classes/class.tx_fal_Indexer.php";s:4:"15c6";s:30:"classes/class.tx_fal_Mount.php";s:4:"f9e3";s:35:"classes/class.tx_fal_Repository.php";s:4:"4b11";s:32:"classes/class.tx_fal_tcafunc.php";s:4:"b101";s:27:"classes/tceforms_wizard.php";s:4:"42a1";s:55:"classes/controller/class.tx_fal_migrationcontroller.php";s:4:"19b2";s:50:"classes/dataprovider/class.tx_fal_dataprovider.php";s:4:"47e9";s:49:"classes/dataprovider/class.tx_fal_extfilefunc.php";s:4:"f892";s:55:"classes/dataprovider/class.tx_fal_list_dataprovider.php";s:4:"cafb";s:59:"classes/dataprovider/class.tx_fal_plupload_dataprovider.php";s:4:"ef08";s:57:"classes/exception/class.tx_fal_exception_filenotfound.php";s:4:"75a8";s:54:"classes/hooks/class.tslib_fe_content_cobjdata_hook.php";s:4:"b00b";s:45:"classes/hooks/class.tslib_fe_rootlinehook.php";s:4:"7d7a";s:42:"classes/hooks/class.tx_fal_backendhook.php";s:4:"883e";s:42:"classes/hooks/class.tx_fal_examplehook.php";s:4:"5211";s:65:"classes/hooks/class.tx_fal_hooks_browselinks_browserrendering.php";s:4:"eb56";s:52:"classes/hooks/class.tx_fal_hooks_extfilefunchook.php";s:4:"fb47";s:57:"classes/hooks/class.tx_fal_hooks_tceforms_dbfileicons.php";s:4:"b47c";s:59:"classes/iterator/class.tx_fal_databasefieldnameiterator.php";s:4:"03c7";s:48:"classes/iterator/class.tx_fal_recorditerator.php";s:4:"7d86";s:58:"classes/storage/class.tx_fal_storage_filesystemstorage.php";s:4:"13d8";s:54:"classes/storage/interface.tx_fal_storage_interface.php";s:4:"ddba";s:30:"contrib/plupload/changelog.txt";s:4:"905f";s:28:"contrib/plupload/license.txt";s:4:"3515";s:26:"contrib/plupload/readme.md";s:4:"66d9";s:27:"contrib/plupload/readme.txt";s:4:"cffa";s:33:"contrib/plupload/js/gears_init.js";s:4:"428c";s:48:"contrib/plupload/js/jquery.plupload.queue.min.js";s:4:"cd08";s:47:"contrib/plupload/js/plupload.browserplus.min.js";s:4:"9eef";s:41:"contrib/plupload/js/plupload.flash.min.js";s:4:"fc0d";s:38:"contrib/plupload/js/plupload.flash.swf";s:4:"0047";s:40:"contrib/plupload/js/plupload.full.min.js";s:4:"a7b6";s:40:"contrib/plupload/js/plupload.full.tmp.js";s:4:"f164";s:41:"contrib/plupload/js/plupload.gears.min.js";s:4:"99a3";s:41:"contrib/plupload/js/plupload.html4.min.js";s:4:"8240";s:41:"contrib/plupload/js/plupload.html5.min.js";s:4:"13bf";s:35:"contrib/plupload/js/plupload.min.js";s:4:"bac2";s:47:"contrib/plupload/js/plupload.silverlight.min.js";s:4:"6925";s:40:"mod_extjs/class.tx_fal_list_registry.php";s:4:"6284";s:18:"mod_extjs/conf.php";s:4:"953e";s:19:"mod_extjs/index.php";s:4:"0d49";s:18:"mod_extjs/list.gif";s:4:"adc5";s:23:"mod_extjs/locallang.xml";s:4:"3003";s:27:"mod_extjs/locallang_mod.xml";s:4:"3eb5";s:27:"mod_extjs/mod_template.html";s:4:"7c59";s:19:"res/css/fallist.css";s:4:"41d9";s:21:"res/js/Application.js";s:4:"8e42";s:27:"res/js/ComponentRegistry.js";s:4:"48e4";s:30:"res/js/DetailView/Bootstrap.js";s:4:"d70f";s:31:"res/js/DetailView/DetailView.js";s:4:"3a9a";s:28:"res/js/FileList/Bootstrap.js";s:4:"2e2e";s:27:"res/js/FileList/FileList.js";s:4:"410d";s:30:"res/js/FolderTree/Bootstrap.js";s:4:"cf7d";s:31:"res/js/FolderTree/FolderTree.js";s:4:"65e4";s:37:"res/js/SelectedFilesView/Bootstrap.js";s:4:"6d6d";s:45:"res/js/SelectedFilesView/SelectedFilesView.js";s:4:"ae1f";s:24:"res/js/Ui/EbBootstrap.js";s:4:"6932";s:25:"res/js/Ui/ModBootstrap.js";s:4:"1ca3";s:15:"res/js/Ui/Ui.js";s:4:"5c54";s:35:"res/js/plupload/ext.ux.plupload.css";s:4:"b22f";s:34:"res/js/plupload/ext.ux.plupload.js";s:4:"b13f";s:27:"res/js/plupload/plupload.js";s:4:"dd9a";s:36:"tasks/class.tx_fal_migrationtask.php";s:4:"3577";s:60:"tasks/class.tx_fal_migrationtask_additionalfieldprovider.php";s:4:"e986";s:25:"tests/tx_fal_fileTest.php";s:4:"49e7";s:54:"tests/storage/tx_fal_storage_filesystemstorageTest.php";s:4:"e4b6";}',
	'suggests' => array(
	),
);

?>
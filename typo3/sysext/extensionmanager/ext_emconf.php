<?php
/**********************************************************************
 * Extension Manager/Repository config file for ext "extensionmanager".
 *
 * Auto generated 25-10-2011 13:10
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ********************************************************************/
$EM_CONF[$_EXTKEY] = array(
	'title' => 'Extension Manager',
	'description' => 'TYPO3 Extension Manager',
	'category' => 'module',
	'shy' => 1,
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => 'classes',
	'doNotLoadInFE' => 1,
	'state' => 'stable',
	'internal' => 0,
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author' => '',
	'author_email' => '',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'version' => '6.0.0',
	'_md5_values_when_last_written' => 'a:104:{s:9:"ChangeLog";s:4:"bc4a";s:16:"ext_autoload.php";s:4:"0d1b";s:21:"ext_conf_template.txt";s:4:"0eb3";s:12:"ext_icon.gif";s:4:"2cc2";s:17:"ext_localconf.php";s:4:"98f4";s:14:"ext_tables.php";s:4:"217d";s:14:"ext_tables.sql";s:4:"5a42";s:25:"ext_tables_static+adt.sql";s:4:"3c1b";s:27:"classes/class.tx_em_api.php";s:4:"9d23";s:40:"classes/class.tx_em_extensionmanager.php";s:4:"33c0";s:16:"classes/conf.php";s:4:"d842";s:17:"classes/index.php";s:4:"3786";s:61:"classes/connection/class.tx_em_connection_extdirectserver.php";s:4:"4a46";s:59:"classes/connection/class.tx_em_connection_extdirectsoap.php";s:4:"84c5";s:50:"classes/connection/class.tx_em_connection_soap.php";s:4:"8cf4";s:49:"classes/connection/class.tx_em_connection_ter.php";s:4:"d732";s:41:"classes/database/class.tx_em_database.php";s:4:"c20c";s:53:"classes/exception/class.tx_em_connectionexception.php";s:4:"f33b";s:58:"classes/exception/class.tx_em_extensionimportexception.php";s:4:"b440";s:55:"classes/exception/class.tx_em_extensionxmlexception.php";s:4:"4251";s:52:"classes/exception/class.tx_em_mirrorxmlexception.php";s:4:"fc17";s:46:"classes/exception/class.tx_em_xmlexception.php";s:4:"169d";s:53:"classes/extensions/class.tx_em_extensions_details.php";s:4:"7864";s:50:"classes/extensions/class.tx_em_extensions_list.php";s:4:"d97c";s:59:"classes/import/class.tx_em_import_extensionlistimporter.php";s:4:"f3e4";s:56:"classes/import/class.tx_em_import_mirrorlistimporter.php";s:4:"226c";s:39:"classes/install/class.tx_em_install.php";s:4:"48ee";s:64:"classes/parser/class.tx_em_parser_extensionxmlabstractparser.php";s:4:"97cb";s:60:"classes/parser/ExtensionXmlPullParser.php";s:4:"9387";s:60:"classes/parser/ExtensionXmlPushParser.php";s:4:"b997";s:61:"classes/parser/MirrorXmlAbstractParser.php";s:4:"cf29";s:57:"classes/parser/MirrorXmlPullParser.php";s:4:"dc3e";s:57:"classes/parser/MirrorXmlPushParser.php";s:4:"defd";s:55:"classes/parser/XmlAbstractParser.php";s:4:"fa8a";s:54:"classes/parser/XmlParserFactory.php";s:4:"c167";s:55:"classes/reports/class.tx_em_reports_extensionstatus.php";s:4:"917c";s:45:"classes/repository/class.tx_em_repository.php";s:4:"1f94";s:53:"classes/repository/class.tx_em_repository_mirrors.php";s:4:"1c2f";s:53:"classes/repository/class.tx_em_repository_utility.php";s:4:"3719";s:41:"classes/settings/class.tx_em_settings.php";s:4:"2411";s:55:"classes/tasks/class.tx_em_tasks_updateextensionlist.php";s:4:"2fcf";s:35:"classes/tools/class.tx_em_tools.php";s:4:"731e";s:41:"classes/tools/class.tx_em_tools_unzip.php";s:4:"a24c";s:46:"classes/tools/class.tx_em_tools_xmlhandler.php";s:4:"4a11";s:49:"classes/translations/class.tx_em_translations.php";s:4:"7c60";s:61:"interfaces/interface.tx_em_index_checkdatabaseupdateshook.php";s:4:"50fb";s:41:"interfaces/interface.tx_em_renderhook.php";s:4:"16a4";s:22:"language/locallang.xlf";s:4:"b98d";s:18:"res/css/editor.css";s:4:"c89d";s:17:"res/css/t3_em.css";s:4:"39ba";s:24:"res/icons/arrow_redo.png";s:4:"343b";s:24:"res/icons/arrow_undo.png";s:4:"9a4f";s:20:"res/icons/cancel.png";s:4:"757a";s:22:"res/icons/download.png";s:4:"c5b2";s:19:"res/icons/drive.png";s:4:"9520";s:19:"res/icons/email.png";s:4:"af58";s:32:"res/icons/extension-required.png";s:4:"5619";s:25:"res/icons/filebrowser.png";s:4:"25b9";s:18:"res/icons/flag.png";s:4:"8798";s:19:"res/icons/image.png";s:4:"82ab";s:21:"res/icons/install.gif";s:4:"8d57";s:20:"res/icons/jslint.gif";s:4:"2e24";s:19:"res/icons/oodoc.gif";s:4:"744b";s:23:"res/icons/repupdate.png";s:4:"eaa5";s:20:"res/icons/server.png";s:4:"92ce";s:22:"res/icons/settings.png";s:4:"30a1";s:25:"res/icons/text_indent.png";s:4:"47f0";s:19:"res/icons/tools.png";s:4:"16d9";s:23:"res/icons/uninstall.gif";s:4:"a77f";s:16:"res/js/em_app.js";s:4:"752c";s:23:"res/js/em_components.js";s:4:"5205";s:18:"res/js/em_files.js";s:4:"5053";s:22:"res/js/em_languages.js";s:4:"2db8";s:20:"res/js/em_layouts.js";s:4:"6ce4";s:22:"res/js/em_locallist.js";s:4:"c8fe";s:27:"res/js/em_repositorylist.js";s:4:"8cbf";s:21:"res/js/em_settings.js";s:4:"c46e";s:16:"res/js/em_ter.js";s:4:"6029";s:18:"res/js/em_tools.js";s:4:"9077";s:22:"res/js/em_usertools.js";s:4:"2d10";s:33:"res/js/overrides/ext_overrides.js";s:4:"ab79";s:24:"res/js/ux/GridFilters.js";s:4:"7d2e";s:29:"res/js/ux/RowPanelExpander.js";s:4:"f239";s:22:"res/js/ux/TreeState.js";s:4:"ba48";s:27:"res/js/ux/custom_plugins.js";s:4:"1483";s:28:"res/js/ux/fileuploadfield.js";s:4:"b153";s:19:"res/js/ux/jslint.js";s:4:"8c75";s:24:"res/js/ux/searchfield.js";s:4:"4e83";s:29:"res/js/ux/css/GridFilters.css";s:4:"0cdc";s:27:"res/js/ux/css/RangeMenu.css";s:4:"a138";s:33:"res/js/ux/filter/BooleanFilter.js";s:4:"2834";s:30:"res/js/ux/filter/DateFilter.js";s:4:"c8fb";s:26:"res/js/ux/filter/Filter.js";s:4:"5601";s:30:"res/js/ux/filter/ListFilter.js";s:4:"88bc";s:33:"res/js/ux/filter/NumericFilter.js";s:4:"a32d";s:32:"res/js/ux/filter/StringFilter.js";s:4:"ce23";s:27:"res/js/ux/images/equals.png";s:4:"87b7";s:25:"res/js/ux/images/find.png";s:4:"9f1c";s:33:"res/js/ux/images/greater_than.png";s:4:"746c";s:30:"res/js/ux/images/less_than.png";s:4:"2fb7";s:38:"res/js/ux/images/sort_filtered_asc.gif";s:4:"9e7a";s:39:"res/js/ux/images/sort_filtered_desc.gif";s:4:"6d59";s:26:"res/js/ux/menu/ListMenu.js";s:4:"d5ff";s:27:"res/js/ux/menu/RangeMenu.js";s:4:"6a5e";}',
	'constraints' => array(
		'depends' => array(
			'typo3' => '6.0.0-0.0.0'
		),
		'conflicts' => array(),
		'suggests' => array()
	),
	'suggests' => array()
);
?>
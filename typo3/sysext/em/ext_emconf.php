<?php

########################################################################
# Extension Manager/Repository config file for ext "em".
#
# Auto generated 26-01-2011 18:42
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Extension Manager',
	'description' => 'TYPO3 Extension Manager',
	'category' => 'module',
	'shy' => 1,
	'dependencies' => 'cms',
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
	'author' => 'Steffen Kamper',
	'author_email' => 'steffen@typo3.org',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'version' => '4.5.0',
	'_md5_values_when_last_written' => 'a:101:{s:16:"ext_autoload.php";s:4:"5d4e";s:21:"ext_conf_template.txt";s:4:"582d";s:12:"ext_icon.gif";s:4:"2cc2";s:17:"ext_localconf.php";s:4:"fd30";s:14:"ext_tables.php";s:4:"3d63";s:14:"ext_tables.sql";s:4:"5a42";s:25:"ext_tables_static+adt.sql";s:4:"3c1b";s:27:"classes/class.tx_em_api.php";s:4:"c09b";s:40:"classes/class.tx_em_extensionmanager.php";s:4:"c343";s:16:"classes/conf.php";s:4:"d842";s:17:"classes/index.php";s:4:"9dab";s:61:"classes/connection/class.tx_em_connection_extdirectserver.php";s:4:"1b36";s:59:"classes/connection/class.tx_em_connection_extdirectsoap.php";s:4:"fda8";s:50:"classes/connection/class.tx_em_connection_soap.php";s:4:"decd";s:49:"classes/connection/class.tx_em_connection_ter.php";s:4:"813e";s:41:"classes/database/class.tx_em_database.php";s:4:"5852";s:54:"classes/exception/class.tx_em_connection_exception.php";s:4:"8b14";s:59:"classes/exception/class.tx_em_extensionimport_exception.php";s:4:"62e2";s:56:"classes/exception/class.tx_em_extensionxml_exception.php";s:4:"fcfe";s:53:"classes/exception/class.tx_em_mirrorxml_exception.php";s:4:"ad05";s:47:"classes/exception/class.tx_em_xml_exception.php";s:4:"3679";s:53:"classes/extensions/class.tx_em_extensions_details.php";s:4:"7b5a";s:50:"classes/extensions/class.tx_em_extensions_list.php";s:4:"5006";s:59:"classes/import/class.tx_em_import_extensionlistimporter.php";s:4:"25e8";s:56:"classes/import/class.tx_em_import_mirrorlistimporter.php";s:4:"6c5e";s:39:"classes/install/class.tx_em_install.php";s:4:"fa1e";s:64:"classes/parser/class.tx_em_parser_extensionxmlabstractparser.php";s:4:"fdb2";s:60:"classes/parser/class.tx_em_parser_extensionxmlpullparser.php";s:4:"8cb1";s:60:"classes/parser/class.tx_em_parser_extensionxmlpushparser.php";s:4:"728a";s:61:"classes/parser/class.tx_em_parser_mirrorxmlabstractparser.php";s:4:"8f3c";s:57:"classes/parser/class.tx_em_parser_mirrorxmlpullparser.php";s:4:"8092";s:57:"classes/parser/class.tx_em_parser_mirrorxmlpushparser.php";s:4:"7267";s:55:"classes/parser/class.tx_em_parser_xmlabstractparser.php";s:4:"6d00";s:54:"classes/parser/class.tx_em_parser_xmlparserfactory.php";s:4:"471c";s:55:"classes/reports/class.tx_em_reports_extensionstatus.php";s:4:"efb5";s:45:"classes/repository/class.tx_em_repository.php";s:4:"a953";s:53:"classes/repository/class.tx_em_repository_mirrors.php";s:4:"987c";s:53:"classes/repository/class.tx_em_repository_utility.php";s:4:"33ed";s:41:"classes/settings/class.tx_em_settings.php";s:4:"9c1d";s:55:"classes/tasks/class.tx_em_tasks_updateextensionlist.php";s:4:"b634";s:35:"classes/tools/class.tx_em_tools.php";s:4:"50c1";s:41:"classes/tools/class.tx_em_tools_unzip.php";s:4:"d183";s:46:"classes/tools/class.tx_em_tools_xmlhandler.php";s:4:"1bbd";s:49:"classes/translations/class.tx_em_translations.php";s:4:"935d";s:61:"interfaces/interface.tx_em_index_checkdatabaseupdateshook.php";s:4:"7178";s:22:"language/locallang.xml";s:4:"ccd7";s:18:"res/css/editor.css";s:4:"d4a2";s:17:"res/css/t3_em.css";s:4:"7897";s:24:"res/icons/arrow_redo.png";s:4:"343b";s:24:"res/icons/arrow_undo.png";s:4:"9a4f";s:20:"res/icons/cancel.png";s:4:"757a";s:22:"res/icons/download.png";s:4:"c5b2";s:19:"res/icons/drive.png";s:4:"9520";s:19:"res/icons/email.png";s:4:"af58";s:32:"res/icons/extension-required.png";s:4:"5619";s:25:"res/icons/filebrowser.png";s:4:"25b9";s:18:"res/icons/flag.png";s:4:"8798";s:19:"res/icons/image.png";s:4:"82ab";s:21:"res/icons/install.gif";s:4:"8d57";s:20:"res/icons/jslint.gif";s:4:"2e24";s:19:"res/icons/oodoc.gif";s:4:"744b";s:23:"res/icons/repupdate.png";s:4:"eaa5";s:20:"res/icons/server.png";s:4:"92ce";s:22:"res/icons/settings.png";s:4:"30a1";s:25:"res/icons/text_indent.png";s:4:"47f0";s:19:"res/icons/tools.png";s:4:"16d9";s:23:"res/icons/uninstall.gif";s:4:"a77f";s:16:"res/js/em_app.js";s:4:"53ec";s:23:"res/js/em_components.js";s:4:"4b6a";s:18:"res/js/em_files.js";s:4:"0208";s:22:"res/js/em_languages.js";s:4:"be1f";s:20:"res/js/em_layouts.js";s:4:"a934";s:22:"res/js/em_locallist.js";s:4:"0895";s:27:"res/js/em_repositorylist.js";s:4:"b6fa";s:21:"res/js/em_settings.js";s:4:"0264";s:16:"res/js/em_ter.js";s:4:"b7bf";s:18:"res/js/em_tools.js";s:4:"d187";s:22:"res/js/em_usertools.js";s:4:"694b";s:33:"res/js/overrides/ext_overrides.js";s:4:"2a8d";s:24:"res/js/ux/GridFilters.js";s:4:"95db";s:29:"res/js/ux/RowPanelExpander.js";s:4:"d4dd";s:27:"res/js/ux/custom_plugins.js";s:4:"d761";s:28:"res/js/ux/fileuploadfield.js";s:4:"06a5";s:19:"res/js/ux/jslint.js";s:4:"8c75";s:24:"res/js/ux/searchfield.js";s:4:"194f";s:29:"res/js/ux/css/GridFilters.css";s:4:"78fa";s:27:"res/js/ux/css/RangeMenu.css";s:4:"c5f6";s:33:"res/js/ux/filter/BooleanFilter.js";s:4:"d67f";s:30:"res/js/ux/filter/DateFilter.js";s:4:"1d6d";s:26:"res/js/ux/filter/Filter.js";s:4:"5e35";s:30:"res/js/ux/filter/ListFilter.js";s:4:"a9ab";s:33:"res/js/ux/filter/NumericFilter.js";s:4:"abb4";s:32:"res/js/ux/filter/StringFilter.js";s:4:"0923";s:27:"res/js/ux/images/equals.png";s:4:"87b7";s:25:"res/js/ux/images/find.png";s:4:"9f1c";s:33:"res/js/ux/images/greater_than.png";s:4:"746c";s:30:"res/js/ux/images/less_than.png";s:4:"2fb7";s:38:"res/js/ux/images/sort_filtered_asc.gif";s:4:"9e7a";s:39:"res/js/ux/images/sort_filtered_desc.gif";s:4:"6d59";s:26:"res/js/ux/menu/ListMenu.js";s:4:"d6c1";s:27:"res/js/ux/menu/RangeMenu.js";s:4:"cc46";}',
	'constraints' => array(
		'depends' => array(
			'cms' => '',
			'php' => '5.1.0-0.0.0',
			'typo3' => '4.5.0-0.0.0',
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
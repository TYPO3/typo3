<?php

########################################################################
# Extension Manager/Repository config file for ext "em".
#
# Auto generated 01-12-2010 23:35
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Ext Manager',
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
	'author' => 'Kasper Skaarhoj',
	'author_email' => 'kasperYYYY@typo3.com',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'version' => '2.0.2beta2',
	'_md5_values_when_last_written' => 'a:91:{s:16:"ext_autoload.php";s:4:"55c9";s:12:"ext_icon.gif";s:4:"2cc2";s:17:"ext_localconf.php";s:4:"fd30";s:14:"ext_tables.php";s:4:"4ef6";s:14:"ext_tables.sql";s:4:"5a42";s:25:"ext_tables_static+adt.sql";s:4:"3c1b";s:27:"classes/class.tx_em_api.php";s:4:"7b62";s:31:"classes/class.tx_em_develop.php";s:4:"98bd";s:40:"classes/class.tx_em_extensionmanager.php";s:4:"7ccc";s:16:"classes/conf.php";s:4:"d842";s:17:"classes/index.php";s:4:"7117";s:61:"classes/connection/class.tx_em_connection_extdirectserver.php";s:4:"ac98";s:59:"classes/connection/class.tx_em_connection_extdirectsoap.php";s:4:"daf8";s:50:"classes/connection/class.tx_em_connection_soap.php";s:4:"d1f9";s:49:"classes/connection/class.tx_em_connection_ter.php";s:4:"3d38";s:41:"classes/database/class.tx_em_database.php";s:4:"e6c0";s:54:"classes/exception/class.tx_em_connection_exception.php";s:4:"8b14";s:59:"classes/exception/class.tx_em_extensionimport_exception.php";s:4:"62e2";s:56:"classes/exception/class.tx_em_extensionxml_exception.php";s:4:"fcfe";s:53:"classes/exception/class.tx_em_mirrorxml_exception.php";s:4:"ad05";s:47:"classes/exception/class.tx_em_xml_exception.php";s:4:"3679";s:53:"classes/extensions/class.tx_em_extensions_details.php";s:4:"ae75";s:50:"classes/extensions/class.tx_em_extensions_list.php";s:4:"f74a";s:59:"classes/import/class.tx_em_import_extensionlistimporter.php";s:4:"62c8";s:56:"classes/import/class.tx_em_import_mirrorlistimporter.php";s:4:"3ea7";s:39:"classes/install/class.tx_em_install.php";s:4:"0c5c";s:64:"classes/parser/class.tx_em_parser_extensionxmlabstractparser.php";s:4:"d268";s:60:"classes/parser/class.tx_em_parser_extensionxmlpullparser.php";s:4:"c604";s:60:"classes/parser/class.tx_em_parser_extensionxmlpushparser.php";s:4:"3bcc";s:61:"classes/parser/class.tx_em_parser_mirrorxmlabstractparser.php";s:4:"802b";s:57:"classes/parser/class.tx_em_parser_mirrorxmlpullparser.php";s:4:"1054";s:57:"classes/parser/class.tx_em_parser_mirrorxmlpushparser.php";s:4:"b5f5";s:55:"classes/parser/class.tx_em_parser_xmlabstractparser.php";s:4:"911a";s:54:"classes/parser/class.tx_em_parser_xmlparserfactory.php";s:4:"f56a";s:45:"classes/repository/class.tx_em_repository.php";s:4:"5dbd";s:53:"classes/repository/class.tx_em_repository_mirrors.php";s:4:"dc40";s:53:"classes/repository/class.tx_em_repository_utility.php";s:4:"886d";s:41:"classes/settings/class.tx_em_settings.php";s:4:"68fc";s:55:"classes/tasks/class.tx_em_tasks_updateextensionlist.php";s:4:"7e39";s:35:"classes/tools/class.tx_em_tools.php";s:4:"050f";s:41:"classes/tools/class.tx_em_tools_unzip.php";s:4:"ffe1";s:46:"classes/tools/class.tx_em_tools_xmlhandler.php";s:4:"48bb";s:49:"classes/translations/class.tx_em_translations.php";s:4:"7a8b";s:61:"interfaces/interface.tx_em_index_checkdatabaseupdateshook.php";s:4:"7178";s:22:"language/locallang.xml";s:4:"2732";s:17:"res/css/t3_em.css";s:4:"274a";s:20:"res/icons/cancel.png";s:4:"757a";s:22:"res/icons/download.png";s:4:"c5b2";s:19:"res/icons/drive.png";s:4:"9520";s:25:"res/icons/filebrowser.png";s:4:"25b9";s:18:"res/icons/flag.png";s:4:"8798";s:19:"res/icons/image.png";s:4:"82ab";s:21:"res/icons/install.gif";s:4:"8d57";s:19:"res/icons/oodoc.gif";s:4:"744b";s:20:"res/icons/server.png";s:4:"92ce";s:22:"res/icons/settings.png";s:4:"30a1";s:19:"res/icons/tools.png";s:4:"16d9";s:23:"res/icons/uninstall.gif";s:4:"a77f";s:16:"res/js/em_app.js";s:4:"38e3";s:23:"res/js/em_components.js";s:4:"6517";s:18:"res/js/em_files.js";s:4:"efa9";s:22:"res/js/em_languages.js";s:4:"b9b0";s:20:"res/js/em_layouts.js";s:4:"0c4f";s:22:"res/js/em_locallist.js";s:4:"e321";s:27:"res/js/em_repositorylist.js";s:4:"7096";s:21:"res/js/em_settings.js";s:4:"d657";s:16:"res/js/em_ter.js";s:4:"a237";s:18:"res/js/em_tools.js";s:4:"8b1b";s:22:"res/js/em_usertools.js";s:4:"37ce";s:33:"res/js/overrides/ext_overrides.js";s:4:"3bc1";s:24:"res/js/ux/GridFilters.js";s:4:"b42c";s:27:"res/js/ux/custom_plugins.js";s:4:"a27f";s:28:"res/js/ux/fileuploadfield.js";s:4:"06a5";s:29:"res/js/ux/rowpanelexpander.js";s:4:"fc07";s:24:"res/js/ux/searchfield.js";s:4:"41a1";s:29:"res/js/ux/css/GridFilters.css";s:4:"78fa";s:27:"res/js/ux/css/RangeMenu.css";s:4:"c5f6";s:33:"res/js/ux/filter/BooleanFilter.js";s:4:"d67f";s:30:"res/js/ux/filter/DateFilter.js";s:4:"1d6d";s:26:"res/js/ux/filter/Filter.js";s:4:"5e35";s:30:"res/js/ux/filter/ListFilter.js";s:4:"a9ab";s:33:"res/js/ux/filter/NumericFilter.js";s:4:"abb4";s:32:"res/js/ux/filter/StringFilter.js";s:4:"0923";s:27:"res/js/ux/images/equals.png";s:4:"87b7";s:25:"res/js/ux/images/find.png";s:4:"9f1c";s:33:"res/js/ux/images/greater_than.png";s:4:"746c";s:30:"res/js/ux/images/less_than.png";s:4:"2fb7";s:38:"res/js/ux/images/sort_filtered_asc.gif";s:4:"9e7a";s:39:"res/js/ux/images/sort_filtered_desc.gif";s:4:"6d59";s:26:"res/js/ux/menu/ListMenu.js";s:4:"d6c1";s:27:"res/js/ux/menu/RangeMenu.js";s:4:"cc46";};',
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
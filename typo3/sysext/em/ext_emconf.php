<?php

########################################################################
# Extension Manager/Repository config file for ext "em".
#
# Auto generated 11-11-2010 09:28
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
	'module' => 'view',
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
	'version' => '2.0.0',
	'_md5_values_when_last_written' => 'a:86:{s:16:"ext_autoload.php";s:4:"b7e4";s:12:"ext_icon.gif";s:4:"3a30";s:17:"ext_localconf.php";s:4:"fd30";s:14:"ext_tables.php";s:4:"c5d9";s:31:"classes/class.tx_em_develop.php";s:4:"c4a5";s:40:"classes/class.tx_em_extensionmanager.php";s:4:"53ff";s:16:"classes/conf.php";s:4:"a8a0";s:17:"classes/index.php";s:4:"7fa2";s:61:"classes/connection/class.tx_em_connection_extdirectserver.php";s:4:"ee87";s:50:"classes/connection/class.tx_em_connection_soap.php";s:4:"1623";s:49:"classes/connection/class.tx_em_connection_ter.php";s:4:"e818";s:41:"classes/database/class.tx_em_database.php";s:4:"67e9";s:54:"classes/exception/class.tx_em_connection_exception.php";s:4:"8b14";s:59:"classes/exception/class.tx_em_extensionimport_exception.php";s:4:"62e2";s:56:"classes/exception/class.tx_em_extensionxml_exception.php";s:4:"fcfe";s:53:"classes/exception/class.tx_em_mirrorxml_exception.php";s:4:"ad05";s:47:"classes/exception/class.tx_em_xml_exception.php";s:4:"3679";s:53:"classes/extensions/class.tx_em_extensions_details.php";s:4:"5b9b";s:50:"classes/extensions/class.tx_em_extensions_list.php";s:4:"2b68";s:59:"classes/import/class.tx_em_import_extensionlistimporter.php";s:4:"3b4d";s:56:"classes/import/class.tx_em_import_mirrorlistimporter.php";s:4:"c512";s:39:"classes/install/class.tx_em_install.php";s:4:"c861";s:64:"classes/parser/class.tx_em_parser_extensionxmlabstractparser.php";s:4:"d268";s:60:"classes/parser/class.tx_em_parser_extensionxmlpullparser.php";s:4:"c604";s:60:"classes/parser/class.tx_em_parser_extensionxmlpushparser.php";s:4:"3bcc";s:61:"classes/parser/class.tx_em_parser_mirrorxmlabstractparser.php";s:4:"802b";s:57:"classes/parser/class.tx_em_parser_mirrorxmlpullparser.php";s:4:"1054";s:55:"classes/parser/class.tx_em_parser_xmlabstractparser.php";s:4:"822b";s:54:"classes/parser/class.tx_em_parser_xmlparserfactory.php";s:4:"ae4d";s:45:"classes/repository/class.tx_em_repository.php";s:4:"2c1d";s:53:"classes/repository/class.tx_em_repository_mirrors.php";s:4:"dc40";s:53:"classes/repository/class.tx_em_repository_utility.php";s:4:"134d";s:41:"classes/settings/class.tx_em_settings.php";s:4:"bfb9";s:55:"classes/tasks/class.tx_em_tasks_updateextensionlist.php";s:4:"a382";s:35:"classes/tools/class.tx_em_tools.php";s:4:"fd03";s:41:"classes/tools/class.tx_em_tools_unzip.php";s:4:"ffe1";s:46:"classes/tools/class.tx_em_tools_xmlhandler.php";s:4:"48bb";s:49:"classes/translations/class.tx_em_translations.php";s:4:"7a8b";s:61:"interfaces/interface.tx_em_index_checkdatabaseupdateshook.php";s:4:"7178";s:22:"language/locallang.xml";s:4:"5d2d";s:17:"res/css/t3_em.css";s:4:"fcb0";s:20:"res/icons/cancel.png";s:4:"757a";s:22:"res/icons/download.png";s:4:"c5b2";s:19:"res/icons/drive.png";s:4:"9520";s:25:"res/icons/filebrowser.png";s:4:"25b9";s:18:"res/icons/flag.png";s:4:"8798";s:19:"res/icons/image.png";s:4:"82ab";s:21:"res/icons/install.gif";s:4:"8d57";s:19:"res/icons/oodoc.gif";s:4:"744b";s:20:"res/icons/server.png";s:4:"92ce";s:22:"res/icons/settings.png";s:4:"30a1";s:19:"res/icons/tools.png";s:4:"16d9";s:23:"res/icons/uninstall.gif";s:4:"a77f";s:15:"res/js/t3_em.js";s:4:"7cc8";s:23:"res/js/t3_em_emtools.js";s:4:"7c31";s:26:"res/js/t3_em_extdetails.js";s:4:"dab5";s:24:"res/js/t3_em_extfiles.js";s:4:"1ba7";s:28:"res/js/t3_em_extterupload.js";s:4:"3638";s:25:"res/js/t3_em_languages.js";s:4:"b31d";s:34:"res/js/t3_em_localextensionlist.js";s:4:"1317";s:35:"res/js/t3_em_remoteextensionlist.js";s:4:"f1f2";s:26:"res/js/t3_em_repository.js";s:4:"fede";s:24:"res/js/t3_em_settings.js";s:4:"5bd5";s:21:"res/js/t3_em_tools.js";s:4:"6302";s:33:"res/js/overrides/ext_overrides.js";s:4:"3bc1";s:24:"res/js/ux/GridFilters.js";s:4:"b42c";s:27:"res/js/ux/custom_plugins.js";s:4:"e4c1";s:28:"res/js/ux/fileuploadfield.js";s:4:"06a5";s:29:"res/js/ux/rowpanelexpander.js";s:4:"fc07";s:24:"res/js/ux/searchfield.js";s:4:"41a1";s:29:"res/js/ux/css/GridFilters.css";s:4:"78fa";s:27:"res/js/ux/css/RangeMenu.css";s:4:"c5f6";s:33:"res/js/ux/filter/BooleanFilter.js";s:4:"d67f";s:30:"res/js/ux/filter/DateFilter.js";s:4:"1d6d";s:26:"res/js/ux/filter/Filter.js";s:4:"5e35";s:30:"res/js/ux/filter/ListFilter.js";s:4:"a9ab";s:33:"res/js/ux/filter/NumericFilter.js";s:4:"abb4";s:32:"res/js/ux/filter/StringFilter.js";s:4:"0923";s:27:"res/js/ux/images/equals.png";s:4:"87b7";s:25:"res/js/ux/images/find.png";s:4:"9f1c";s:33:"res/js/ux/images/greater_than.png";s:4:"746c";s:30:"res/js/ux/images/less_than.png";s:4:"2fb7";s:38:"res/js/ux/images/sort_filtered_asc.gif";s:4:"9e7a";s:39:"res/js/ux/images/sort_filtered_desc.gif";s:4:"6d59";s:26:"res/js/ux/menu/ListMenu.js";s:4:"d6c1";s:27:"res/js/ux/menu/RangeMenu.js";s:4:"cc46";}',
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
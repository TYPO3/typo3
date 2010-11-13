<?php

########################################################################
# Extension Manager/Repository config file for ext "em".
#
# Auto generated 13-11-2010 01:00
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
	'version' => '2.0.0',
	'_md5_values_when_last_written' => 'a:40:{s:16:"ext_autoload.php";s:4:"e526";s:12:"ext_icon.gif";s:4:"3a30";s:17:"ext_localconf.php";s:4:"fd30";s:14:"ext_tables.php";s:4:"c5d9";s:14:"ext_tables.sql";s:4:"4997";s:25:"ext_tables_static+adt.sql";s:4:"3c1b";s:16:"classes/conf.php";s:4:"a8a0";s:17:"classes/index.php";s:4:"808a";s:61:"classes/connection/class.tx_em_connection_extdirectserver.php";s:4:"50cf";s:50:"classes/connection/class.tx_em_connection_soap.php";s:4:"1623";s:49:"classes/connection/class.tx_em_connection_ter.php";s:4:"e818";s:41:"classes/database/class.tx_em_database.php";s:4:"67e9";s:54:"classes/exception/class.tx_em_connection_exception.php";s:4:"8b14";s:59:"classes/exception/class.tx_em_extensionimport_exception.php";s:4:"62e2";s:56:"classes/exception/class.tx_em_extensionxml_exception.php";s:4:"fcfe";s:53:"classes/exception/class.tx_em_mirrorxml_exception.php";s:4:"ad05";s:47:"classes/exception/class.tx_em_xml_exception.php";s:4:"3679";s:53:"classes/extensions/class.tx_em_extensions_details.php";s:4:"5b9b";s:50:"classes/extensions/class.tx_em_extensions_list.php";s:4:"2b68";s:59:"classes/import/class.tx_em_import_extensionlistimporter.php";s:4:"dd13";s:56:"classes/import/class.tx_em_import_mirrorlistimporter.php";s:4:"c512";s:39:"classes/install/class.tx_em_install.php";s:4:"c861";s:64:"classes/parser/class.tx_em_parser_extensionxmlabstractparser.php";s:4:"d268";s:60:"classes/parser/class.tx_em_parser_extensionxmlpullparser.php";s:4:"c604";s:60:"classes/parser/class.tx_em_parser_extensionxmlpushparser.php";s:4:"3bcc";s:61:"classes/parser/class.tx_em_parser_mirrorxmlabstractparser.php";s:4:"802b";s:57:"classes/parser/class.tx_em_parser_mirrorxmlpullparser.php";s:4:"1054";s:55:"classes/parser/class.tx_em_parser_xmlabstractparser.php";s:4:"822b";s:54:"classes/parser/class.tx_em_parser_xmlparserfactory.php";s:4:"ae4d";s:45:"classes/repository/class.tx_em_repository.php";s:4:"58c6";s:53:"classes/repository/class.tx_em_repository_mirrors.php";s:4:"dc40";s:53:"classes/repository/class.tx_em_repository_utility.php";s:4:"ac3a";s:41:"classes/settings/class.tx_em_settings.php";s:4:"aaf1";s:55:"classes/tasks/class.tx_em_tasks_updateextensionlist.php";s:4:"f022";s:35:"classes/tools/class.tx_em_tools.php";s:4:"fd03";s:41:"classes/tools/class.tx_em_tools_unzip.php";s:4:"ffe1";s:46:"classes/tools/class.tx_em_tools_xmlhandler.php";s:4:"48bb";s:49:"classes/translations/class.tx_em_translations.php";s:4:"7a8b";s:61:"interfaces/interface.tx_em_index_checkdatabaseupdateshook.php";s:4:"7178";s:22:"language/locallang.xml";s:4:"5d2d";}',
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
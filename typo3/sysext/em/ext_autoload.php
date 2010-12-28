<?php
$emClassesPath = PATH_site . 'typo3/sysext/em/classes/';
$emInterfacesPath = PATH_site . 'typo3/sysext/em/interfaces/';
return array(
	'tx_em_index_checkdatabaseupdateshook' => $emInterfacesPath . 'interface.tx_em_index_checkdatabaseupdateshook.php',

	'sc_mod_tools_em_index' => $emClassesPath . 'index.php',
	'tx_em_api' => $emClassesPath . 'class.tx_em_api.php',

	'tx_em_connection_ter' => $emClassesPath . 'connection/class.tx_em_connection_ter.php',
	'tx_em_connection_soap' => $emClassesPath . 'connection/class.tx_em_connection_soap.php',
	'tx_em_connection_extdirectserver' => $emClassesPath . 'connection/class.tx_em_connection_extdirectserver.php',
	'tx_em_connection_extdirectsoap' => $emClassesPath . 'connection/class.tx_em_connection_extdirectsoap.php',

	'tx_em_database' => $emClassesPath . 'database/class.tx_em_database.php',

	'tx_em_xmlexception' => $emClassesPath . 'exception/class.tx_em_xml_exception.php',
	'tx_em_connection_exception' => $emClassesPath . 'exception/class.tx_em_connection_exception.php',
	'tx_em_extensionxml_exception' => $emClassesPath . 'exception/class.tx_em_extensionxml_exception.php',
	'tx_em_extensionimport_exception' => $emClassesPath . 'exception/class.tx_em_extensionimport_exception.php',
	'tx_em_mirrorxml_exception' => $emClassesPath . 'exception/class.tx_em_mirrorxml_exception.php',

	'tx_em_extensions_list' => $emClassesPath . 'extensions/class.tx_em_extensions_list.php',
	'tx_em_extensions_details' => $emClassesPath . 'extensions/class.tx_em_extensions_details.php',

	'tx_em_import_extensionlistimporter' => $emClassesPath . 'import/class.tx_em_import_extensionlistimporter.php',
	'tx_em_import_mirrorlistimporter' => $emClassesPath . 'import/class.tx_em_import_mirrorlistimporter.php',

	'tx_em_install' => $emClassesPath . 'install/class.tx_em_install.php',

	'tx_em_parser_xmlabstractparser' => $emClassesPath . 'parser/class.tx_em_parser_xmlabstractparser.php',
	'tx_em_parser_extensionxmlabstractparser' => $emClassesPath . 'parser/class.tx_em_parser_extensionxmlabstractparser.php',
	'tx_em_parser_mirrorxmlabstractparser' => $emClassesPath . 'parser/class.tx_em_parser_mirrorxmlabstractparser.php',
	'tx_em_parser_xmlparserfactory' => $emClassesPath . 'parser/class.tx_em_parser_xmlparserfactory.php',
	'tx_em_parser_mirrorxmlpullparser' => $emClassesPath . 'parser/class.tx_em_parser_mirrorxmlpullparser.php',
	'tx_em_parser_mirrorxmlpushparser' => $emClassesPath . 'parser/class.tx_em_parser_mirrorxmlpushparser.php',
	'tx_em_parser_extensionxmlpullparser' => $emClassesPath . 'parser/class.tx_em_parser_extensionxmlpullparser.php',
	'tx_em_parser_extensionxmlpushparser' => $emClassesPath . 'parser/class.tx_em_parser_extensionxmlpushparser.php',

	'tx_em_repository' => $emClassesPath . 'repository/class.tx_em_repository.php',
	'tx_em_repository_mirrors' => $emClassesPath . 'repository/class.tx_em_repository_mirrors.php',
	'tx_em_repository_utility' => $emClassesPath . 'repository/class.tx_em_repository_utility.php',

	'tx_em_settings' => $emClassesPath . 'settings/class.tx_em_settings.php',

	'tx_em_tasks_updateextensionlist' => $emClassesPath . 'tasks/class.tx_em_tasks_updateextensionlist.php',

	'tx_em_tools' => $emClassesPath . 'tools/class.tx_em_tools.php',
	'tx_em_tools_unzip' => $emClassesPath . 'tools/class.tx_em_tools_unzip.php',
	'tx_em_tools_xmlhandler' => $emClassesPath . 'tools/class.tx_em_tools_xmlhandler.php',

	'tx_em_translations' => $emClassesPath . 'translations/class.tx_em_translations.php',

	'tx_em_reports_extensionstatus' => $emClassesPath . 'reports/class.tx_em_reports_extensionstatus.php',


	'tx_em_develop' => $emClassesPath . 'class.tx_em_develop.php',
	'tx_em_extensionmanager' => $emClassesPath . 'class.tx_em_extensionmanager.php',

);
?>

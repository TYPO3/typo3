<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');

require_once(t3lib_extMgm::extPath('extbase') . 'Classes/Dispatcher.php');
require_once(t3lib_extMgm::extPath('extbase') . 'Classes/Utility/Extension.php');

	// Register caches if not already done in localconf.php or a previously loaded extension.
	// We do not set frontend and backend: The cache manager uses t3lib_cache_frontend_VariableFrontend
	// and t3lib_cache_backend_DbBackend by default if not set otherwise.
	// This default is perfectly fine for our reflection and object cache.
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_reflection'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_reflection'] = array();
}
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_object'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_object'] = array();
}

// We need to set the default implementation for Storage Backend & Query Settings
// the code below is NO PUBLIC API! It's just to make sure that
// Extbase works correctly in the backend if the page tree is empty or no
// template is defined.
$extbaseObjectContainer = t3lib_div::makeInstance('Tx_Extbase_Object_Container_Container'); // Singleton
$extbaseObjectContainer->registerImplementation('Tx_Extbase_Persistence_Storage_BackendInterface', 'Tx_Extbase_Persistence_Storage_Typo3DbBackend');
$extbaseObjectContainer->registerImplementation('Tx_Extbase_Persistence_QuerySettingsInterface', 'Tx_Extbase_Persistence_Typo3QuerySettings');
unset($extbaseObjectContainer);

// Register type converters
Tx_Extbase_Utility_Extension::registerTypeConverter('Tx_Extbase_Property_TypeConverter_ArrayConverter');
Tx_Extbase_Utility_Extension::registerTypeConverter('Tx_Extbase_Property_TypeConverter_BooleanConverter');
Tx_Extbase_Utility_Extension::registerTypeConverter('Tx_Extbase_Property_TypeConverter_DateTimeConverter');
Tx_Extbase_Utility_Extension::registerTypeConverter('Tx_Extbase_Property_TypeConverter_FloatConverter');
Tx_Extbase_Utility_Extension::registerTypeConverter('Tx_Extbase_Property_TypeConverter_IntegerConverter');
Tx_Extbase_Utility_Extension::registerTypeConverter('Tx_Extbase_Property_TypeConverter_ObjectStorageConverter');
Tx_Extbase_Utility_Extension::registerTypeConverter('Tx_Extbase_Property_TypeConverter_PersistentObjectConverter');
Tx_Extbase_Utility_Extension::registerTypeConverter('Tx_Extbase_Property_TypeConverter_StringConverter');
# $GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'EXT:extbase/Classes/Persistence/Hook/TCEMainValueObjectUpdater.php:tx_Extbase_Persistence_Hook_TCEMainValueObjectUpdater';

if (TYPO3_MODE === 'BE') {
	// registers Extbase at the cli_dispatcher with key "extbase".
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['GLOBAL']['cliKeys']['extbase'] = array(
		'EXT:extbase/Scripts/CommandLineLauncher.php',
		'_CLI_lowlevel'
	);
	// register help command
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = 'Tx_Extbase_Command_HelpCommandController';
}
?>
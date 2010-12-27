<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

require_once(t3lib_extMgm::extPath('extbase') . 'Classes/Dispatcher.php');
require_once(t3lib_extMgm::extPath('extbase') . 'Classes/Utility/Extension.php');

// use own cache tables
// Reflection cache:
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['cache_extbase_reflection'] = array(
	'frontend' => 't3lib_cache_frontend_VariableFrontend',
	'backend' => 't3lib_cache_backend_DbBackend',
	'options' => array(
		'cacheTable' => 'tx_extbase_cache_reflection',
		'tagsTable' => 'tx_extbase_cache_reflection_tags',
	),
);
// Object container cache:
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['cache_extbase_object'] = array(
	'frontend' => 't3lib_cache_frontend_VariableFrontend',
	'backend' => 't3lib_cache_backend_DbBackend',
	'options' => array(
		'cacheTable' => 'tx_extbase_cache_object',
		'tagsTable' => 'tx_extbase_cache_object_tags',
	),
);

// We need to set the default implementation for the Storage Backend
// the code below is NO PUBLIC API! It's just to make sure that
// Extbase works correctly in the backend if the page tree is empty or no
// template is defined.
$extbaseObjectContainer = t3lib_div::makeInstance('Tx_Extbase_Object_Container_Container'); // Singleton
$extbaseObjectContainer->registerImplementation('Tx_Extbase_Persistence_Storage_BackendInterface', 'Tx_Extbase_Persistence_Storage_Typo3DbBackend');
unset($extbaseObjectContainer);

# $GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'EXT:extbase/Classes/Persistence/Hook/TCEMainValueObjectUpdater.php:tx_Extbase_Persistence_Hook_TCEMainValueObjectUpdater';
?>
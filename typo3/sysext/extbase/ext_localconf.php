<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

require_once(t3lib_extMgm::extPath('extbase') . 'Classes/Dispatcher.php');
require_once(t3lib_extMgm::extPath('extbase') . 'Classes/Utility/Extension.php');

// use own cache table
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['cache_extbase_reflection'] = array(
	'frontend' => 't3lib_cache_frontend_VariableFrontend',
	'backend' => 't3lib_cache_backend_DbBackend',
	'options' => array(
		'cacheTable' => 'tx_extbase_cache_reflection',
		'tagsTable' => 'tx_extbase_cache_reflection_tags',
	),
);

# $GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'EXT:extbase/Classes/Persistence/Hook/TCEMainValueObjectUpdater.php:tx_Extbase_Persistence_Hook_TCEMainValueObjectUpdater';
?>
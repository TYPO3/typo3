<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

require_once(t3lib_extMgm::extPath('extbase') . 'Classes/Dispatcher.php');
spl_autoload_register(array('Tx_Extbase_Dispatcher', 'autoloadClass'));

$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['Tx_Extbase_Reflection'] = array(
	'backend' => 't3lib_cache_backend_FileBackend',
	'options' => array(
		'cacheDirectory' => 'typo3temp/cache/Tx_Extbase_Reflection/'
	),
);

# $GLOBALS ['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'EXT:extbase/Classes/Persistence/Hook/TCEMainValueObjectUpdater.php:tx_Extbase_Persistence_Hook_TCEMainValueObjectUpdater';
?>
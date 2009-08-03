<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');

// use own cache table
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['cache_extbase_reflection'] = array(
	'backend' => 't3lib_cache_backend_DbBackend',
	'options' => array(
		'cacheTable' => 'cache_extbase_reflection'
	),
);

if (TYPO3_MODE == 'BE') {
	// register the cache in BE so it will be cleared with "clear all cahces"
	try {
		$GLOBALS['typo3CacheFactory']->create(
			'cache_extbase_reflection',
			't3lib_cache_frontend_VariableFrontend',
			$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['cache_extbase_reflection']['backend'],
			$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['cache_extbase_reflection']['options']
		);
	} catch(t3lib_cache_exception_NoSuchCache $exception) {

	}
}

?>

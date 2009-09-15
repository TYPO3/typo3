<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');


if (TYPO3_MODE == 'BE') {
	// register the cache in BE so it will be cleared with "clear all caches"
	try {
		$GLOBALS['typo3CacheFactory']->create(
			'tx_extbase_cache_reflection',
			't3lib_cache_frontend_VariableFrontend',
			$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['cache_extbase_reflection']['backend'],
			$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['cache_extbase_reflection']['options']
		);
	} catch(t3lib_cache_exception_NoSuchCache $exception) {

	}
	
	$TBE_MODULES['_dispatcher'][] = t3lib_div::makeInstance('Tx_Extbase_Dispatcher');
}

?>
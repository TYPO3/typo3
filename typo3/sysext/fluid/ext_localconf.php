<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');

	// Register caches if not already done in localconf.php or a previously loaded extension.
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['fluid_template'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['fluid_template'] = array(
		'backend' => 't3lib_cache_backend_FileBackend',
		'frontend' => 't3lib_cache_frontend_PhpFrontend',
	);
}
?>
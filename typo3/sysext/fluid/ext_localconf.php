<?php
defined('TYPO3_MODE') or die();

// Register caches if not already done in localconf.php or a previously loaded extension.
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['fluid_template'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['fluid_template'] = array(
		'backend' => \TYPO3\CMS\Core\Cache\Backend\FileBackend::class,
		'frontend' => \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend::class,
		'groups' => array('system')
	);
}

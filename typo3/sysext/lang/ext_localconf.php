<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

	// Register cache lang_l10n_cache
if (!is_array($TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['lang_l10n_cache'])) {
    $TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['lang_l10n_cache'] = array();
}

?>
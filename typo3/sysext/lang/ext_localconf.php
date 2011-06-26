<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

	// Register cache lang_l10n_cache
if (!is_array($TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['lang_l10n_cache'])) {
    $TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['lang_l10n_cache'] = array();
}

		// Define string frontend as default frontend, this must be set with TYPO3 4.5 and below
if (!isset($TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['lang_l10n_cache']['frontend'])) {
    $TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['lang_l10n_cache']['frontend'] = 't3lib_cache_frontend_StringFrontend';
}

if (t3lib_div::int_from_ver(TYPO3_version) <= '4005999') {
        // Define database backend as backend for 4.5 and below (default in 4.6)
    if (!isset($TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['lang_l10n_cache']['backend'])) {
        $TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['lang_l10n_cache']['backend'] = 't3lib_cache_backend_DbBackend';
    }
        // Define data and tags table for 4.5 and below (obsolete in 4.6)
    if (!isset($TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['lang_l10n_cache']['options'])) {
        $TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['lang_l10n_cache']['options'] = array();
    }
    if (!isset($TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['lang_l10n_cache']['options']['cacheTable'])) {
        $TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['lang_l10n_cache']['options']['cacheTable'] = 'tx_lang_l10n_cache';
    }
    if (!isset($TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['lang_l10n_cache']['options']['tagsTable'])) {
        $TYPO3_CONF_VARS['SYS']['caching']['cacheConfigurations']['lang_l10n_cache']['options']['tagsTable'] = 'tx_lang_l10n_cache_tags';
    }
}

?>
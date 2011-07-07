<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

	// Register cache t3lib_l10n
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['t3lib_l10n'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['t3lib_l10n'] = array();
}

if (!isset($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['t3lib_l10n']['backend'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['t3lib_l10n']['backend'] = 't3lib_cache_backend_FileBackend';
}

	// Register Clear Cache Menu hook
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['additionalBackendItems']['cacheActions']['clearLangCache'] = 'EXT:lang/hooks/clearcache/class.tx_lang_clearcachemenu.php:&tx_lang_clearcachemenu';

	// Register Ajax call
$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['lang::clearCache'] = 'EXT:lang/hooks/clearcache/class.tx_lang_clearcache.php:tx_lang_clearcache->clearCache';

?>
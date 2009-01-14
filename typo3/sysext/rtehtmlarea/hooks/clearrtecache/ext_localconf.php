<?php
	// Register Clear Cache Menu hook
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['additionalBackendItems']['cacheActions']['clearRTECache'] = 'EXT:rtehtmlarea/hooks/clearrtecache/class.tx_rtehtmlarea_clearcachemenu.php:&tx_rtehtmlarea_clearcachemenu';

	// Register Ajax call
$TYPO3_CONF_VARS['BE']['AJAX']['rtehtmlarea::clearTempDir'] = 'EXT:rtehtmlarea/hooks/clearrtecache/class.tx_rtehtmlarea_clearrtecache.php:tx_rtehtmlarea_clearrtecache->clearTempDir';

?>

<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Core\\Database\\DatabaseConnection'] = array('className' => 'TYPO3\\CMS\\Dbal\\Database\\DatabaseConnection');
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Core\\Database\\SqlParser'] = array('className' => 'TYPO3\\CMS\\Dbal\\Database\\SqlParser');
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Recordlist\\RecordList\\DatabaseRecordList'] = array('className' => 'TYPO3\\CMS\\Dbal\\RecordList\\DatabaseRecordList');

// Register a hook for the installer
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install/mod/class.tx_install.php']['stepOutput'][] = 'EXT:dbal/class.tx_dbal_installtool.php:tx_dbal_installtool';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install/mod/class.tx_install.php']['writeLocalconf'][] = 'EXT:dbal/class.tx_dbal_installtool.php:tx_dbal_installtool';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install/mod/class.tx_install.php']['requiredPhpModules'][] = 'EXT:dbal/class.tx_dbal_installtool.php:tx_dbal_installtool';
// Register a hook for the extension manager
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/mod/tools/em/index.php']['checkDBupdates'][] = 'EXT:dbal/class.tx_dbal_em.php:tx_dbal_em';
// Register caches if not already done in localconf.php or a previously loaded extension.
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['dbal'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['dbal'] = array(
		'backend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\TransientMemoryBackend'
	);
}
?>
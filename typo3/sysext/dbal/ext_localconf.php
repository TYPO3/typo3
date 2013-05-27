<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Core\\Database\\DatabaseConnection'] = array('className' => 'TYPO3\\CMS\\Dbal\\Database\\DatabaseConnection');
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Core\\Database\\SqlParser'] = array('className' => 'TYPO3\\CMS\\Dbal\\Database\\SqlParser');
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Recordlist\\RecordList\\DatabaseRecordList'] = array('className' => 'TYPO3\\CMS\\Dbal\\RecordList\\DatabaseRecordList');

// Register a hook for the installer
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install/mod/class.tx_install.php']['stepOutput'][] = 'TYPO3\\CMS\\Dbal\\Hooks\\InstallToolHooks';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install/mod/class.tx_install.php']['writeLocalconf'][] = 'TYPO3\\CMS\\Dbal\\Hooks\\InstallToolHooks';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install/mod/class.tx_install.php']['requiredPhpModules'][] = 'TYPO3\\CMS\\Dbal\\Hooks\\InstallToolHooks';

// Register a hook for the extension manager
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/mod/tools/em/index.php']['checkDBupdates'][] = 'TYPO3\\CMS\\Dbal\\Hooks\\ExtensionManagerHooks';

// Register caches if not already done in localconf.php or a previously loaded extension.
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['dbal'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['dbal'] = array(
		'backend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\TransientMemoryBackend'
	);
}
?>
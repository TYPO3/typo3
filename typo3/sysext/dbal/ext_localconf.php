<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Core\\Database\\DatabaseConnection'] = array('className' => 'TYPO3\\CMS\\Dbal\\Database\\DatabaseConnection');
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Core\\Database\\SqlParser'] = array('className' => 'TYPO3\\CMS\\Dbal\\Database\\SqlParser');
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Recordlist\\RecordList\\DatabaseRecordList'] = array('className' => 'TYPO3\\CMS\\Dbal\\RecordList\\DatabaseRecordList');

// Register caches if not already done in localconf.php or a previously loaded extension.
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['dbal'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['dbal'] = array(
		'backend' => 'TYPO3\\CMS\\Core\\Cache\\Backend\\TransientMemoryBackend',
		'groups' => array()
	);
}

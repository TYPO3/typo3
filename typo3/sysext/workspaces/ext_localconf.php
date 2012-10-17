<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE == 'BE') {
	$workspaceSelectorToolbarItemClassPath = \TYPO3\CMS\Core\Extension\ExtensionManager::extPath('workspaces', 'Classes/ExtDirect/WorkspaceSelectorToolbarItem.php');
	$GLOBALS['TYPO3_CONF_VARS']['typo3/backend.php']['additionalBackendItems'][] = $workspaceSelectorToolbarItemClassPath;
}
// Register the autopublishing task
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['TYPO3\\CMS\\Workspaces\\Service\\AutoPublishServiceTask'] = array(
	'extension' => $_EXTKEY,
	'title' => ('LLL:EXT:' . $_EXTKEY) . '/Resources/Private/Language/locallang_mod.xml:autopublishTask.name',
	'description' => ('LLL:EXT:' . $_EXTKEY) . '/Resources/Private/Language/locallang_mod.xml:autopublishTask.description'
);
// Register the cleanup preview links task
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['TYPO3\\CMS\\Workspaces\\Task\\CleanupPreviewLinkTask'] = array(
	'extension' => $_EXTKEY,
	'title' => ('LLL:EXT:' . $_EXTKEY) . '/Resources/Private/Language/locallang_mod.xml:cleanupPreviewLinkTask.name',
	'description' => ('LLL:EXT:' . $_EXTKEY) . '/Resources/Private/Language/locallang_mod.xml:cleanupPreviewLinkTask.description'
);
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['workspaces'] = 'EXT:workspaces/Classes/Service/Tcemain.php:TYPO3\\CMS\\Workspaces\\Hook\\DataHandlerHook';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['viewOnClickClass']['workspaces'] = 'EXT:workspaces/Classes/Service/Befunc.php:TYPO3\\CMS\\Workspaces\\Hook\\BackendUtilityHook';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_eofe']['workspaces'] = 'EXT:workspaces/Classes/Service/Fehooks.php:TYPO3\\CMS\\Workspaces\\Hook\\TypoScriptFrontendControllerHook->hook_eofe';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/alt_doc.php']['makeEditForm_accessCheck']['workspaces'] = 'EXT:workspaces/Classes/Service/Befunc.php:TYPO3\\CMS\\Workspaces\\Hook\\BackendUtilityHook->makeEditForm_accessCheck';
// Register workspaces cache if not already done in localconf.php or a previously loaded extension.
// We do not set frontend and backend: The cache manager uses t3lib_Cache\Frontend\VariableFrontend
// as frontend and t3lib_cache_backend_DbBackend as backend by default if not set otherwise. This
// is perfectly fine for the workspaces_cache.
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['workspaces_cache'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['workspaces_cache'] = array();
}
\TYPO3\CMS\Core\Extension\ExtensionManager::addUserTSConfig('options.workspaces.considerReferences = 1');
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/tree/pagetree/class.t3lib_tree_pagetree_dataprovider.php']['postProcessCollections'][] = 'EXT:workspaces/Classes/ExtDirect/PagetreeCollectionsProcessor.php:TYPO3\\CMS\\Workspaces\\ExtDirect\\PagetreeCollectionsProcessor';
\TYPO3\CMS\Core\Extension\ExtensionManager::addUserTSConfig('options.workspaces.considerReferences = 1');
?>
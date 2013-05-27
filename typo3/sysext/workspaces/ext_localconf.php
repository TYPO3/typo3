<?php

if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}
if(TYPO3_MODE == 'BE') {
	$workspaceSelectorToolbarItemClassPath = t3lib_extMgm::extPath('workspaces', 'Classes/ExtDirect/WorkspaceSelectorToolbarItem.php');

	$GLOBALS['TYPO3_CONF_VARS']['typo3/backend.php']['additionalBackendItems'][] = $workspaceSelectorToolbarItemClassPath;

}
	// Register the autopublishing task
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['Tx_Workspaces_Service_AutoPublishTask'] = array(
	'extension'        => $_EXTKEY,
	'title'            => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod.xml:autopublishTask.name',
	'description'      => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod.xml:autopublishTask.description'
);
	// Register the cleanup preview links task
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['Tx_Workspaces_Service_CleanupPreviewLinkTask'] = array(
	'extension'        => $_EXTKEY,
	'title'            => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod.xml:cleanupPreviewLinkTask.name',
	'description'      => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod.xml:cleanupPreviewLinkTask.description'
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['workspaces'] = 'EXT:workspaces/Classes/Service/Tcemain.php:Tx_Workspaces_Service_Tcemain';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['viewOnClickClass']['workspaces'] = 'EXT:workspaces/Classes/Service/Befunc.php:Tx_Workspaces_Service_Befunc';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_eofe']['workspaces'] = 'EXT:workspaces/Classes/Service/Fehooks.php:Tx_Workspaces_Service_Fehooks->hook_eofe';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/alt_doc.php']['makeEditForm_accessCheck']['workspaces'] = 'EXT:workspaces/Classes/Service/Befunc.php:Tx_Workspaces_Service_Befunc->makeEditForm_accessCheck';

	// Register workspaces cache if not already done in localconf.php or a previously loaded extension.
	// We do not set frontend and backend: The cache manager uses t3lib_cache_frontend_VariableFrontend
	// as frontend and t3lib_cache_backend_DbBackend as backend by default if not set otherwise. This
	// is perfectly fine for the workspaces_cache.
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['workspaces_cache'])) {
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['workspaces_cache'] = array();
}

t3lib_extMgm::addUserTSConfig('options.workspaces.considerReferences = 1');

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/tree/pagetree/class.t3lib_tree_pagetree_dataprovider.php']['postProcessCollections'][] = 'EXT:workspaces/Classes/ExtDirect/PagetreeCollectionsProcessor.php:Tx_Workspaces_ExtDirect_PagetreeCollectionsProcessor';
t3lib_extMgm::addUserTSConfig('options.workspaces.considerReferences = 1');


?>
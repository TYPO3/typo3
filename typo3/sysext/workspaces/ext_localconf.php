<?php

if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}
if(TYPO3_MODE == 'BE') {
	$workspaceSelectorToolbarItemClassPath = t3lib_extMgm::extPath('workspaces', 'Classes/BackendUserInterface/WorkspaceSelectorToolbarItem.php');

	$GLOBALS['TYPO3_CONF_VARS']['typo3/backend.php']['additionalBackendItems'][] = $workspaceSelectorToolbarItemClassPath;

}
	// Register the autopublishing task
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_Workspaces_Service_AutoPublishTask'] = array(
	'extension'        => $_EXTKEY,
	'title'            => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod.xml:autopublishTask.name',
	'description'      => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod.xml:autopublishTask.description'
);

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['workspaces'] = 'EXT:workspaces/Classes/Service/Tcemain.php:tx_Workspaces_Service_Tcemain';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['viewOnClickClass']['workspaces'] = 'EXT:workspaces/Classes/Service/Tcemain.php:tx_Workspaces_Service_Befunc';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_eofe']['workspaces'] = 'EXT:workspaces/Classes/Service/Fehooks.php:tx_Workspaces_Service_Fehooks->hook_eofe';

if ($GLOBALS['TYPO3_CONF_VARS']['SYS']['useCachingFramework']) {
		// Initialize the caching framework. The caching framework is asking it self if it is initialized already before initializing.
	t3lib_cache::initializeCachingFramework();
	
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['sys_workspace_cache']['backend'] = 't3lib_cache_backend_DbBackend';
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['sys_workspace_cache']['options'] = array(
		'cacheTable' => 'sys_workspace_cache',
		'tagsTable' => 'sys_workspace_cache_tags'
	);
}

?>
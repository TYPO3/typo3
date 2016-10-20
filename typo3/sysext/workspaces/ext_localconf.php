<?php
defined('TYPO3_MODE') or die();

// Register the autopublishing task
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Workspaces\Task\AutoPublishTask::class] = [
    'extension' => 'workspaces',
    'title' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf:autopublishTask.name',
    'description' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf:autopublishTask.description'
];

// Register the cleanup preview links task
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Workspaces\Task\CleanupPreviewLinkTask::class] = [
    'extension' => 'workspaces',
    'title' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf:cleanupPreviewLinkTask.name',
    'description' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf:cleanupPreviewLinkTask.description'
];

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['workspaces'] = \TYPO3\CMS\Workspaces\Hook\DataHandlerHook::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['viewOnClickClass']['workspaces'] = \TYPO3\CMS\Workspaces\Hook\BackendUtilityHook::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_eofe']['workspaces'] = \TYPO3\CMS\Workspaces\Hook\TypoScriptFrontendControllerHook::class . '->hook_eofe';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/alt_doc.php']['makeEditForm_accessCheck']['workspaces'] = \TYPO3\CMS\Workspaces\Hook\BackendUtilityHook::class . '->makeEditForm_accessCheck';

// Register hook to check for the preview mode in the FE
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['connectToDB']['version_preview'] = \TYPO3\CMS\Workspaces\Hook\PreviewHook::class . '->checkForPreview';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['postBeUser']['version_preview'] = \TYPO3\CMS\Workspaces\Hook\PreviewHook::class . '->initializePreviewUser';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['getPagePermsClause']['version_preview'] = \TYPO3\CMS\Workspaces\Hook\PreviewHook::class . '->overridePagePermissionClause';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['calcPerms']['version_preview'] = \TYPO3\CMS\Workspaces\Hook\PreviewHook::class . '->overridePermissionCalculation';

// Register workspaces cache if not already done in localconf.php or a previously loaded extension.
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['workspaces_cache'])) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['workspaces_cache'] = [
        'groups' => ['all']
    ];
}

if (TYPO3_MODE === 'BE') {
    // If publishing/swapping dependent parent-child references, consider all parents and children
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('options.workspaces.considerReferences = 1');
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/tree/pagetree/class.t3lib_tree_pagetree_dataprovider.php']['postProcessCollections'][] = \TYPO3\CMS\Workspaces\Hooks\PagetreeCollectionsProcessor::class;

    $GLOBALS['TYPO3_CONF_VARS']['BE']['toolbarItems'][1435433114] = \TYPO3\CMS\Workspaces\Backend\ToolbarItems\WorkspaceSelectorToolbarItem::class;
}

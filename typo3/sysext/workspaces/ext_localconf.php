<?php
defined('TYPO3_MODE') or die();

// add default notification options to every page
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
tx_version.workspaces.stageNotificationEmail.subject = LLL:EXT:workspaces/Resources/Private/Language/locallang_emails.xlf:subject
tx_version.workspaces.stageNotificationEmail.message = LLL:EXT:workspaces/Resources/Private/Language/locallang_emails.xlf:message
# tx_version.workspaces.stageNotificationEmail.additionalHeaders =
');

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

// register the hook to actually do the work within DataHandler
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['workspaces'] = \TYPO3\CMS\Workspaces\Hook\DataHandlerHook::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['moveRecordClass']['version'] = \TYPO3\CMS\Workspaces\Hook\DataHandlerHook::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['viewOnClickClass']['workspaces'] = \TYPO3\CMS\Workspaces\Hook\BackendUtilityHook::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['hook_previewInfo']['workspaces'] = \TYPO3\CMS\Workspaces\Hook\TypoScriptFrontendControllerHook::class . '->renderPreviewInfo';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/alt_doc.php']['makeEditForm_accessCheck']['workspaces'] = \TYPO3\CMS\Workspaces\Hook\BackendUtilityHook::class . '->makeEditForm_accessCheck';

// Register hook to check for the preview mode in the FE
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['connectToDB']['version_preview'] = \TYPO3\CMS\Workspaces\Hook\PreviewHook::class . '->checkForPreview';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['postBeUser']['version_preview'] = \TYPO3\CMS\Workspaces\Hook\PreviewHook::class . '->initializePreviewUser';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['getPagePermsClause']['version_preview'] = \TYPO3\CMS\Workspaces\Hook\PreviewHook::class . '->overridePagePermissionClause';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['calcPerms']['version_preview'] = \TYPO3\CMS\Workspaces\Hook\PreviewHook::class . '->overridePermissionCalculation';

// Register workspaces cache if not already done in localconf.php or a previously loaded extension.
if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['workspaces_cache'] ?? false)) {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['workspaces_cache'] = [
        'groups' => ['all']
    ];
}

$GLOBALS['TYPO3_CONF_VARS']['BE']['toolbarItems'][1435433114] = \TYPO3\CMS\Workspaces\Backend\ToolbarItems\WorkspaceSelectorToolbarItem::class;

// Registers preview link icon
\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class)->registerIcon(
    'module-workspaces-action-preview-link',
    \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
    ['source' => 'EXT:workspaces/Resources/Public/Images/generate-ws-preview-link.png']
);

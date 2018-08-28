<?php
defined('TYPO3_MODE') or die();

// add default notification options to every page
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
tx_version.workspaces.stageNotificationEmail.subject = LLL:EXT:workspaces/Resources/Private/Language/locallang_emails.xlf:subject
tx_version.workspaces.stageNotificationEmail.message = LLL:EXT:workspaces/Resources/Private/Language/locallang_emails.xlf:message
# tx_version.workspaces.stageNotificationEmail.additionalHeaders =
');

// Register the autopublishing task
// This will be removed in favor of the CLI command workspace:autopublish
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Workspaces\Task\AutoPublishTask::class] = [
    'extension' => 'workspaces',
    'title' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf:autopublishTask.name',
    'description' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf:autopublishTask.description'
];

// Register the cleanup preview links task
// This will be removed in favor of the CLI command cleanup:previewlinks
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Workspaces\Task\CleanupPreviewLinkTask::class] = [
    'extension' => 'workspaces',
    'title' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf:cleanupPreviewLinkTask.name',
    'description' => 'LLL:EXT:workspaces/Resources/Private/Language/locallang_mod.xlf:cleanupPreviewLinkTask.description'
];

// register the hook to actually do the work within DataHandler
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['workspaces'] = \TYPO3\CMS\Workspaces\Hook\DataHandlerHook::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['moveRecordClass']['version'] = \TYPO3\CMS\Workspaces\Hook\DataHandlerHook::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_befunc.php']['viewOnClickClass']['workspaces'] = \TYPO3\CMS\Workspaces\Hook\BackendUtilityHook::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/alt_doc.php']['makeEditForm_accessCheck']['workspaces'] = \TYPO3\CMS\Workspaces\Hook\BackendUtilityHook::class . '->makeEditForm_accessCheck';

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

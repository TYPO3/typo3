<?php
defined('TYPO3_MODE') or die();

// register the hook to actually do the work within TCEmain
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['version'] = \TYPO3\CMS\Version\Hook\DataHandlerHook::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['moveRecordClass']['version'] = \TYPO3\CMS\Version\Hook\DataHandlerHook::class;

// Register hook to check for the preview mode in the FE
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['connectToDB']['version_preview'] = \TYPO3\CMS\Version\Hook\PreviewHook::class . '->checkForPreview';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['postBeUser']['version_preview'] = \TYPO3\CMS\Version\Hook\PreviewHook::class . '->initializePreviewUser';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['getPagePermsClause']['version_preview'] = \TYPO3\CMS\Version\Hook\PreviewHook::class . '->overridePagePermissionClause';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauthgroup.php']['calcPerms']['version_preview'] = \TYPO3\CMS\Version\Hook\PreviewHook::class . '->overridePermissionCalculation';

if (TYPO3_MODE === 'BE') {
    // add default notification options to every page
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSconfig('
	tx_version.workspaces.stageNotificationEmail.subject = LLL:EXT:version/Resources/Private/Language/locallang_emails.xlf:subject
	tx_version.workspaces.stageNotificationEmail.message = LLL:EXT:version/Resources/Private/Language/locallang_emails.xlf:message
	# tx_version.workspaces.stageNotificationEmail.additionalHeaders =
');
}

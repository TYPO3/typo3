<?php
defined('TYPO3_MODE') or die();

// register the hook to actually do the work within DataHandler
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass']['version'] = \TYPO3\CMS\Version\Hook\DataHandlerHook::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['moveRecordClass']['version'] = \TYPO3\CMS\Version\Hook\DataHandlerHook::class;

if (TYPO3_MODE === 'BE') {
    // add default notification options to every page
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('
	tx_version.workspaces.stageNotificationEmail.subject = LLL:EXT:version/Resources/Private/Language/locallang_emails.xlf:subject
	tx_version.workspaces.stageNotificationEmail.message = LLL:EXT:version/Resources/Private/Language/locallang_emails.xlf:message
	# tx_version.workspaces.stageNotificationEmail.additionalHeaders =
');
}

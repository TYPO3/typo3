<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
// Keep old code (pre-FAL) for installations that haven't upgraded yet.
// @deprecated since TYPO3 6.0, please remove at earliest in TYPO3 6.2
// existing installation - and files are merged, nothing to do
if ((!isset($GLOBALS['TYPO3_CONF_VARS']['INSTALL']['wizardDone']['TYPO3\\CMS\\Install\\Updates\\TceformsUpdateWizard']) || !\TYPO3\CMS\Core\Utility\GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['INSTALL']['wizardDone']['TYPO3\\CMS\\Install\\Updates\\TceformsUpdateWizard'], 'pages_language_overlay:media')) && !\TYPO3\CMS\Core\Utility\GeneralUtility::compat_version('6.0')) {
	\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog('This installation hasn\'t been migrated to FAL for the field $TCA[pages_language_overlay][columns][media] yet. Please do so before TYPO3 v7.');
	// Existing installation and no upgrade wizard was executed - and files haven't been merged: use the old code
	$GLOBALS['TCA']['pages_language_overlay']['columns']['media']['config'] = array(
		'type' => 'group',
		'internal_type' => 'file',
		'allowed' => $GLOBALS['TCA']['pages']['columns']['media']['config']['allowed'],
		'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
		'uploadfolder' => 'uploads/media',
		'show_thumbs' => '1',
		'size' => '3',
		'maxitems' => '100',
		'minitems' => '0'
	);
}
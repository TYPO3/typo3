<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
// keep old code (pre-FAL) for installations that haven't upgraded yet. please remove this code in TYPO3 7.0
// @deprecated since TYPO3 6.0, please remove in TYPO3 7.0
// existing installation - and files are merged, nothing to do
if ((!isset($GLOBALS['TYPO3_CONF_VARS']['INSTALL']['wizardDone']['TYPO3\\CMS\\Install\\Updates\\TceformsUpdateWizard']) || !\TYPO3\CMS\Core\Utility\GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['INSTALL']['wizardDone']['TYPO3\\CMS\\Install\\Updates\\TceformsUpdateWizard'], 'tt_content:image')) && !\TYPO3\CMS\Core\Utility\GeneralUtility::compat_version('6.0')) {
	\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog('This installation hasn\'t been migrated to FAL for the field $TCA[tt_content][columns][image] yet. Please do so before TYPO3 v7.');
	// Existing installation and no upgrade wizard was executed - and files haven't been merged: use the old code
	$GLOBALS['TCA']['tt_content']['columns']['image']['config'] = array(
		'type' => 'group',
		'internal_type' => 'file',
		'allowed' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
		'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
		'uploadfolder' => 'uploads/pics',
		'show_thumbs' => '1',
		'size' => '3',
		'maxitems' => '200',
		'minitems' => '0',
		'autoSizeMax' => 40
	);
}

if ((!isset($GLOBALS['TYPO3_CONF_VARS']['INSTALL']['wizardDone']['TYPO3\\CMS\\Install\\Updates\\TceformsUpdateWizard']) || !\TYPO3\CMS\Core\Utility\GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['INSTALL']['wizardDone']['TYPO3\\CMS\\Install\\Updates\\TceformsUpdateWizard'], 'tt_content:media')) && !\TYPO3\CMS\Core\Utility\GeneralUtility::compat_version('6.0')) {
	\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog('This installation hasn\'t been migrated to FAL for the field $TCA[tt_content][columns][media] yet. Please do so before TYPO3 v7.');
	// Existing installation and no upgrade wizard was executed - and files haven't been merged: use the old code
	$GLOBALS['TCA']['tt_content']['columns']['media']['config'] = array(
		'type' => 'group',
		'internal_type' => 'file',
		'allowed' => '',
		// Must be empty for disallowed to work.
		'disallowed' => PHP_EXTENSIONS_DEFAULT,
		'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
		'uploadfolder' => 'uploads/media',
		'show_thumbs' => '1',
		'size' => '3',
		'maxitems' => '10',
		'minitems' => '0'
	);
}
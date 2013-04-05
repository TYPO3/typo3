<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE == 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule('web', 'layout', 'top', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'layout/');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_web_layout', 'EXT:cms/locallang_csh_weblayout.xml');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('_MOD_web_info', 'EXT:cms/locallang_csh_webinfo.xml');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction('web_info', 'tx_cms_webinfo_page', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'web_info/class.tx_cms_webinfo.php', 'LLL:EXT:cms/locallang_tca.xlf:mod_tx_cms_webinfo_page');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::insertModuleFunction('web_info', 'tx_cms_webinfo_lang', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'web_info/class.tx_cms_webinfo_lang.php', 'LLL:EXT:cms/locallang_tca.xlf:mod_tx_cms_webinfo_lang');
}
// Add allowed records to pages:
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('pages_language_overlay,tt_content,sys_template,sys_domain,backend_layout');

if (!function_exists('user_sortPluginList')) {
	function user_sortPluginList(array &$parameters) {
		usort($parameters['items'], create_function('$item1,$item2', 'return strcasecmp($GLOBALS[\'LANG\']->sL($item1[0]),$GLOBALS[\'LANG\']->sL($item2[0]));'));
	}
}

// keep old code (pre-FAL) for installations that haven't upgraded yet. please remove this code in TYPO3 7.0
// @deprecated since TYPO3 6.0, please remove in TYPO3 7.0
// existing installation - and files are merged, nothing to do
if ((!isset($GLOBALS['TYPO3_CONF_VARS']['INSTALL']['wizardDone']['Tx_Install_Updates_File_TceformsUpdateWizard']) || !\TYPO3\CMS\Core\Utility\GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['INSTALL']['wizardDone']['Tx_Install_Updates_File_TceformsUpdateWizard'], 'tt_content:image')) && !\TYPO3\CMS\Core\Utility\GeneralUtility::compat_version('6.0')) {
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

if ((!isset($GLOBALS['TYPO3_CONF_VARS']['INSTALL']['wizardDone']['Tx_Install_Updates_File_TceformsUpdateWizard']) || !\TYPO3\CMS\Core\Utility\GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['INSTALL']['wizardDone']['Tx_Install_Updates_File_TceformsUpdateWizard'], 'tt_content:media')) && !\TYPO3\CMS\Core\Utility\GeneralUtility::compat_version('6.0')) {
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

// Keep old code (pre-FAL) for installations that haven't upgraded yet.
// @deprecated since TYPO3 6.0, please remove at earliest in TYPO3 6.2
// existing installation - and files are merged, nothing to do
if ((!isset($GLOBALS['TYPO3_CONF_VARS']['INSTALL']['wizardDone']['Tx_Install_Updates_File_TceformsUpdateWizard']) || !\TYPO3\CMS\Core\Utility\GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['INSTALL']['wizardDone']['Tx_Install_Updates_File_TceformsUpdateWizard'], 'pages_language_overlay:media')) && !\TYPO3\CMS\Core\Utility\GeneralUtility::compat_version('6.0')) {
	\TYPO3\CMS\Core\Utility\GeneralUtility::deprecationLog('This installation hasn\'t been migrated to FAL for the field $TCA[pages_language_overlay][columns][media] yet. Please do so before TYPO3 v7.');
	// Existing installation and no upgrade wizard was executed - and files haven't been merged: use the old code
	$GLOBALS['TCA']['pages_language_overlay']['columns']['media']['config'] = array(
		'type' => 'group',
		'internal_type' => 'file',
		'allowed' => $TCA['pages']['columns']['media']['config']['allowed'],
		'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
		'uploadfolder' => 'uploads/media',
		'show_thumbs' => '1',
		'size' => '3',
		'maxitems' => '100',
		'minitems' => '0'
	);
}
?>

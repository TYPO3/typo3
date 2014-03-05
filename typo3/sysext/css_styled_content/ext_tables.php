<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

// Add flexform
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('*', 'FILE:EXT:css_styled_content/flexform_ds.xml', 'table');

$GLOBALS['TCA']['tt_content']['types']['table']['showitem'] = 'CType;;4;;1-1-1, hidden, header;;3;;2-2-2, linkToTop;;;;4-4-4,
			--div--;LLL:EXT:cms/locallang_ttc.xlf:CType.I.5, layout;;10;;3-3-3, cols, bodytext;;9;nowrap:wizards[table], pi_flexform,
			--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.access, starttime, endtime, fe_group';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'static/', 'CSS Styled Content');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'static/v4.5/', 'CSS Styled Content TYPO3 v4.5');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'static/v4.6/', 'CSS Styled Content TYPO3 v4.6');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'static/v4.7/', 'CSS Styled Content TYPO3 v4.7');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'static/v6.0/', 'CSS Styled Content TYPO3 v6.0');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'static/v6.1/', 'CSS Styled Content TYPO3 v6.1');
$GLOBALS['TCA']['tt_content']['columns']['section_frame']['config']['items'][0] = array(
	'LLL:EXT:css_styled_content/locallang_db.xlf:tt_content.tx_cssstyledcontent_section_frame.I.0', '0'
);
$GLOBALS['TCA']['tt_content']['columns']['section_frame']['config']['items'][9] = array(
	'LLL:EXT:css_styled_content/locallang_db.xlf:tt_content.tx_cssstyledcontent_section_frame.I.9', '66'
);

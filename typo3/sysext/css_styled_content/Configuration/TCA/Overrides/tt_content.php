<?php
defined('TYPO3_MODE') or die();

// Add flexform
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('*', 'FILE:EXT:css_styled_content/flexform_ds.xml', 'table');

$GLOBALS['TCA']['tt_content']['types']['table']['showitem'] = '--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.general;general,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.header;header,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:CType.I.5, layout;;10, cols, bodytext;;9;nowrap:wizards[table], pi_flexform,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.appearance,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.frames;frames,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.table_layout;tablelayout,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.visibility;visibility,
					--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.access;access,
				--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.extended';

$GLOBALS['TCA']['tt_content']['columns']['section_frame']['config']['items'][0] = array(
	'LLL:EXT:css_styled_content/locallang_db.xlf:tt_content.tx_cssstyledcontent_section_frame.I.0', '0'
);

$GLOBALS['TCA']['tt_content']['columns']['section_frame']['config']['items'][9] = array(
	'LLL:EXT:css_styled_content/locallang_db.xlf:tt_content.tx_cssstyledcontent_section_frame.I.9', '66'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('css_styled_content', 'static/', 'CSS Styled Content');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('css_styled_content', 'static/v4.5/', 'CSS Styled Content TYPO3 v4.5');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('css_styled_content', 'static/v4.6/', 'CSS Styled Content TYPO3 v4.6');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('css_styled_content', 'static/v4.7/', 'CSS Styled Content TYPO3 v4.7');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('css_styled_content', 'static/v6.0/', 'CSS Styled Content TYPO3 v6.0');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('css_styled_content', 'static/v6.1/', 'CSS Styled Content TYPO3 v6.1');
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('css_styled_content', 'static/v6.2/', 'CSS Styled Content TYPO3 v6.2');

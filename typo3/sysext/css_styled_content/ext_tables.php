<?php
# TYPO3 SVN ID: $Id$
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

	// add flexform
t3lib_extMgm::addPiFlexFormValue('*', 'FILE:EXT:css_styled_content/flexform_ds.xml','table');
$TCA['tt_content']['types']['table']['showitem']='CType;;4;;1-1-1, hidden, header;;3;;2-2-2, linkToTop;;;;4-4-4,
			--div--;LLL:EXT:cms/locallang_ttc.xml:CType.I.5, layout;;10;;3-3-3, cols, bodytext;;9;nowrap:wizards[table], text_properties, pi_flexform,
			--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access, starttime, endtime, fe_group';

t3lib_extMgm::addStaticFile($_EXTKEY, 'static/', 'CSS Styled Content');
t3lib_extMgm::addStaticFile($_EXTKEY, 'static/v3.8/', 'CSS Styled Content TYPO3 v3.8');
t3lib_extMgm::addStaticFile($_EXTKEY, 'static/v3.9/', 'CSS Styled Content TYPO3 v3.9');
t3lib_extMgm::addStaticFile($_EXTKEY, 'static/v4.2/', 'CSS Styled Content TYPO3 v4.2');
t3lib_extMgm::addStaticFile($_EXTKEY, 'static/v4.3/', 'CSS Styled Content TYPO3 v4.3');
t3lib_extMgm::addStaticFile($_EXTKEY, 'static/v4.4/', 'CSS Styled Content TYPO3 v4.4');

$TCA['tt_content']['columns']['section_frame']['config']['items'][0] = array('LLL:EXT:css_styled_content/locallang_db.php:tt_content.tx_cssstyledcontent_section_frame.I.0', '0');
$TCA['tt_content']['columns']['section_frame']['config']['items'][9] = array('LLL:EXT:css_styled_content/locallang_db.php:tt_content.tx_cssstyledcontent_section_frame.I.9', '66');

?>
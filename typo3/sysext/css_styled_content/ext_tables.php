<?php
# TYPO3 SVN ID: $Id$
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

	// add flexform
t3lib_extMgm::addPiFlexFormValue('*', 'FILE:EXT:css_styled_content/flexform_ds.xml','table');
$TCA['tt_content']['types']['table']['showitem']='CType;;4;;1-1-1, hidden, header;;3;;2-2-2, linkToTop;;;;4-4-4,
			--div--;LLL:EXT:cms/locallang_ttc.xml:CType.I.5, layout;;10;;3-3-3, cols, bodytext;;9;nowrap:wizards[table], text_properties, pi_flexform,
			--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.access, starttime, endtime';

t3lib_extMgm::addStaticFile($_EXTKEY,'static/','CSS Styled Content');
?>

<?php
defined('TYPO3_MODE') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
	'*',
	'FILE:EXT:felogin/flexform.xml',
	'login'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem(
	'tt_content',
	'CType',
	array(
		'LLL:EXT:cms/locallang_ttc.xlf:CType.I.10',
		'login',
		'i/tt_content_login.gif'
	),
	'mailform',
	'after'
);

$GLOBALS['TCA']['tt_content']['types']['login']['showitem'] =
	'--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.general;general,'
	. '--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.header;header,'
	. '--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.plugin,pi_flexform;;;;1-1-1,'
	. '--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,'
	. '--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.visibility;visibility,'
	. '--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.access;access,'
	. '--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.appearance,'
	. '--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.frames;frames,'
	. '--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.behaviour,'
	. '--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.extended';

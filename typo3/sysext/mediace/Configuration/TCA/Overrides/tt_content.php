<?php
defined('TYPO3_MODE') or die();

/**
 * Registering CType "media" and "multimedia"
 */
$GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['multimedia'] = 'mimetypes-x-content-multimedia';
$GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['media'] = 'mimetypes-x-content-multimedia';
$GLOBALS['TCA']['tt_content']['ctrl']['typeicons']['multimedia'] = 'tt_content_mm.gif';
$GLOBALS['TCA']['tt_content']['ctrl']['typeicons']['media'] = 'tt_content_mm.gif';


// Register new CType in item list just before "menu"
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem('tt_content', 'CType',
	array(
		'LLL:EXT:cms/locallang_ttc.xlf:CType.I.7',
		'multimedia',
		'i/tt_content_mm.gif'
	),
	'menu', 'before'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem('tt_content', 'CType',
	array(
		'LLL:EXT:cms/locallang_ttc.xlf:CType.I.18',
		'media',
		'i/tt_content_mm.gif'
	),
	'menu', 'before'
);

// Add new field
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('tt_content', array(
	'multimedia' => array(
		'label' => 'LLL:EXT:cms/locallang_ttc.xlf:multimedia',
		'config' => array(
			'type' => 'group',
			'internal_type' => 'file',
			'allowed' => 'txt,html,htm,class,swf,swa,dcr,wav,avi,au,mov,asf,mpg,wmv,mp3,mp4,m4v',
			'max_size' => $GLOBALS['TYPO3_CONF_VARS']['BE']['maxFileSize'],
			'uploadfolder' => 'uploads/media',
			'size' => '2',
			'maxitems' => '1',
			'minitems' => '0'
		)
	)
));

// add type definition and palette
$GLOBALS['TCA']['tt_content']['types']['multimedia'] = array(
	'showitem' => '--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.general;general,
			--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.header;header,
		--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.media,
			--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.multimediafiles;multimediafiles,
		--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.appearance,
			--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.frames;frames,
		--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,
			--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.visibility;visibility,
			--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.access;access,
		--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.extended'
);
$GLOBALS['TCA']['tt_content']['types']['media'] = array(
	'showitem' => '--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.general;general,
			--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.header;header,
		--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.media,
			pi_flexform,
		--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.appearance,
			--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.frames;frames,
		--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,
			--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.visibility;visibility,
			--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.access;access,
		--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.behaviour,
			bodytext;LLL:EXT:cms/locallang_ttc.xlf:bodytext.ALT.media_formlabel,
		--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.extended'
);
$baseDefaultExtrasOfBodytext = '';
if (!empty($GLOBALS['TCA']['tt_content']['columns']['bodytext']['defaultExtras'])) {
	$baseDefaultExtrasOfBodytext = $GLOBALS['TCA']['tt_content']['columns']['bodytext']['defaultExtras'] . ':';
}
if (!is_array($GLOBALS['TCA']['tt_content']['types']['media']['columnsOverrides'])) {
	$GLOBALS['TCA']['tt_content']['types']['media']['columnsOverrides'] = array();
}
if (!is_array($GLOBALS['TCA']['tt_content']['types']['media']['columnsOverrides']['bodytext'])) {
	$GLOBALS['TCA']['tt_content']['types']['media']['columnsOverrides']['bodytext'] = array();
}
$GLOBALS['TCA']['tt_content']['types']['media']['columnsOverrides']['bodytext']['defaultExtras'] = $baseDefaultExtrasOfBodytext . 'richtext:rte_transform[flag=rte_enabled|mode=ts_css]';

$GLOBALS['TCA']['tt_content']['palettes']['multimediafiles'] = array(
	'showitem' => 'multimedia;LLL:EXT:cms/locallang_ttc.xlf:multimedia_formlabel, bodytext;LLL:EXT:cms/locallang_ttc.xlf:bodytext.ALT.multimedia_formlabel',
	'canNotCollapse' => 1
);
if (!is_array($GLOBALS['TCA']['tt_content']['types']['multimedia']['columnsOverrides'])) {
	$GLOBALS['TCA']['tt_content']['types']['multimedia']['columnsOverrides'] = array();
}
if (!is_array($GLOBALS['TCA']['tt_content']['types']['multimedia']['columnsOverrides']['bodytext'])) {
	$GLOBALS['TCA']['tt_content']['types']['multimedia']['columnsOverrides']['bodytext'] = array();
}
$GLOBALS['TCA']['tt_content']['types']['multimedia']['columnsOverrides']['bodytext']['defaultExtras'] = $baseDefaultExtrasOfBodytext . 'nowrap';


// Add flexform
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue('*', 'FILE:EXT:mediace/Configuration/FlexForms/media.xml', 'media');

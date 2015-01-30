<?php
defined('TYPO3_MODE') or die();

if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('form')) {

	$GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['mailform'] = 'mimetypes-x-content-form';
	$GLOBALS['TCA']['tt_content']['ctrl']['typeicons']['mailform'] = 'tt_content_form.gif';
	$GLOBALS['TCA']['tt_content']['columns']['bodytext']['config']['wizards']['forms'] = array(
		'notNewRecords' => 1,
		'enableByTypeConfig' => 1,
		'type' => 'script',
		'title' => 'LLL:EXT:cms/locallang_ttc.xlf:bodytext.W.forms',
		'icon' => 'wizard_forms.gif',
		'module' => array(
			'name' => 'wizard_forms',
			'urlParameters' => array(
				'special' => 'formtype_mail'
			)
		),
		'params' => array(
			'xmlOutput' => 0
		)
	);

	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem('tt_content', 'CType', array(
			'LLL:EXT:cms/locallang_ttc.xlf:CType.I.8',
			'mailform',
			'i/tt_content_form.gif'
		),
		'search',
		'before'
	);

	// set up the fields
	$GLOBALS['TCA']['tt_content']['types']['mailform'] = array(
		'showitem' => '
			--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.general;general,
			--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.header;header,
			bodytext;LLL:EXT:cms/locallang_ttc.xlf:bodytext.ALT.mailform_formlabel;;nowrap:wizards[forms],
		--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.appearance,
			--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.frames;frames,
		--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,
			--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.visibility;visibility,
			--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.access;access,
		--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.behaviour,
			--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.mailform;mailform,
		--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.extended'
	);
	$GLOBALS['TCA']['tt_content']['palettes']['mailform'] = array(
			'showitem' => 'pages;LLL:EXT:cms/locallang_ttc.xlf:pages.ALT.mailform, --linebreak--, subheader;LLL:EXT:cms/locallang_ttc.xlf:subheader.ALT.mailform_formlabel',
			'canNotCollapse' => 1
	);
}

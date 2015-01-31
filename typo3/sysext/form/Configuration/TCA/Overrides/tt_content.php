<?php
defined('TYPO3_MODE') or die();

// add an CType element "mailform"
$GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['mailform'] = 'mimetypes-x-content-form';
$GLOBALS['TCA']['tt_content']['ctrl']['typeicons']['mailform'] = 'tt_content_form.gif';

// check if there is already a forms tab and add the item after that, otherwise
// add the tab item as well
$additionalCTypeItem = array(
	'LLL:EXT:cms/locallang_ttc.xlf:CType.I.8',
	'mailform',
	'i/tt_content_form.gif'
);

$existingCTypeItems = $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'];
$groupFound = FALSE;
$groupPosition = FALSE;
foreach ($existingCTypeItems as $position => $item) {
	if ($item[0] === 'LLL:EXT:cms/locallang_ttc.xlf:CType.div.forms') {
		$groupFound = TRUE;
		$groupPosition = $position;
		break;
	}
}

if ($groupFound && $groupPosition) {
	// add the new CType item below CType
	array_splice($GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'], $groupPosition+1, 0, array(0 => $additionalCTypeItem));
} else {
	// nothing found, add two items (group + new CType) at the bottom of the list
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem('tt_content', 'CType',
		array('LLL:EXT:cms/locallang_ttc.xlf:CType.div.forms', '--div--')
	);
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem('tt_content', 'CType', $additionalCTypeItem);
}

$GLOBALS['TCA']['tt_content']['columns']['bodytext']['config']['wizards']['forms'] = array(
	'notNewRecords' => 1,
	'enableByTypeConfig' => 1,
	'type' => 'script',
	'title' => 'Form wizard',
	'icon' => 'wizard_forms.gif',
	'module' => array(
		'name' => 'wizard_form'
	),
	'params' => array(
		'xmlOutput' => 0
	)
);

$GLOBALS['TCA']['tt_content']['types']['mailform']['showitem'] = '
	--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.general;general,
	--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.header;header,
	--div--;LLL:EXT:cms/locallang_ttc.xlf:CType.I.8,
		bodytext;LLL:EXT:cms/locallang_ttc.xlf:bodytext.ALT.mailform;;nowrap:wizards[forms],
	--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.appearance,
		--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.frames;frames,
	--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,
		--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.visibility;visibility,
		--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.access;access,
';

// Add Default TS to Include static (from extensions)
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile('form', 'Configuration/TypoScript/', 'Default TS');

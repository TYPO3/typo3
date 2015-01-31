<?php
defined('TYPO3_MODE') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
	'*',
	'FILE:EXT:felogin/flexform.xml',
	'login'
);


// check if there is already a forms tab and add the item after that, otherwise
// add the tab item as well
$additionalCTypeItem = array(
	'LLL:EXT:cms/locallang_ttc.xlf:CType.I.10',
	'login',
	'i/tt_content_login.gif'
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
	array_splice($GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'], $groupPosition, 0, array(0 => $additionalCTypeItem));
} else {
	// nothing found, add two items (group + new CType) at the bottom of the list
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem('tt_content', 'CType',
		array('LLL:EXT:cms/locallang_ttc.xlf:CType.div.forms', '--div--')
	);
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem('tt_content', 'CType', $additionalCTypeItem);
}


$GLOBALS['TCA']['tt_content']['types']['login']['showitem'] =
	'--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.general;general,'
	. '--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.header;header,'
	. '--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.plugin,pi_flexform,'
	. '--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access,'
	. '--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.visibility;visibility,'
	. '--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.access;access,'
	. '--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.appearance,'
	. '--palette--;LLL:EXT:cms/locallang_ttc.xlf:palette.frames;frames,'
	. '--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.behaviour,'
	. '--div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.extended';

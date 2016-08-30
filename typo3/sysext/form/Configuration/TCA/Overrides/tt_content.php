<?php
defined('TYPO3_MODE') or die();

// add an CType element "mailform"
$GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['mailform'] = 'mimetypes-x-content-form';

// check if there is already a forms tab and add the item after that, otherwise
// add the tab item as well
$additionalCTypeItem = [
    'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.I.8',
    'mailform',
    'content-elements-mailform'
];

$existingCTypeItems = $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'];
$groupFound = false;
$groupPosition = false;
foreach ($existingCTypeItems as $position => $item) {
    if ($item[0] === 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.div.forms') {
        $groupFound = true;
        $groupPosition = $position;
        break;
    }
}

if ($groupFound && $groupPosition) {
    // add the new CType item below CType
    array_splice($GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'], $groupPosition+1, 0, [0 => $additionalCTypeItem]);
} else {
    // nothing found, add two items (group + new CType) at the bottom of the list
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem('tt_content', 'CType',
        ['LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.div.forms', '--div--']
    );
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItem('tt_content', 'CType', $additionalCTypeItem);
}

// predefined forms
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'tt_content',
    [
        'tx_form_predefinedform' => [
            'label' => 'LLL:EXT:form/Resources/Private/Language/Database.xlf:tx_form_predefinedform',
            'exclude' => true,
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'LLL:EXT:form/Resources/Private/Language/Database.xlf:tx_form_predefinedform.selectPredefinedForm',
                        ''
                    ],
                ],
            ],
        ],
    ]
);
$GLOBALS['TCA']['tt_content']['ctrl']['requestUpdate'] .= ',tx_form_predefinedform';

// Hide bodytext if a predefined form is selected
$GLOBALS['TCA']['tt_content']['columns']['bodytext']['displayCond']['AND'] = [
    'OR' => [
        'FIELD:CType:!=:mailform',
        'AND' => [
            'FIELD:CType:=:mailform',
            'FIELD:tx_form_predefinedform:REQ:false',
        ],
    ],
];

$GLOBALS['TCA']['tt_content']['columns']['bodytext']['config']['wizards']['forms'] = [
    'notNewRecords' => true,
    'enableByTypeConfig' => 1,
    'type' => 'script',
    'title' => 'Form wizard',
    'icon' => 'EXT:backend/Resources/Public/Images/FormFieldWizard/wizard_forms.gif',
    'module' => [
        'name' => 'wizard_form'
    ],
    'params' => [
        'xmlOutput' => 0
    ]
];

// Add palettes if they are not available
if (!isset($GLOBALS['TCA']['tt_content']['palettes']['visibility'])) {
    $GLOBALS['TCA']['tt_content']['palettes']['visibility'] = [
        'showitem' => '
            hidden;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:hidden_formlabel,
            sectionIndex;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:sectionIndex_formlabel,
            linkToTop;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:linkToTop_formlabel
        ',
    ];
}

if (!isset($GLOBALS['TCA']['tt_content']['palettes']['frames'])) {
    $GLOBALS['TCA']['tt_content']['palettes']['frames'] = [
        'showitem' => '
            layout;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:layout_formlabel
        ',
    ];
}

$GLOBALS['TCA']['tt_content']['types']['mailform']['showitem'] = '
	--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
	--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.header;header,rowDescription,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:CType.I.8,
		bodytext;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext.ALT.mailform,
		tx_form_predefinedform;LLL:EXT:form/Resources/Private/Language/Database.xlf:tx_form_predefinedform,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.frames;frames,
	--div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.visibility;visibility,
		--palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,
';
if (!is_array($GLOBALS['TCA']['tt_content']['types']['mailform']['columnsOverrides'])) {
    $GLOBALS['TCA']['tt_content']['types']['mailform']['columnsOverrides'] = [];
}
if (!is_array($GLOBALS['TCA']['tt_content']['types']['mailform']['columnsOverrides']['bodytext'])) {
    $GLOBALS['TCA']['tt_content']['types']['mailform']['columnsOverrides']['bodytext'] = [];
}
$GLOBALS['TCA']['tt_content']['types']['mailform']['columnsOverrides']['bodytext']['config']['renderType'] = 'formwizard';

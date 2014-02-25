<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

// Add Default TS to Include static (from extensions)
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript/', 'Default TS');

if (TYPO3_MODE === 'BE') {
	// Register wizard
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModulePath(
		'wizard_form',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Modules/Wizards/FormWizard/'
	);
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
	CType;;4;;1-1-1,
	hidden,
	header;;3;;2-2-2,
	linkToTop;;;;3-3-3,
	--div--;LLL:EXT:cms/locallang_ttc.xlf:CType.I.8,
	bodytext;LLL:EXT:cms/locallang_ttc.xlf:bodytext.ALT.mailform;;nowrap:wizards[forms];3-3-3,
	--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.access,
	starttime,
	endtime,
	fe_group
';

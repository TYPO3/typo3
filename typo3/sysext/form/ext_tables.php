<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

// Add Default TS to Include static (from extensions)
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript/', 'Default TS');

$GLOBALS['TCA']['tt_content']['columns']['bodytext']['config']['wizards']['forms'] = array(
	'notNewRecords' => 1,
	'enableByTypeConfig' => 1,
	'type' => 'script',
	'title' => 'Form wizard',
	'icon' => 'wizard_forms.gif',
	'script' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('form') . 'Classes/Controller/Wizard.php',
	'params' => array(
		'xmlOutput' => 0
	)
);
$GLOBALS['TCA']['tt_content']['types']['mailform']['showitem'] = '
	CType;;4;;1-1-1,
	hidden,
	header;;3;;2-2-2,
	linkToTop;;;;3-3-3,
	--div--;LLL:EXT:cms/locallang_ttc.xml:CType.I.8,
	bodytext;LLL:EXT:cms/locallang_ttc.php:bodytext.ALT.mailform;;nowrap:wizards[forms];3-3-3,
	--div--;LLL:EXT:cms/locallang_tca.xlf:pages.tabs.access,
	starttime,
	endtime,
	fe_group
';
?>
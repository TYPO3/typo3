<?php
defined('TYPO3_MODE') or die();

// Add the t3editor wizard on the bodytext field of tt_content
$GLOBALS['TCA']['tt_content']['columns']['bodytext']['config']['wizards']['t3editor'] = array(
	'enableByTypeConfig' => 1,
	'type' => 'userFunc',
	'userFunc' => 'TYPO3\\CMS\\T3editor\\FormWizard->main',
	'title' => 't3editor',
	'icon' => 'wizard_table.gif',
	'module' => array(
		'name' => 'wizard_table'
	),
	'params' => array(
		'format' => 'html',
		'style' => 'width:98%; height: 60%;'
	)
);

// Activate the t3editor only for type html
$GLOBALS['TCA']['tt_content']['types']['html']['showitem'] = str_replace('bodytext,', 'bodytext;LLL:EXT:cms/locallang_ttc.xlf:bodytext.ALT.html_formlabel;;nowrap:wizards[t3editor],', $GLOBALS['TCA']['tt_content']['types']['html']['showitem']);

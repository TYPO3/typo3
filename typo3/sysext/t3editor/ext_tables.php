<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

if (TYPO3_MODE === 'BE') {
	// Register AJAX handlers:
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler('T3Editor::saveCode', 'TYPO3\\CMS\\T3editor\\T3editor->ajaxSaveCode');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler('T3Editor::getPlugins', 'TYPO3\\CMS\\T3editor\\T3editor->getPlugins');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler('T3Editor_TSrefLoader::getTypes', 'TYPO3\\CMS\\T3editor\\TypoScriptReferenceLoader->processAjaxRequest');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler('T3Editor_TSrefLoader::getDescription', 'TYPO3\\CMS\\T3editor\\TypoScriptReferenceLoader->processAjaxRequest');
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerAjaxHandler('CodeCompletion::loadTemplates', 'TYPO3\\CMS\\T3editor\\CodeCompletion->processAjaxRequest');

	// Add the t3editor wizard on the bodytext field of tt_content
	$TCA['tt_content']['columns']['bodytext']['config']['wizards']['t3editor'] = array(
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
	$TCA['tt_content']['types']['html']['showitem'] = str_replace('bodytext,', 'bodytext;LLL:EXT:cms/locallang_ttc.xlf:bodytext.ALT.html_formlabel;;nowrap:wizards[t3editor],', $TCA['tt_content']['types']['html']['showitem']);
}

<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE === 'BE') {
	// Register AJAX handlers:
	$TYPO3_CONF_VARS['BE']['AJAX']['T3Editor::saveCode'] = 'EXT:t3editor/Classes/class.tx_t3editor.php:TYPO3\\CMS\\T3Editor\\T3Editor->ajaxSaveCode';
	$TYPO3_CONF_VARS['BE']['AJAX']['T3Editor::getPlugins'] = 'EXT:t3editor/Classes/class.tx_t3editor.php:TYPO3\\CMS\\T3Editor\\T3Editor->getPlugins';
	$TYPO3_CONF_VARS['BE']['AJAX']['T3Editor_TSrefLoader::getTypes'] = 'EXT:t3editor/Classes/ts_codecompletion/class.tx_t3editor_tsrefloader.php:TYPO3\\CMS\\T3Editor\\TypoScriptReferenceLoader->processAjaxRequest';
	$TYPO3_CONF_VARS['BE']['AJAX']['T3Editor_TSrefLoader::getDescription'] = 'EXT:t3editor/Classes/ts_codecompletion/class.tx_t3editor_tsrefloader.php:TYPO3\\CMS\\T3Editor\\TypoScriptReferenceLoader->processAjaxRequest';
	$TYPO3_CONF_VARS['BE']['AJAX']['CodeCompletion::loadTemplates'] = 'EXT:t3editor/Classes/ts_codecompletion/class.tx_t3editor_codecompletion.php:TYPO3\\CMS\\T3Editor\\CodeCompletion->processAjaxRequest';
	// Add the t3editor wizard on the bodytext field of tt_content
	$TCA['tt_content']['columns']['bodytext']['config']['wizards']['t3editor'] = array(
		'enableByTypeConfig' => 1,
		'type' => 'userFunc',
		'userFunc' => 'EXT:t3editor/Classes/class.tx_t3editor_tceforms_wizard.php:TYPO3\\CMS\\T3Editor\\FormWizard->main',
		'title' => 't3editor',
		'icon' => 'wizard_table.gif',
		'script' => 'wizard_table.php',
		'params' => array(
			'format' => 'html',
			'style' => 'width:98%; height: 200px;'
		)
	);
	// Activate the t3editor only for type html
	$TCA['tt_content']['types']['html']['showitem'] = str_replace('bodytext,', 'bodytext;LLL:EXT:cms/locallang_ttc.xml:bodytext.ALT.html_formlabel;;nowrap:wizards[t3editor],', $TCA['tt_content']['types']['html']['showitem']);
}
?>
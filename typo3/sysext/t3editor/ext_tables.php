<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

if (TYPO3_MODE === 'BE') {
		// Register AJAX handlers:
	$TYPO3_CONF_VARS['BE']['AJAX']['tx_t3editor::saveCode'] = 'EXT:t3editor/classes/class.tx_t3editor.php:tx_t3editor->ajaxSaveCode';
	$TYPO3_CONF_VARS['BE']['AJAX']['tx_t3editor::getPlugins'] = 'EXT:t3editor/classes/class.tx_t3editor.php:tx_t3editor->getPlugins';
	$TYPO3_CONF_VARS['BE']['AJAX']['tx_t3editor_TSrefLoader::getTypes'] = 'EXT:t3editor/classes/ts_codecompletion/class.tx_t3editor_tsrefloader.php:tx_t3editor_TSrefLoader->processAjaxRequest';
	$TYPO3_CONF_VARS['BE']['AJAX']['tx_t3editor_TSrefLoader::getDescription'] = 'EXT:t3editor/classes/ts_codecompletion/class.tx_t3editor_tsrefloader.php:tx_t3editor_TSrefLoader->processAjaxRequest';
	$TYPO3_CONF_VARS['BE']['AJAX']['tx_t3editor_codecompletion::loadTemplates'] = 'EXT:t3editor/classes/ts_codecompletion/class.tx_t3editor_codecompletion.php:tx_t3editor_codecompletion->processAjaxRequest';

	t3lib_div::loadTCA('tt_content');
		// Add the t3editor wizard on the bodytext field of tt_content
	$TCA['tt_content']['columns']['bodytext']['config']['wizards']['t3editor'] = array(
		'enableByTypeConfig' => 1,
		'type' => 'userFunc',
		'userFunc' => 'EXT:t3editor/classes/class.tx_t3editor_tceforms_wizard.php:tx_t3editor_tceforms_wizard->main',
		'title' => 't3editor',
		'icon' => 'wizard_table.gif',
		'script' => 'wizard_table.php',
		'params' => array(
			'format' => 'html',
		),
	);
		// Activate the t3editor only for type html
	$TCA['tt_content']['types']['html']['showitem'] = str_replace('bodytext,', 'bodytext;LLL:EXT:cms/locallang_ttc.xml:bodytext.ALT.html_formlabel;;nowrap:wizards[t3editor],', $TCA['tt_content']['types']['html']['showitem']);
}
?>
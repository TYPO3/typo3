<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

if (TYPO3_MODE == 'BE') {
	// Register AJAX handlers:
	$TYPO3_CONF_VARS['BE']['AJAX']['tx_t3editor::saveCode'] = 'EXT:t3editor/classes/class.tx_t3editor.php:tx_t3editor->ajaxSaveCode';
	$TYPO3_CONF_VARS['BE']['AJAX']['tx_t3editor::getPlugins'] = 'EXT:t3editor/classes/class.tx_t3editor.php:tx_t3editor->getPlugins';
	$TYPO3_CONF_VARS['BE']['AJAX']['tx_t3editor_TSrefLoader::getTypes'] = 'EXT:t3editor/classes/ts_codecompletion/class.tx_t3editor_tsrefloader.php:tx_t3editor_TSrefLoader->processAjaxRequest';
	$TYPO3_CONF_VARS['BE']['AJAX']['tx_t3editor_TSrefLoader::getDescription'] = 'EXT:t3editor/classes/ts_codecompletion/class.tx_t3editor_tsrefloader.php:tx_t3editor_TSrefLoader->processAjaxRequest';
	$TYPO3_CONF_VARS['BE']['AJAX']['tx_t3editor_codecompletion::loadTemplates'] = 'EXT:t3editor/classes/ts_codecompletion/class.tx_t3editor_codecompletion.php:tx_t3editor_codecompletion->processAjaxRequest';
}
?>
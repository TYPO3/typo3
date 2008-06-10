<?php

if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

if (TYPO3_MODE == 'BE') {
	$t3editorPath = t3lib_extMgm::extPath('t3editor');

	// register AJAX calls
	$GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX']['tx_t3editor::saveCode'] = $t3editorPath . 'class.tx_t3editor.php:tx_t3editor->saveCode';
}

?>

<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

if (TYPO3_MODE === 'BE') {
	t3lib_extMgm::addModule('web', 'perm', '', t3lib_extMgm::extPath($_EXTKEY) . 'mod1/');
	$TYPO3_CONF_VARS['BE']['AJAX']['SC_mod_web_perm_ajax::dispatch'] = t3lib_extMgm::extPath($_EXTKEY) . 'mod1/class.sc_mod_web_perm_ajax.php:SC_mod_web_perm_ajax->dispatch';
}
?>
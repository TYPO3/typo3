<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

if (TYPO3_MODE == 'BE') {
		// register hooks for tstemplate module
	$TYPO3_CONF_VARS['SC_OPTIONS']['typo3/template.php']['preStartPageHook'][] =
		'EXT:t3editor/classes/class.tx_t3editor_hooks_tstemplateinfo.php:&tx_t3editor_hooks_tstemplateinfo->preStartPageHook';
	$TYPO3_CONF_VARS['SC_OPTIONS']['ext/tstemplate_info/class.tx_tstemplateinfo.php']['postOutputProcessingHook'][] =
		'EXT:t3editor/classes/class.tx_t3editor_hooks_tstemplateinfo.php:&tx_t3editor_hooks_tstemplateinfo->postOutputProcessingHook';

	$TYPO3_CONF_VARS['SC_OPTIONS']['ext/t3editor/classes/class.tx_t3editor.php']['ajaxSaveCode']['tx_tstemplateinfo'] =
		'EXT:t3editor/classes/class.tx_t3editor_hooks_tstemplateinfo.php:&tx_t3editor_hooks_tstemplateinfo->save';
}

?>
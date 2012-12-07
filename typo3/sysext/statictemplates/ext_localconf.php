<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tstemplate.php']['includeStaticTypoScriptSources'][] = 'EXT:statictemplates/Classes/StaticTemplatesHook.php:TYPO3\\CMS\\Statictemplates\\StaticTemplatesHook->includeStaticTypoScriptSources';
?>
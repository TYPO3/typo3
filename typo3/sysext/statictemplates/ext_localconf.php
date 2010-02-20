<?php

if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tstemplate.php']['includeStaticTypoScriptSources'][] = 'EXT:statictemplates/class.tx_statictemplates.php:tx_statictemplates->includeStaticTypoScriptSources';

?>

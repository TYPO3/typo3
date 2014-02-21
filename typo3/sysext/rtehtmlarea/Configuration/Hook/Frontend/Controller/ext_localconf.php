<?php
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}
if (TYPO3_MODE === 'FE') {
	// Register frontend hook to add meta tag when rtehtmlarea is present and user agent is IE 11+
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output']['rtehtmlarea'] = 'TYPO3\\CMS\\Rtehtmlarea\\Hook\\Frontend\\Controller\\TypoScriptFrontendControllerHook->contentPostProcOutput';
}

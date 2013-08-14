<?php
if (TYPO3_MODE == 'BE') {
	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['LoginForm']['provideLoginForm'][100]
		= 'TYPO3\\CMS\\Backend\\View\\LoginForm\\Password->render';
}
?>

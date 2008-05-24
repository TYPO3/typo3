<?php

if(TYPO3_MODE == 'BE') {

		// handle stupid IE6
	$userAgent = t3lib_div::getIndpEnv('HTTP_USER_AGENT');

	if(!(strpos($userAgent, 'MSIE 6') === false)
	&& strpos($userAgent, 'Opera') === false
	&& strpos($userAgent, 'MSIE 7') === false) {
			//make sure we match IE6 but not Opera or IE7
		$GLOBALS['TYPO3backend']->addCssFile('ie6fix', 'sysext/t3skin/stylesheets/ie6.css');
	}

}

?>
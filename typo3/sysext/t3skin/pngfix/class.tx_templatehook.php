<?php

class tx_templatehook {
	function registerPngFix($params,$parent) {
			// handle stupid IE6
		$userAgent = t3lib_div::getIndpEnv('HTTP_USER_AGENT');

		if(!(strpos($userAgent, 'MSIE 6') === false)
		&& strpos($userAgent, 'Opera') === false
		&& strpos($userAgent, 'MSIE 7') === false) {
				//make sure we match IE6 but not Opera or IE7
			$params['pageRenderer']->addCssFile($parent->backPath . 'sysext/t3skin/stylesheets/ie6/z_t3-icons-gifSprites.css');
		}
	}
}

?>
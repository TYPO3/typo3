<?php

class tx_templatehook {
	function registerPngFix($params,$parent) {
			// handle stupid IE6
		$userAgent = t3lib_div::getIndpEnv('HTTP_USER_AGENT');

		if(!(strpos($userAgent, 'MSIE 6') === false)
		&& strpos($userAgent, 'Opera') === false
		&& strpos($userAgent, 'MSIE 7') === false) {
				//make sure we match IE6 but not Opera or IE7
			$files = t3lib_div::getFilesInDir(PATH_typo3 . 'sysext/t3skin/stylesheets/ie6', 'css', 0, 1);
			foreach($files as $fileName) {
				$params['pageRenderer']->addCssFile($parent->backPath . 'sysext/t3skin/stylesheets/ie6/' . $fileName);
			}

				// load files of spriteGenerator for ie6
			$files = t3lib_div::getFilesInDir(PATH_site . t3lib_SpriteManager::$tempPath . 'ie6/', 'css', 0, 1);
			foreach($files as $fileName) {
				$params['pageRenderer']->addCssFile($parent->backPath . '../' . t3lib_SpriteManager::$tempPath . 'ie6/' . $fileName);
			}

		}
	}
}

?>
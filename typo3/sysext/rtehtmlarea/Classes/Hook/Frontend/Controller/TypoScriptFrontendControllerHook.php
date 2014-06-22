<?php
namespace TYPO3\CMS\Rtehtmlarea\Hook\Frontend\Controller;
/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
/**
 * Frontend hook to add meta tag when rtehtmlarea is present and user agent is IE 11+
 *
 */
class TypoScriptFrontendControllerHook {

	/**
	 * Add meta tag when rtehtmlarea is present and user agent is IE 11+
	 *
	 * @param array $params
	 * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $controller
	 * @return void
	 */
	public function contentPostProcOutput(array $params, \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $controller) {
		if (strpos($controller->content, 'textarea id="RTEarea') !== FALSE) {
			$userAgent = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_USER_AGENT');
			$browserInfo = \TYPO3\CMS\Core\Utility\ClientUtility::getBrowserInfo($userAgent);
			if ($browserInfo['browser'] === 'msie' && $browserInfo['version'] > 10) {
				$controller->content = preg_replace('/<head([^>]*)>/', '<head$1>' . LF . '<meta http-equiv="X-UA-Compatible" content="IE=10" />', $controller->content);
			}
		}
	}
}

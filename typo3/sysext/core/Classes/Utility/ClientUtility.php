<?php
namespace TYPO3\CMS\Core\Utility;

/***************************************************************
 * Copyright notice
 *
 * (c) 2009-2013 Oliver Hader <oliver@typo3.org>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the textfile GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Class to handle and determine browser specific information.
 *
 * @author Oliver Hader <oliver@typo3.org>
 */
class ClientUtility {

	/**
	 * Generates an array with abstracted browser information
	 *
	 * @param string $userAgent The useragent string, \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_USER_AGENT')
	 * @return array Contains keys "browser", "version", "system
	 */
	static public function getBrowserInfo($userAgent) {
		// Hook: $TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/div/class.t3lib_utility_client.php']['getBrowserInfo']:
		$getBrowserInfoHooks = &$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/div/class.t3lib_utility_client.php']['getBrowserInfo'];
		if (is_array($getBrowserInfoHooks)) {
			foreach ($getBrowserInfoHooks as $hookFunction) {
				$returnResult = TRUE;
				$hookParameters = array(
					'userAgent' => &$userAgent,
					'returnResult' => &$returnResult
				);
				// need reference for third parameter in \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction,
				// so create a reference to NULL
				$null = NULL;
				$hookResult = \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($hookFunction, $hookParameters, $null);
				if ($returnResult && is_array($hookResult) && count($hookResult)) {
					return $hookResult;
				}
			}
		}
		$userAgent = trim($userAgent);
		$browserInfo = array(
			'useragent' => $userAgent
		);
		// Analyze the userAgent string
		// Declare known browsers to look for
		$known = array(
			'msie',
			'firefox',
			'webkit',
			'opera',
			'netscape',
			'konqueror',
			'gecko',
			'chrome',
			'safari',
			'seamonkey',
			'navigator',
			'mosaic',
			'lynx',
			'amaya',
			'omniweb',
			'avant',
			'camino',
			'flock',
			'aol'
		);
		$matches = array();
		$pattern = '#(?P<browser>' . join('|', $known) . ')[/ ]+(?P<version>[0-9]+(?:\\.[0-9]+)?)#';
		// Find all phrases (or return empty array if none found)
		if (!preg_match_all($pattern, strtolower($userAgent), $matches)) {
			$browserInfo['browser'] = 'unknown';
			$browserInfo['version'] = '';
			$browserInfo['all'] = array();
		} else {
			// Since some UAs have more than one phrase (e.g Firefox has a Gecko phrase,
			// Opera 7,8 have a MSIE phrase), use the last one found (the right-most one
			// in the UA).  That's usually the most correct.
			// For IE use the first match as IE sends multiple MSIE with version, from higher to lower.
			$lastIndex = count($matches['browser']) - 1;
			$browserInfo['browser'] = $matches['browser'][$lastIndex];
			$browserInfo['version'] = $browserInfo['browser'] === 'msie' ? $matches['version'][0] : $matches['version'][$lastIndex];
			// But return all parsed browsers / version in an extra array
			for ($i = 0; $i <= $lastIndex; $i++) {
				if (!isset($browserInfo['all'][$matches['browser'][$i]])) {
					$browserInfo['all'][$matches['browser'][$i]] = $matches['version'][$i];
				}
			}
			// Replace gecko build date with version given by rv
			if (isset($browserInfo['all']['gecko'])) {
				preg_match_all('/rv:([0-9\\.]*)/', strtolower($userAgent), $version);
				if ($version[1][0]) {
					$browserInfo['all']['gecko'] = $version[1][0];
				}
			}
		}
		$browserInfo['all_systems'] = array();
		if (strstr($userAgent, 'Win')) {
			// Windows
			if (strstr($userAgent, 'Windows NT 6.1')) {
				$browserInfo['all_systems'][] = 'win7';
				$browserInfo['all_systems'][] = 'winNT';
			} elseif (strstr($userAgent, 'Windows NT 6.0')) {
				$browserInfo['all_systems'][] = 'winVista';
				$browserInfo['all_systems'][] = 'winNT';
			} elseif (strstr($userAgent, 'Windows NT 5.1')) {
				$browserInfo['all_systems'][] = 'winXP';
				$browserInfo['all_systems'][] = 'winNT';
			} elseif (strstr($userAgent, 'Windows NT 5.0')) {
				$browserInfo['all_systems'][] = 'win2k';
				$browserInfo['all_systems'][] = 'winNT';
			} elseif (strstr($userAgent, 'Win98') || strstr($userAgent, 'Windows 98')) {
				$browserInfo['all_systems'][] = 'win98';
			} elseif (strstr($userAgent, 'Win95') || strstr($userAgent, 'Windows 95')) {
				$browserInfo['all_systems'][] = 'win95';
			} elseif (strstr($userAgent, 'WinNT') || strstr($userAgent, 'Windows NT')) {
				$browserInfo['all_systems'][] = 'winNT';
			} elseif (strstr($userAgent, 'Win16') || strstr($userAgent, 'Windows 311')) {
				$browserInfo['all_systems'][] = 'win311';
			}
		} elseif (strstr($userAgent, 'Mac')) {
			if (strstr($userAgent, 'iPad') || strstr($userAgent, 'iPhone') || strstr($userAgent, 'iPod')) {
				$browserInfo['all_systems'][] = 'iOS';
				$browserInfo['all_systems'][] = 'mac';
			} else {
				$browserInfo['all_systems'][] = 'mac';
			}
		} elseif (strstr($userAgent, 'Android')) {
			$browserInfo['all_systems'][] = 'android';
			$browserInfo['all_systems'][] = 'linux';
		} elseif (strstr($userAgent, 'Linux')) {
			$browserInfo['all_systems'][] = 'linux';
		} elseif (strstr($userAgent, 'BSD')) {
			$browserInfo['all_systems'][] = 'unix_bsd';
		} elseif (strstr($userAgent, 'SGI') && strstr($userAgent, ' IRIX ')) {
			$browserInfo['all_systems'][] = 'unix_sgi';
		} elseif (strstr($userAgent, ' SunOS ')) {
			$browserInfo['all_systems'][] = 'unix_sun';
		} elseif (strstr($userAgent, ' HP-UX ')) {
			$browserInfo['all_systems'][] = 'unix_hp';
		} elseif (strstr($userAgent, 'CrOS')) {
			$browserInfo['all_systems'][] = 'chrome';
			$browserInfo['all_systems'][] = 'linux';
		}
		return $browserInfo;
	}

	/**
	 * Returns the version of a browser; Basically getting doubleval() of the input string,
	 * stripping of any non-numeric values in the beginning of the string first.
	 *
	 * @param string $version A string with version number, eg. "/7.32 blablabla
	 * @return double Returns double value, eg. "7.32
	 */
	static public function getVersion($version) {
		return doubleval(preg_replace('/^[^0-9]*/', '', $version));
	}

	/**
	 * Gets a code for a browsing device based on the input useragent string.
	 *
	 * @param string $userAgent The useragent string, \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_USER_AGENT')
	 * @return string Code for the specific device type
	 */
	static public function getDeviceType($userAgent) {
		// Hook: $TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/div/class.t3lib_utility_client.php']['getDeviceType']:
		$getDeviceTypeHooks = &$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/div/class.t3lib_utility_client.php']['getDeviceType'];
		if (is_array($getDeviceTypeHooks)) {
			foreach ($getDeviceTypeHooks as $hookFunction) {
				$returnResult = TRUE;
				$hookParameters = array(
					'userAgent' => &$userAgent,
					'returnResult' => &$returnResult
				);
				// need reference for third parameter in \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction,
				// so create a reference to NULL
				$null = NULL;
				$hookResult = \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($hookFunction, $hookParameters, $null);
				if ($returnResult && is_string($hookResult) && !empty($hookResult)) {
					return $hookResult;
				}
			}
		}
		$userAgent = strtolower(trim($userAgent));
		$deviceType = '';
		// pda
		if (strstr($userAgent, 'avantgo')) {
			$deviceType = 'pda';
		}
		// wap
		$browser = substr($userAgent, 0, 4);
		$wapviwer = substr(stristr($userAgent, 'wap'), 0, 3);
		if ($wapviwer == 'wap' || $browser == 'noki' || $browser == 'eric' || $browser == 'r380' || $browser == 'up.b' || $browser == 'winw' || $browser == 'wapa') {
			$deviceType = 'wap';
		}
		// grabber
		if (strstr($userAgent, 'g.r.a.b.') || strstr($userAgent, 'utilmind httpget') || strstr($userAgent, 'webcapture') || strstr($userAgent, 'teleport') || strstr($userAgent, 'webcopier')) {
			$deviceType = 'grabber';
		}
		// robots
		if (strstr($userAgent, 'crawler') || strstr($userAgent, 'spider') || strstr($userAgent, 'googlebot') || strstr($userAgent, 'searchbot') || strstr($userAgent, 'infoseek') || strstr($userAgent, 'altavista') || strstr($userAgent, 'diibot')) {
			$deviceType = 'robot';
		}
		return $deviceType;
	}

}


?>
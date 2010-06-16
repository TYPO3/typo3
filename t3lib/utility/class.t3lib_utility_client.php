<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2009 Oliver Hader <oliver@typo3.org>
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
 * $Id$
 *
 * @author	Oliver Hader <oliver@typo3.org>
 */
final class t3lib_utility_Client {

	/**
	 * Generates an array with abstracted browser information
	 *
	 * @param	string		$userAgent: The useragent string, t3lib_div::getIndpEnv('HTTP_USER_AGENT')
	 * @return	array		Contains keys "browser", "version", "system"
	 */
	public static function getBrowserInfo($userAgent) {
			// Hook: $TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/div/class.t3lib_utility_client.php']['getBrowserInfo']:
		$getBrowserInfoHooks =& $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/div/class.t3lib_utility_client.php']['getBrowserInfo'];
		if (is_array($getBrowserInfoHooks)) {
			foreach ($getBrowserInfoHooks as $hookFunction) {
				$returnResult = true;
				$hookParameters = array(
					'userAgent' => &$userAgent,
					'returnResult' => &$returnResult,
				);

					// need reference for third parameter in t3lib_div::callUserFunction,
					// so create a reference to NULL
				$null = NULL;
				$hookResult = t3lib_div::callUserFunction($hookFunction, $hookParameters, $null);
				if ($returnResult && is_array($hookResult) && count($hookResult)) {
					return $hookResult;
				}
			}
		}

		$userAgent = trim($userAgent);
		$browserInfo = array(
			'useragent' => $userAgent,
		);

		if ($userAgent) {
			// browser
			if (strstr($userAgent,'MSIE'))	{
				$browserInfo['browser']='msie';
			} elseif(strstr($userAgent,'Konqueror'))	{
				$browserInfo['browser']='konqueror';
			} elseif(strstr($userAgent,'Opera'))	{
				$browserInfo['browser']='opera';
			} elseif(strstr($userAgent,'Lynx'))	{
				$browserInfo['browser']='lynx';
			} elseif(strstr($userAgent,'PHP'))	{
				$browserInfo['browser']='php';
			} elseif(strstr($userAgent,'AvantGo'))	{
				$browserInfo['browser']='avantgo';
			} elseif(strstr($userAgent,'WebCapture'))	{
				$browserInfo['browser']='acrobat';
			} elseif(strstr($userAgent,'IBrowse'))	{
				$browserInfo['browser']='ibrowse';
			} elseif(strstr($userAgent,'Teleport'))	{
				$browserInfo['browser']='teleport';
			} elseif(strstr($userAgent,'Mozilla'))	{
				$browserInfo['browser']='netscape';
			} else {
				$browserInfo['browser']='unknown';
			}

			// version
			switch($browserInfo['browser']) {
				case 'netscape':
					$browserInfo['version'] = self::getVersion(substr($userAgent, 8));
					if (strstr($userAgent, 'Netscape6')) {
						$browserInfo['version'] = 6;
					}
				break;
				case 'msie':
					$tmp = strstr($userAgent, 'MSIE');
					$browserInfo['version'] = self::getVersion(substr($tmp, 4));
				break;
				case 'opera':
					$tmp = strstr($userAgent, 'Opera');
					$browserInfo['version'] = self::getVersion(substr($tmp, 5));
				break;
				case 'lynx':
					$tmp = strstr($userAgent, 'Lynx/');
					$browserInfo['version'] = self::getVersion(substr($tmp, 5));
				break;
				case 'php':
					$tmp = strstr($userAgent, 'PHP/');
					$browserInfo['version'] = self::getVersion(substr($tmp, 4));
				break;
				case 'avantgo':
					$tmp = strstr($userAgent, 'AvantGo');
					$browserInfo['version'] = self::getVersion(substr($tmp, 7));
				break;
				case 'acrobat':
					$tmp = strstr($userAgent, 'WebCapture');
					$browserInfo['version'] = self::getVersion(substr($tmp, 10));
				break;
				case 'ibrowse':
					$tmp = strstr($userAgent, 'IBrowse/');
					$browserInfo['version'] = self::getVersion(substr($tmp ,8));
				break;
				case 'konqueror':
					$tmp = strstr($userAgent, 'Konqueror/');
					$browserInfo['version'] = self::getVersion(substr($tmp, 10));
				break;
			}
			// system
			$browserInfo['system'] = '';
			if (strstr($userAgent, 'Win')) {
				// windows
				if (strstr($userAgent, 'Win98') || strstr($userAgent, 'Windows 98')) {
					$browserInfo['system'] = 'win98';
				} elseif (strstr($userAgent, 'Win95') || strstr($userAgent, 'Windows 95')) {
					$browserInfo['system'] = 'win95';
				} elseif (strstr($userAgent, 'WinNT') || strstr($userAgent, 'Windows NT')) {
					$browserInfo['system'] = 'winNT';
				} elseif (strstr($userAgent, 'Win16') || strstr($userAgent, 'Windows 311')) {
					$browserInfo['system'] = 'win311';
				}
			} elseif (strstr($userAgent,'Mac')) {
				$browserInfo['system'] = 'mac';
				// unixes
			} elseif (strstr($userAgent, 'Linux')) {
				$browserInfo['system'] = 'linux';
			} elseif (strstr($userAgent, 'SGI') && strstr($userAgent, ' IRIX ')) {
				$browserInfo['system'] = 'unix_sgi';
			} elseif (strstr($userAgent, ' SunOS ')) {
				$browserInfo['system'] = 'unix_sun';
			} elseif (strstr($userAgent, ' HP-UX ')) {
				$browserInfo['system'] = 'unix_hp';
			}
		}

		return $browserInfo;
	}

	/**
	 * Returns the version of a browser; Basically getting doubleval() of the input string,
	 * stripping of any non-numeric values in the beginning of the string first.
	 *
	 * @param	string		$version: A string with version number, eg. "/7.32 blablabla"
	 * @return	double		Returns double value, eg. "7.32"
	 */
	public static function getVersion($version) {
		return doubleval(preg_replace('/^[^0-9]*/', '', $version));
	}

	/**
	 * Gets a code for a browsing device based on the input useragent string.
	 *
	 * @param	string		$userAgent: The useragent string, t3lib_div::getIndpEnv('HTTP_USER_AGENT')
	 * @return	string		Code for the specific device type
	 */
	public static function getDeviceType($userAgent) {
			// Hook: $TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/div/class.t3lib_utility_client.php']['getDeviceType']:
		$getDeviceTypeHooks =& $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/div/class.t3lib_utility_client.php']['getDeviceType'];
		if (is_array($getDeviceTypeHooks)) {
			foreach ($getDeviceTypeHooks as $hookFunction) {
				$returnResult = true;
				$hookParameters = array(
					'userAgent' => &$userAgent,
					'returnResult' => &$returnResult,
				);

					// need reference for third parameter in t3lib_div::callUserFunction,
					// so create a reference to NULL
				$null = NULL;
				$hookResult = t3lib_div::callUserFunction($hookFunction, $hookParameters, $null);
				if ($returnResult && is_string($hookResult) && !empty($hookResult)) {
					return $hookResult;
				}
			}
		}

		$userAgent = strtolower(trim($userAgent));
		$deviceType = '';

			// pda
		if(strstr($userAgent, 'avantgo')) {
			$deviceType = 'pda';
		}
			// wap
		$browser=substr($userAgent, 0, 4);
		$wapviwer=substr(stristr($userAgent,'wap'), 0, 3);
		if($wapviwer == 'wap' ||
			$browser == 'noki' ||
			$browser == 'eric' ||
			$browser == 'r380' ||
			$browser == 'up.b' ||
			$browser == 'winw' ||
			$browser == 'wapa') {
			$deviceType = 'wap';
		}
			// grabber
		if(strstr($userAgent, 'g.r.a.b.') ||
			strstr($userAgent, 'utilmind httpget') ||
			strstr($userAgent, 'webcapture') ||
			strstr($userAgent, 'teleport') ||
			strstr($userAgent, 'webcopier')) {
			$deviceType = 'grabber';
		}
			// robots
		if(strstr($userAgent, 'crawler') ||
			strstr($userAgent, 'spider') ||
			strstr($userAgent, 'googlebot') ||
			strstr($userAgent, 'searchbot') ||
			strstr($userAgent, 'infoseek') ||
			strstr($userAgent, 'altavista') ||
			strstr($userAgent, 'diibot')) {
			$deviceType = 'robot';
		}

		return $deviceType;
	}
}
?>
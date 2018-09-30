<?php
namespace TYPO3\CMS\Core\Utility;

/*
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
 * Class to handle and determine browser specific information.
 */
class ClientUtility
{
    /**
     * Generates an array with abstracted browser information
     *
     * @param string $userAgent The useragent string, \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('HTTP_USER_AGENT')
     * @return array Contains keys "browser", "version", "system
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    public static function getBrowserInfo($userAgent)
    {
        trigger_error('ClientUtility::getBrowserInfo() will be removed with TYPO3 v10.0.', E_USER_DEPRECATED);
        // Hook: $TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/div/class.t3lib_utility_client.php']['getBrowserInfo']:
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/div/class.t3lib_utility_client.php']['getBrowserInfo'] ?? [] as $hookFunction) {
            $returnResult = true;
            $hookParameters = [
                'userAgent' => &$userAgent,
                'returnResult' => &$returnResult
            ];
            // need reference for third parameter in \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction,
            // so create a reference to NULL
            $null = null;
            $hookResult = GeneralUtility::callUserFunction($hookFunction, $hookParameters, $null);
            if ($returnResult && is_array($hookResult) && !empty($hookResult)) {
                return $hookResult;
            }
        }
        $userAgent = trim($userAgent);
        $browserInfo = [
            'useragent' => $userAgent
        ];
        // Analyze the userAgent string
        // Declare known browsers to look for
        $known = [
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
        ];
        $matches = [];
        $pattern = '#(?P<browser>' . implode('|', $known) . ')[/ ]+(?P<version>[0-9]+(?:\\.[0-9]+)?)#';
        // Find all phrases (or return empty array if none found)
        if (!preg_match_all($pattern, strtolower($userAgent), $matches)) {
            // Microsoft Internet-Explorer 11 does not have a sign of "MSIE" or so in the useragent.
            // All checks from the pattern above fail here. Look for a special combination of
            // "Mozilla/5.0" in front, "Trident/7.0" in the middle and "like Gecko" at the end.
            // The version (revision) is given as "; rv:11.0" in the useragent then.
            unset($matches);
            $pattern = '#mozilla/5\\.0 \\(.*trident/7\\.0.*; rv:(?P<version>[0-9]+(?:\\.[0-9]+)?)\\) like gecko#i';
            if (preg_match_all($pattern, $userAgent, $matches)) {
                $browserInfo['browser'] = 'msie';
                $browserInfo['version'] = $matches['version'][0];
                $browserInfo['all'] = ['msie' => $matches['version'][0]];
            } else {
                $browserInfo['browser'] = 'unknown';
                $browserInfo['version'] = '';
                $browserInfo['all'] = [];
            }
        } else {
            // Since some UAs have more than one phrase (e.g Firefox has a Gecko phrase,
            // Opera 7,8 have a MSIE phrase), use the last one found (the right-most one
            // in the UA).  That's usually the most correct.
            // For IE use the first match as IE sends multiple MSIE with version, from higher to lower.
            $lastIndex = count($matches['browser']) - 1;
            $browserInfo['browser'] = $matches['browser'][$lastIndex];
            $browserInfo['version'] = $browserInfo['browser'] === 'msie' ? $matches['version'][0] : $matches['version'][$lastIndex];
            // But return all parsed browsers / version in an extra array
            $browserInfo['all'] = [];
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
        $browserInfo['all_systems'] = [];
        if (strstr($userAgent, 'Win')) {
            // Windows
            if (strstr($userAgent, 'Windows NT 6.2') || strstr($userAgent, 'Windows NT 6.3')) {
                $browserInfo['all_systems'][] = 'win8';
                $browserInfo['all_systems'][] = 'winNT';
            } elseif (strstr($userAgent, 'Windows NT 6.1')) {
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
     * Returns the version of a browser; Basically getting float value of the input string,
     * stripping of any non-numeric values in the beginning of the string first.
     *
     * @param string $version A string with version number, eg. "/7.32 blablabla
     * @return float Returns double value, eg. "7.32
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    public static function getVersion($version)
    {
        trigger_error('ClientUtility::getVersion() will be removed with TYPO3 v10.0.', E_USER_DEPRECATED);
        return (float)preg_replace('/^[^0-9]*/', '', $version);
    }
}

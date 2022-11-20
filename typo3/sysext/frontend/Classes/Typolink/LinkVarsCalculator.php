<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Frontend\Typolink;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Class to calculate so-called "linkVars", which is a TypoScript setting
 * to always append these query parameters (if available in an existing request)
 * to a URL when using TypoLink for pages.
 */
class LinkVarsCalculator
{
    /**
     * Calculates and sets the internal linkVars based upon the current request's GET parameters
     * and the setting "config.linkVars".
     */
    public function getAllowedLinkVarsFromRequest(string $linkVarsSetting, array $queryParams, Context $context): string
    {
        $calculatedLinkVars = '';
        $adminCommand = $queryParams['ADMCMD_prev'] ?? '';
        $isBackendUserLoggedIn = $context->getAspect('backend.user')->isLoggedIn();

        // This allows to keep the current logged-in workspace when navigating through the Frontend from a Backend link, and keep the logged-in state
        if (($adminCommand === 'LIVE' || $adminCommand === 'IGNORE') && $isBackendUserLoggedIn) {
            $linkVarsSetting = ltrim($linkVarsSetting . ',ADMCMD_prev', ',');
        }
        // This allows to keep the "ADMCMD_simUser" parameter when navigating through the Frontend from a Backend link, and keep the logged-in state
        if (!empty($queryParams['ADMCMD_simUser']) && $isBackendUserLoggedIn) {
            $linkVarsSetting = ltrim($linkVarsSetting . ',ADMCMD_simUser', ',');
        }
        // This allows to keep the "ADMCMD_simUser" parameter when navigating through the Frontend from a Backend link, and keep the logged-in state
        if (!empty($queryParams['ADMCMD_simTime']) && $isBackendUserLoggedIn) {
            $linkVarsSetting = ltrim($linkVarsSetting . ',ADMCMD_simTime', ',');
        }

        if (empty($linkVarsSetting)) {
            return '';
        }

        $linkVars = $this->splitLinkVarsString($linkVarsSetting);
        if (empty($linkVars)) {
            return '';
        }
        foreach ($linkVars as $linkVar) {
            $test = '';
            if (preg_match('/^(.*)\\((.+)\\)$/', $linkVar, $match)) {
                $linkVar = trim($match[1]);
                $test = trim($match[2]);
            }

            $keys = explode('|', $linkVar);
            $numberOfLevels = count($keys);
            $rootKey = trim($keys[0]);
            if (!isset($queryParams[$rootKey])) {
                continue;
            }
            $value = $queryParams[$rootKey];
            for ($i = 1; $i < $numberOfLevels; $i++) {
                $currentKey = trim($keys[$i]);
                if (isset($value[$currentKey])) {
                    $value = $value[$currentKey];
                } else {
                    $value = false;
                    break;
                }
            }
            if ($value !== false) {
                $parameterName = $keys[0];
                for ($i = 1; $i < $numberOfLevels; $i++) {
                    $parameterName .= '[' . $keys[$i] . ']';
                }
                if (!is_array($value)) {
                    $temp = rawurlencode((string)$value);
                    if ($test !== '' && !$this->isAllowedLinkVarValue($temp, $test)) {
                        // Error: This value was not allowed for this key
                        continue;
                    }
                    $value = '&' . $parameterName . '=' . $temp;
                } else {
                    if ($test !== '' && $test !== 'array') {
                        // Error: This key must not be an array!
                        continue;
                    }
                    $value = HttpUtility::buildQueryString([$parameterName => $value], '&');
                }
                $calculatedLinkVars .= $value;
            }
        }
        return $calculatedLinkVars;
    }

    /**
     * Split the link vars string by "," but not if the "," is inside of braces
     */
    protected function splitLinkVarsString(string $string): array
    {
        $tempCommaReplacementString = '###KASPER###';

        // replace every "," wrapped in "()" by a "unique" string
        $string = preg_replace_callback('/\((?>[^()]|(?R))*\)/', static function ($result) use ($tempCommaReplacementString) {
            return str_replace(',', $tempCommaReplacementString, $result[0]);
        }, $string) ?? '';

        $string = GeneralUtility::trimExplode(',', $string);

        // replace all "unique" strings back to ","
        return str_replace($tempCommaReplacementString, ',', $string);
    }

    /**
     * Checks if the value defined in "config.linkVars" contains an allowed value.
     * Otherwise, return FALSE which means the value will not be added to any links.
     *
     * @param string $haystack The string in which to find $value
     * @param string $value The string to find in $haystack
     * @return bool Returns TRUE if $value matches or is found in $haystack
     */
    protected function isAllowedLinkVarValue(string $haystack, string $value): bool
    {
        $isAllowed = false;
        // Integer
        if ($value === 'int' || $value === 'integer') {
            if (MathUtility::canBeInterpretedAsInteger($haystack)) {
                $isAllowed = true;
            }
        } elseif (preg_match('/^\\/.+\\/[imsxeADSUXu]*$/', $value)) {
            // Regular expression, only "//" is allowed as delimiter
            if (@preg_match($value, $haystack)) {
                $isAllowed = true;
            }
        } elseif (str_contains($value, '-')) {
            // Range
            if (MathUtility::canBeInterpretedAsInteger($haystack)) {
                $range = explode('-', $value);
                if ($range[0] <= $haystack && $range[1] >= $haystack) {
                    $isAllowed = true;
                }
            }
        } elseif (str_contains($value, '|')) {
            // List
            // Trim the input
            $haystack = str_replace(' ', '', $haystack);
            if (str_contains('|' . $value . '|', '|' . $haystack . '|')) {
                $isAllowed = true;
            }
        } elseif ($value === $haystack) {
            // String comparison
            $isAllowed = true;
        }
        return $isAllowed;
    }
}

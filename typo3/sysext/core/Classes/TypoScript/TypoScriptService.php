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

namespace TYPO3\CMS\Core\TypoScript;

/**
 * Helper class to manage and convert TypoScript into differently shaped arrays.
 * Also contains the functionality in TypoScript called "optionSplit".
 *
 * @internal
 */
class TypoScriptService
{
    /**
     * Removes all trailing dots recursively from TS settings array
     *
     * Extbase converts the "classical" TypoScript (with trailing dot) to a format without trailing dot,
     * to be more future-proof and not to have any conflicts with Fluid object accessor syntax.
     *
     * @param array<string|int, mixed> $typoScriptArray for example `['foo' => 'TEXT', 'foo.' => ['bar' => 'baz']]`
     * @return array<string|int, mixed> for example `['foo' => ['_typoScriptNodeValue' => 'TEXT', 'bar' => 'baz']]`
     * @internal Avoid using this method. This has been invented for Extbase, which decided to move TypoScript
     *           arrays around in just another different way.
     * @todo: Usages should be removed mid-term to work on the TypoScript object tree directly.
     */
    public function convertTypoScriptArrayToPlainArray(array $typoScriptArray): array
    {
        foreach ($typoScriptArray as $key => $value) {
            if (str_ends_with((string)$key, '.')) {
                $keyWithoutDot = substr((string)$key, 0, -1);
                $typoScriptNodeValue = $typoScriptArray[$keyWithoutDot] ?? null;
                if (is_array($value)) {
                    $typoScriptArray[$keyWithoutDot] = $this->convertTypoScriptArrayToPlainArray($value);
                    if ($typoScriptNodeValue !== null) {
                        $typoScriptArray[$keyWithoutDot]['_typoScriptNodeValue'] = $typoScriptNodeValue;
                    }
                    unset($typoScriptArray[$key]);
                } else {
                    $typoScriptArray[$keyWithoutDot] = null;
                }
            }
        }
        return $typoScriptArray;
    }

    /**
     * Returns an array with Typoscript the old way (with dot).
     *
     * Extbase converts the "classical" TypoScript (with trailing dot) to a format without trailing dot,
     * to be more future-proof and not to have any conflicts with Fluid object accessor syntax.
     * However, if you want to call legacy TypoScript objects, you somehow need the "old" syntax (because this is what TYPO3 is used to).
     * With this method, you can convert the extbase TypoScript to classical TYPO3 TypoScript which is understood by the rest of TYPO3.
     *
     * @param array $plainArray A TypoScript Array with Extbase Syntax (without dot but with _typoScriptNodeValue)
     * @return array Array with TypoScript as usual (with dot)
     * @internal Avoid using this method. This has been invented for Extbase, which decided to move TypoScript
     *           arrays around in just another different way.
     * @todo: Usages should be removed mid-term to work on the TypoScript object tree directly.
     */
    public function convertPlainArrayToTypoScriptArray(array $plainArray): array
    {
        $typoScriptArray = [];
        foreach ($plainArray as $key => $value) {
            if (is_array($value)) {
                if (isset($value['_typoScriptNodeValue'])) {
                    $typoScriptArray[$key] = $value['_typoScriptNodeValue'];
                    unset($value['_typoScriptNodeValue']);
                }
                $typoScriptArray[$key . '.'] = $this->convertPlainArrayToTypoScriptArray($value);
            } else {
                $typoScriptArray[$key] = $value ?? '';
            }
        }
        return $typoScriptArray;
    }

    /**
     * Implementation of the "optionSplit" feature in TypoScript (used eg. for MENU objects)
     * What it does is to split the incoming TypoScript array so that the values are exploded by certain
     * strings ("||" and "|*|") and each part distributed into individual TypoScript arrays with a similar structure,
     * but individualized values.
     * The concept is known as "optionSplit" and is rather advanced to handle but quite powerful, in particular
     * for creating menus in TYPO3.
     *
     * @param array $originalConfiguration A TypoScript array
     * @param int $splitCount The number of items for which to generate individual TypoScript arrays
     * @return array The individualized TypoScript array.
     * @todo: This method is a sign of bad abstraction. It should be modelled differently and located elsewhere.
     */
    public function explodeConfigurationForOptionSplit(array $originalConfiguration, int $splitCount): array
    {
        $finalConfiguration = [];
        if (!$splitCount) {
            return $finalConfiguration;
        }
        // Initialize output to carry at least the keys
        for ($aKey = 0; $aKey < $splitCount; $aKey++) {
            $finalConfiguration[$aKey] = [];
        }
        // Recursive processing of array keys
        foreach ($originalConfiguration as $cKey => $val) {
            if (is_array($val)) {
                $tempConf = $this->explodeConfigurationForOptionSplit($val, $splitCount);
                foreach ($tempConf as $aKey => $val2) {
                    $finalConfiguration[$aKey][$cKey] = $val2;
                }
            } elseif (is_string($val)) {
                // Splitting of all values on this level of the TypoScript object tree:
                if ($cKey === 'noTrimWrap' || (!str_contains($val, '|*|') && !str_contains($val, '||'))) {
                    for ($aKey = 0; $aKey < $splitCount; $aKey++) {
                        $finalConfiguration[$aKey][$cKey] = $val;
                    }
                } else {
                    $main = explode('|*|', $val);
                    $lastC = 0;
                    $middleC = 0;
                    $firstC = 0;
                    if ($main[0]) {
                        $first = explode('||', $main[0]);
                        $firstC = count($first);
                    }
                    $middle = [];
                    if (!empty($main[1])) {
                        $middle = explode('||', $main[1]);
                        $middleC = count($middle);
                    }
                    $last = [];
                    $value = '';
                    if (!empty($main[2])) {
                        $last = explode('||', $main[2]);
                        $lastC = count($last);
                        $value = $last[0];
                    }
                    for ($aKey = 0; $aKey < $splitCount; $aKey++) {
                        if ($firstC && isset($first[$aKey])) {
                            $value = $first[$aKey];
                        } elseif ($middleC) {
                            $value = $middle[($aKey - $firstC) % $middleC];
                        }
                        if ($lastC && $lastC >= $splitCount - $aKey) {
                            $value = $last[$lastC - ($splitCount - $aKey)];
                        }
                        $finalConfiguration[$aKey][$cKey] = trim($value);
                    }
                }
            }
        }
        return $finalConfiguration;
    }
}
